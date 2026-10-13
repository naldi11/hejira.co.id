<?php

namespace App\Services;

use App\Models\HendhysProduction;
use App\Models\JihansProductionSession;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionLots
{
    private function lots(string $entity, int $productId)
    {
        return DB::table('production_lots')->where('entity', $entity)->where('product_id', $productId);
    }

    // Called only while StockService holds the physical stock row lock in a transaction.
    // Old balances are deliberately not assigned to historical production numbers.
    private function initialize(string $entity, int $productId, int $unitId, int $before): bool
    {
        $lots = $this->lots($entity, $productId);
        if (!$lots->exists()) {
            if (Product::withTrashed()->find($productId)?->source_type !== 'produced') return false;
            if ($before > 0) $this->unknown($entity, $productId, $unitId, $before);
        }
        if ((int) $this->lots($entity, $productId)->sum('remaining') !== $before) {
            throw ValidationException::withMessages(['items' => 'Saldo nomor produksi tidak cocok dengan stok fisik. Periksa kartu stok sebelum melanjutkan.']);
        }
        return true;
    }

    private function unknown(string $entity, int $productId, int $unitId, int $qty): void
    {
        if ($qty <= 0) return;
        DB::table('production_lots')->insert([
            'entity' => $entity, 'product_id' => $productId, 'unit_id' => $unitId,
            'quantity' => $qty, 'remaining' => $qty, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function credit(string $entity, int $productId, int $unitId, int $before, int $qty, string $source, ?int $refId): void
    {
        if ($source === 'production') {
            $model = $entity === 'hendhys' ? HendhysProduction::class : JihansProductionSession::class;
            $production = $model::findOrFail($refId);
            if ($production->type !== 'aktual') throw ValidationException::withMessages(['items' => 'Prediksi belum menjadi stok produksi.']);
            // Even if a product label was changed, existing production is still tracked.
            if (!$this->lots($entity, $productId)->exists() && $before > 0) $this->unknown($entity, $productId, $unitId, $before);
            if ((int) $this->lots($entity, $productId)->sum('remaining') !== $before) throw ValidationException::withMessages(['items' => 'Saldo nomor produksi tidak cocok dengan stok fisik.']);
            $lot = $this->lots($entity, $productId)->where('production_id', $refId)->lockForUpdate()->first();
            if ($lot) {
                DB::table('production_lots')->where('id', $lot->id)->update(['quantity' => $lot->quantity + $qty, 'remaining' => $lot->remaining + $qty, 'updated_at' => now()]);
            } else {
                DB::table('production_lots')->insert([
                    'entity' => $entity, 'production_id' => $refId, 'product_id' => $productId, 'unit_id' => $unitId,
                    'production_date' => $production->date->format('Y-m-d'),
                    'production_number' => $production->production_number ?? $production->session_number,
                    'quantity' => $qty, 'remaining' => $qty, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return;
        }
        if (!$this->initialize($entity, $productId, $unitId, $before)) return;
        if (in_array($source, ['pos_void', 'transfer_shortfall'], true)) {
            $restoreSource = $source === 'pos_void' ? 'pos_sale' : ($entity === 'hendhys' ? 'transfer_to_branch' : 'transfer_out');
            $outflows = DB::table('production_lot_outflows as o')->join('production_lots as l', 'l.id', '=', 'o.lot_id')
                ->where('l.entity', $entity)->where('l.product_id', $productId)
                ->where('o.source', $restoreSource)->where('o.reference_id', $refId)
                ->whereColumn('o.restored_quantity', '<', 'o.quantity')->select('o.*')->orderBy('o.id')->lockForUpdate()->get();
            // A mixed shipment has no batch-level receiving quantities. Do not guess which lot returned.
            if ($source === 'transfer_shortfall' && $outflows->pluck('lot_id')->unique()->count() > 1) $outflows = collect();
            foreach ($outflows as $outflow) {
                $restore = min($qty, $outflow->quantity - $outflow->restored_quantity);
                DB::table('production_lots')->where('id', $outflow->lot_id)->increment('remaining', $restore, ['updated_at' => now()]);
                DB::table('production_lot_outflows')->where('id', $outflow->id)->increment('restored_quantity', $restore);
                $qty -= $restore;
                if ($qty === 0) break;
            }
        }
        // Returns/adjustments with no provenance stay separate from known production.
        $this->unknown($entity, $productId, $unitId, $qty);
    }

    public function debit(string $entity, int $productId, int $unitId, int $before, int $qty, string $source, ?int $refId, ?int $userId, ?int $lotId = null, ?int $detailId = null): void
    {
        if (!$this->initialize($entity, $productId, $unitId, $before)) {
            if ($lotId) throw ValidationException::withMessages(['items' => 'Nomor produksi tidak berlaku untuk produk ini.']);
            return;
        }
        $lots = $this->lots($entity, $productId)->where('remaining', '>', 0)
            ->when($lotId, fn ($q) => $q->where('id', $lotId))
            // Unknown opening stock predates tracking; consume it separately first in FIFO.
            ->orderByRaw('CASE WHEN production_date IS NULL THEN 0 ELSE 1 END')
            ->orderBy('production_date')->orderBy('id')->lockForUpdate()->get();
        if ($lots->sum('remaining') < $qty || ($lotId && (int) $lots->first()?->unit_id !== $unitId)) {
            throw ValidationException::withMessages(['items' => 'Stok nomor produksi yang dipilih tidak cukup atau tidak sesuai produk/satuan. Muat ulang dan pilih sumber stok yang tersedia.']);
        }
        foreach ($lots as $lot) {
            $take = min($qty, (int) $lot->remaining);
            DB::table('production_lots')->where('id', $lot->id)->update(['remaining' => $lot->remaining - $take, 'updated_at' => now()]);
            DB::table('production_lot_outflows')->insert([
                'lot_id' => $lot->id, 'source' => $source, 'reference_id' => $refId, 'detail_id' => $detailId,
                'quantity' => $take, 'created_by' => $userId, 'created_at' => now(),
            ]);
            $qty -= $take;
            if ($qty === 0) break;
        }
    }

    public function choices(string $entity): array
    {
        return DB::table('production_lots')->where('entity', $entity)->where('remaining', '>', 0)
            ->orderByRaw('CASE WHEN production_date IS NULL THEN 0 ELSE 1 END')->orderBy('production_date')->orderBy('id')
            ->get(['id', 'product_id', 'unit_id', 'production_number', 'production_date', 'remaining'])->map(fn ($lot) => (array) $lot)->all();
    }

    public function dispatchSources(string $source, int $referenceId): array
    {
        return DB::table('production_lot_outflows as o')->join('production_lots as l', 'l.id', '=', 'o.lot_id')
            ->where('o.source', $source)->where('o.reference_id', $referenceId)->orderBy('o.id')
            ->get(['o.detail_id', 'o.quantity', 'l.production_number'])->groupBy('detail_id')
            ->map(fn ($items) => $items->map(fn ($item) => ['number' => $item->production_number, 'quantity' => (int) $item->quantity])->all())->all();
    }

    public function summary($production, string $entity): array
    {
        $lots = DB::table('production_lots')->where('entity', $entity)->where('production_id', $production->id)->get();
        $unassigned = DB::table('production_lots')->where('entity', $entity)->whereNull('production_id')->whereIn('product_id', $lots->pluck('product_id'))->select('product_id')->selectRaw('SUM(remaining) as remaining')->groupBy('product_id')->pluck('remaining', 'product_id');
        $outflows = DB::table('production_lot_outflows')->whereIn('lot_id', $lots->pluck('id'))->get()->groupBy('lot_id');
        $deadline = Carbon::parse($production->date->format('Y-m-d').' 17:00', 'Asia/Jakarta');
        return $lots->map(function ($lot) use ($production, $outflows, $unassigned, $entity, $deadline) {
            $flows = $outflows->get($lot->id, collect());
            $source = $entity === 'hendhys' ? 'transfer_to_branch' : 'transfer_out';
            $detail = $production->details->firstWhere('product_id', $lot->product_id);
            return [
                'id' => $lot->id, 'product_id' => $lot->product_id, 'product' => $detail?->product?->name ?? 'Produk tidak tersedia',
                'unit' => $detail?->product?->unit?->abbreviation ?? '-', 'produced' => (int) $lot->quantity,
                'distributed' => $flows->where('source', $source)->sum(fn ($flow) => $flow->quantity - $flow->restored_quantity),
                'returned' => $flows->where('source', $source)->sum('restored_quantity'),
                'written_off' => $flows->where('source', 'production_writeoff')->sum(fn ($flow) => $flow->quantity - $flow->restored_quantity),
                'writeoff_cancelled' => $flows->where('source', 'production_writeoff')->sum('restored_quantity'),
                'other_out' => $flows->whereNotIn('source', [$source, 'production_writeoff'])->sum(fn ($flow) => $flow->quantity - $flow->restored_quantity),
                'remaining' => (int) $lot->remaining, 'unassigned_quantity' => (int) ($unassigned[$lot->product_id] ?? 0),
                'overdue' => $entity === 'hendhys' && $lot->remaining > 0 && now('Asia/Jakarta')->gte($deadline),
                'deadline' => $deadline->format('d/m/Y H:i').' WIB',
            ];
        })->all();
    }
}
