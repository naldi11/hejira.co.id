<?php

namespace App\Services;

use App\Models\JihansGudangStock;
use App\Models\JihansGudangStockMovement;
use App\Models\HendhysStockBranch;
use App\Models\HendhysStockIn;
use App\Models\HendhysStockMovement;
use App\Models\HendhysStockPusat;
use App\Models\JihansRetailStock;
use App\Models\JihansRetailStockIn;
use App\Models\JihansRetailStockInDetail;
use App\Models\JihansRetailStockMovement;
use App\Models\Product;
use App\Models\TransferOut;
use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private NumberGeneratorService $numbers) {}

    // ── Jihans Gudang ─────────────────────────────────────────────────────────

    public function creditJihansGudang(
        int    $productId,
        int    $unitId,
        int    $qty,
        string $source,
        int    $referenceId,
        ?int   $userId = null,
        ?string $notes = null
    ): void {
        $stock = JihansGudangStock::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
        );

        $before = (int) $stock->quantity;
        $after  = $before + $qty;

        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        JihansGudangStockMovement::create([
            'product_id'      => $productId,
            'type'            => 'in',
            'source'          => $source,
            'reference_id'    => $referenceId,
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'notes'           => $notes,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    public function debitJihansGudang(
        int    $productId,
        int    $qty,
        string $source,
        int    $referenceId,
        ?int   $userId = null,
        ?string $notes = null
    ): void {
        $stock = JihansGudangStock::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'unit_id' => \App\Models\Product::find($productId)?->unit_id ?? 1, 'last_updated' => now()]
        );

        $before = (int) $stock->quantity;
        $after  = max(0, $before - $qty);

        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        JihansGudangStockMovement::create([
            'product_id'      => $productId,
            'type'            => 'out',
            'source'          => $source,
            'reference_id'    => $referenceId,
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'notes'           => $notes,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    /**
     * Write a movement log entry WITHOUT changing the stock balance.
     * Used for defect / expired returns so they appear in Kartu Stok
     * but do not inflate the sellable qty.
     */
    public function logJihansGudangReturnMovement(
        int    $productId,
        int    $qty,
        string $source,
        int    $referenceId,
        ?int   $userId = null,
        ?string $notes = null
    ): void {
        $currentQty = (int) (JihansGudangStock::where('product_id', $productId)->value('quantity') ?? 0);

        JihansGudangStockMovement::create([
            'product_id'      => $productId,
            'type'            => 'in',
            'source'          => $source,
            'reference_id'    => $referenceId,
            'quantity'        => $qty,
            'quantity_before' => $currentQty,
            'quantity_after'  => $currentQty,   // balance unchanged
            'notes'           => $notes,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    public function adjustJihansGudang(
        int    $productId,
        int    $unitId,
        int    $newQty,
        ?int   $userId = null,
        ?string $notes = null
    ): void {
        $stock = JihansGudangStock::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
        );

        $before = (int) $stock->quantity;
        $diff   = $newQty - $before;
        $type   = $diff >= 0 ? 'in' : 'out';

        $stock->update(['quantity' => $newQty, 'last_updated' => now()]);

        JihansGudangStockMovement::create([
            'product_id'      => $productId,
            'type'            => $type,
            'source'          => 'adjustment',
            'reference_id'    => null,
            'quantity'        => abs($diff),
            'quantity_before' => $before,
            'quantity_after'  => $newQty,
            'notes'           => $notes ?? 'Penyesuaian stok manual',
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    // ── Transfer Keluar: hanya debit Jihans Gudang ───────────────────────────

    public function processTransferOut(TransferOut $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $userId = $transfer->created_by;

            foreach ($transfer->details as $detail) {
                $this->debitJihansGudang(
                    $detail->product_id,
                    $detail->quantity,
                    'transfer_out',
                    $transfer->id,
                    $userId
                );
            }
        });
    }

    // ── Penerimaan Transfer: credit entity berdasarkan qty diterima ───────────

    public function processTransferReceive(TransferOut $transfer, int $userId): void
    {
        foreach ($transfer->details as $detail) {
            $qty = (float) $detail->received_quantity;
            if ($qty <= 0) continue;

            if ($transfer->to_entity === 'jihans') {
                $this->creditJihansRetail($detail->product_id, $detail->unit_id, $qty, 'transfer_gudang', $transfer->id, $userId);
            } else {
                $this->creditHendhys($detail->product_id, $detail->unit_id, $qty, $transfer->branch_id, 'transfer_gudang', $transfer->id, $userId);
            }
        }

        if ($transfer->to_entity === 'jihans') {
            $this->createJihansRetailStockIn($transfer);
        } else {
            $this->createHendhysStockIn($transfer);
        }
    }

    // ── Jihans Retail ─────────────────────────────────────────────────────────

    public function creditJihansRetail(int $productId, int $unitId, float $qty, string $source, ?int $refId, ?int $userId): void
    {
        $stock = JihansRetailStock::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
        );

        $before = (int) $stock->quantity;
        $after  = $before + $qty;
        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        $this->recordJihansRetailMovement($productId, 'in', $source, $refId, $qty, $before, $after, $userId);
    }

    public function debitJihansRetail(int $productId, float $qty, string $source, ?int $refId, ?int $userId): void
    {
        $stock = JihansRetailStock::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0, 'unit_id' => \App\Models\Product::find($productId)?->unit_id ?? 1, 'last_updated' => now()]
        );

        $before = (int) $stock->quantity;
        $after  = max(0, $before - $qty);
        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        $this->recordJihansRetailMovement($productId, 'out', $source, $refId, $qty, $before, $after, $userId);
    }

    public function recordJihansRetailMovement(int $productId, string $type, string $source, ?int $refId, float $qty, float $before, float $after, ?int $userId): void
    {
        DB::table('jihans_retail_stock_movements')->insert([
            'product_id'      => $productId,
            'type'            => $type,
            'source'          => $source,
            'reference_id'    => $refId,
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    private function createJihansRetailStockIn(TransferOut $transfer): void
    {
        $stockIn = JihansRetailStockIn::create([
            'stock_in_number' => $this->numbers->generateYearly('JHS-STI', 'jihans_retail_stock_in', 'stock_in_number'),
            'transfer_out_id' => $transfer->id,
            'date'            => $transfer->date,
            'notes'           => $transfer->notes,
            'created_by'      => $transfer->created_by,
            'created_at'      => now(),
        ]);

        foreach ($transfer->details as $detail) {
            $qty = (float) ($detail->received_quantity ?? $detail->quantity);
            if ($qty <= 0) continue;

            JihansRetailStockInDetail::create([
                'stock_in_id' => $stockIn->id,
                'product_id'  => $detail->product_id,
                'quantity'    => $qty,
                'unit_id'     => $detail->unit_id,
                'hpp_price'   => $detail->hpp_price,
            ]);
        }
    }

    // ── Hendhys ───────────────────────────────────────────────────────────────

    public function creditHendhys(int $productId, int $unitId, float $qty, ?int $branchId, string $source, ?int $refId, ?int $userId): void
    {
        $isPusat = true;
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && $branch->type !== 'pusat') {
                $isPusat = false;
            }
        }

        if (!$isPusat) {
            $stock = HendhysStockBranch::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['quantity' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
            );
        } else {
            $stock = HendhysStockPusat::firstOrCreate(
                ['product_id' => $productId],
                ['quantity' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
            );
        }

        $before = (int) $stock->quantity;
        $after  = $before + $qty;
        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        $this->recordHendhysMovement($branchId, $productId, 'in', $source, $refId, $qty, $before, $after, $userId);
    }

    public function creditHendhysReturn(int $productId, int $unitId, float $qty, ?int $branchId, string $source, ?int $refId, ?int $userId): void
    {
        $isPusat = true;
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && $branch->type !== 'pusat') {
                $isPusat = false;
            }
        }

        if (!$isPusat) {
            $stock = HendhysStockBranch::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['quantity' => 0, 'quantity_return' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
            );
        } else {
            $stock = HendhysStockPusat::firstOrCreate(
                ['product_id' => $productId],
                ['quantity' => 0, 'quantity_return' => 0, 'unit_id' => $unitId, 'last_updated' => now()]
            );
        }

        $before = (float) $stock->quantity_return;
        $after  = $before + $qty;
        $stock->update(['quantity_return' => $after, 'last_updated' => now()]);

        // Catat di pergerakan stok sebagai return masuk
        $this->recordHendhysMovement($branchId, $productId, 'in', $source, $refId, $qty, $before, $after, $userId);
    }

    public function debitHendhys(int $productId, float $qty, ?int $branchId, string $source, ?int $refId, ?int $userId): void
    {
        $isPusat = true;
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && $branch->type !== 'pusat') {
                $isPusat = false;
            }
        }

        if (!$isPusat) {
            $stock = HendhysStockBranch::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['quantity' => 0, 'unit_id' => \App\Models\Product::find($productId)?->unit_id ?? 1, 'last_updated' => now()]
            );
        } else {
            $stock = HendhysStockPusat::firstOrCreate(
                ['product_id' => $productId],
                ['quantity' => 0, 'unit_id' => \App\Models\Product::find($productId)?->unit_id ?? 1, 'last_updated' => now()]
            );
        }

        $before = (int) $stock->quantity;
        $after  = max(0, $before - $qty);
        $stock->update(['quantity' => $after, 'last_updated' => now()]);

        $this->recordHendhysMovement($branchId, $productId, 'out', $source, $refId, $qty, $before, $after, $userId);
    }

    public function recordHendhysMovement(?int $branchId, int $productId, string $type, string $source, ?int $refId, float $qty, float $before, float $after, ?int $userId): void
    {
        DB::table('hendhys_stock_movements')->insert([
            'branch_id'       => $branchId,
            'product_id'      => $productId,
            'type'            => $type,
            'source'          => $source,
            'reference_id'    => $refId,
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'created_by'      => $userId,
            'created_at'      => now(),
        ]);
    }

    private function createHendhysStockIn(TransferOut $transfer): void
    {
        HendhysStockIn::create([
            'stock_in_number' => $this->numbers->generateYearly('HND-STI', 'hendhys_stock_in', 'stock_in_number'),
            'transfer_out_id' => $transfer->id,
            'branch_id'       => $transfer->branch_id,
            'date'            => $transfer->date,
            'notes'           => $transfer->notes,
            'created_by'      => $transfer->created_by,
            'created_at'      => now(),
        ]);
    }

    // ── GRN: update HPP on master_products ───────────────────────────────────

    public function updateProductHpp(int $productId, float $hppPrice): void
    {
        \App\Models\Product::where('id', $productId)->update(['hpp' => $hppPrice]);
    }

    // ── Jihans Gudang stock listing (read model) ─────────────────────────────

    /**
     * Paginated warehouse stock: every active INV product left-joined with its
     * current Jihans Gudang balance. Eager-loads unit + category to avoid N+1.
     */
    public function paginateJihansGudangStock(?string $search = null, bool $lowStockOnly = false, int $perPage = 20): LengthAwarePaginator
    {
        return Product::query()
            ->with(['unit', 'category'])
            ->visibleInGudang()
            ->leftJoin('jihans_gudang_stock', 'master_products.id', '=', 'jihans_gudang_stock.product_id')
            ->select('master_products.*', 'jihans_gudang_stock.quantity as current_stock')
            ->selectRaw("
                (SELECT COALESCE(SUM(grd.received_quantity), 0)
                 FROM gudang_return_details grd
                 JOIN gudang_returns gr ON grd.return_id = gr.id
                 WHERE grd.product_id = master_products.id
                   AND gr.status = 'received'
                   AND grd.condition = 'Rusak (Defect)') as returned_defect_stock
            ")
            ->selectRaw("
                (SELECT COALESCE(SUM(grd.received_quantity), 0)
                 FROM gudang_return_details grd
                 JOIN gudang_returns gr ON grd.return_id = gr.id
                 WHERE grd.product_id = master_products.id
                   AND gr.status = 'received'
                   AND grd.condition = 'Kadaluwarsa') as returned_expired_stock
            ")
            ->where('master_products.status', 'active')
            ->where('master_products.product_type', 'INV')
            ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('master_products.name', 'like', "%{$s}%")
                ->orWhere('master_products.code', 'like', "%{$s}%")))
            ->when($lowStockOnly, fn ($q) => $q
                ->whereRaw('COALESCE(jihans_gudang_stock.quantity, 0) < master_products.stock_min')
                ->where('master_products.stock_min', '>', 0))
            ->when($lowStockOnly, function ($q) {
                // qty > 0 tapi menipis dulu, baru qty = 0 paling bawah
                $q->orderBy(DB::raw("CASE WHEN COALESCE(jihans_gudang_stock.quantity, 0) = 0 THEN 1 ELSE 0 END"), 'asc')
                  ->orderBy('jihans_gudang_stock.quantity', 'desc');
            })
            ->orderBy(DB::raw("CASE WHEN COALESCE(jihans_gudang_stock.quantity, 0) > 0 THEN 0 ELSE 1 END"), 'asc')
            ->orderBy('master_products.name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
