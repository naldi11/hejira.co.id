<?php

namespace App\Services;

use App\Models\{HendhysProduction, HendhysStockPusat, JihansGudangStock, JihansProductionSession, Product, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductionWriteoffs
{
    public const REASONS = [
        'tidak_habis' => 'Tidak habis dalam satu hari', 'rusak' => 'Rusak / tidak layak',
        'kedaluwarsa' => 'Kedaluwarsa', 'lainnya' => 'Alasan lain',
    ];

    public function __construct(private ProductionLots $lots, private ActivityLogService $logger) {}

    public function available(array $filters = [])
    {
        $columns = ['l.id as lot_id', 'l.entity', 'l.product_id', 'l.production_id', 'l.production_number',
            'l.production_date', 'l.remaining', 'p.name as product_name', 'u.abbreviation as unit_name'];
        $known = DB::table('production_lots as l')->join('master_products as p', 'p.id', '=', 'l.product_id')
            ->join('master_units as u', 'u.id', '=', 'l.unit_id')->select($columns)
            ->where('l.remaining', '>', 0)->whereIn('l.entity', ['jihans', 'hendhys'])
            ->where(fn ($q) => $q->whereNull('l.production_date')->orWhere('l.production_date', '<=', now('Asia/Jakarta')->toDateString()));
        // Read legacy balances without assigning them to a historical production or changing stock.
        foreach (['jihans' => 'jihans_gudang_stock', 'hendhys' => 'hendhys_stock_pusat'] as $entity => $table) {
            $legacy = DB::table($table.' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
                ->join('master_units as u', 'u.id', '=', 's.unit_id')
                ->selectRaw("NULL as lot_id, ? as entity, s.product_id, NULL as production_id, NULL as production_number, NULL as production_date, s.quantity as remaining, p.name as product_name, u.abbreviation as unit_name", [$entity])
                ->where('p.source_type', 'produced')->where('s.quantity', '>', 0)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('production_lots as existing')
                    ->where('existing.entity', $entity)->whereColumn('existing.product_id', 's.product_id'));
            $known->unionAll($legacy);
        }
        return DB::query()->fromSub($known, 'stock')
            ->when(!empty($filters['entity']), fn ($q) => $q->where('entity', $filters['entity']))
            ->when(!empty($filters['search']), fn ($q) => $q->where(fn ($w) => $w
                ->where('product_name', 'like', '%'.$filters['search'].'%')->orWhere('production_number', 'like', '%'.$filters['search'].'%')))
            ->orderBy('production_date')->orderBy('entity')->orderBy('product_id')->orderBy('lot_id');
    }

    public function history(array $filters = [])
    {
        return DB::table('production_writeoffs as w')
            ->leftJoin('production_writeoff_reversals as r', 'r.writeoff_id', '=', 'w.id')
            ->select('w.*', 'r.id as reversal_id', 'r.notes as reversal_notes', 'r.owner_name as reversal_owner',
                'r.created_at as reversed_at', 'r.remaining_before as reversal_remaining_before', 'r.remaining_after as reversal_remaining_after')
            ->when(!empty($filters['entity']), fn ($q) => $q->where('w.entity', $filters['entity']))
            ->when(isset($filters['production_id']), fn ($q) => $q->where('w.production_id', $filters['production_id']))
            ->when(!empty($filters['search']), fn ($q) => $q->where(fn ($w) => $w
                ->where('w.product_name', 'like', '%'.$filters['search'].'%')->orWhere('w.production_number', 'like', '%'.$filters['search'].'%')))
            ->orderByDesc('w.id');
    }

    public function create(array $data, User $owner): int
    {
        abort_unless($owner->hasRole('owner'), 403);
        return DB::transaction(function () use ($data, $owner) {
            $entity = $data['entity'];
            $model = $entity === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
            // Same lock order as StockService: physical balance first, then production lots.
            $stock = $model::where('product_id', $data['product_id'])->lockForUpdate()->first();
            $existing = DB::table('production_writeoffs')->where('request_key', $data['request_key'])->first();
            if ($existing) {
                $same = $existing->entity === $entity && (int) $existing->product_id === (int) $data['product_id']
                    && (int) $existing->created_by === $owner->id && (int) $existing->quantity === (int) $data['quantity']
                    && (int) $existing->remaining_before === (int) $data['expected_remaining']
                    && $existing->reason === $data['reason'] && $existing->notes === trim($data['notes'])
                    && (empty($data['lot_id']) ? $existing->production_id === null : (int) $existing->lot_id === (int) $data['lot_id']);
                if (!$same) $this->invalid('Permintaan sudah digunakan untuk penghapusan lain. Muat ulang halaman.');
                return (int) $existing->id;
            }
            if (!$stock || $stock->quantity <= 0) $this->invalid('Stok sudah kosong. Muat ulang halaman.');
            $before = (int) $stock->quantity;
            $product = Product::withTrashed()->findOrFail($data['product_id']);
            $lotQuery = DB::table('production_lots')->where('entity', $entity)->where('product_id', $product->id);
            if (empty($data['lot_id'])) {
                if ($product->source_type !== 'produced' || (clone $lotQuery)->exists()) {
                    $this->invalid('Sumber stok berubah atau bukan hasil produksi. Muat ulang dan pilih nomor produksi yang tepat.');
                }
                $this->lots->credit($entity, $product->id, $stock->unit_id, $before, 0, 'adjustment', null);
                $lot = $lotQuery->whereNull('production_id')->lockForUpdate()->first();
            } else {
                $lot = $lotQuery->where('id', $data['lot_id'])->lockForUpdate()->first();
            }
            if (!$lot || (int) $lot->unit_id !== (int) $stock->unit_id) $this->invalid('Nomor produksi tidak sesuai produk, unit usaha, atau satuan stok.');
            if ($lot->production_id !== null) {
                $productionModel = $entity === 'hendhys' ? HendhysProduction::class : JihansProductionSession::class;
                $production = $productionModel::find($lot->production_id);
                if (!$production || $production->type !== 'aktual' || !$lot->production_date
                    || $lot->production_date > now('Asia/Jakarta')->toDateString()) {
                    $this->invalid('Hanya stok produksi aktual sampai hari ini yang dapat dihapus.');
                }
            }
            if ((int) $lot->remaining !== (int) $data['expected_remaining']) $this->invalid('Sisa stok telah berubah. Muat ulang dan periksa jumlah terbaru sebelum menghapus.');
            $qty = (int) $data['quantity'];
            if ($qty > (int) $lot->remaining || $qty > $before) $this->invalid('Jumlah penghapusan melebihi sisa stok yang tersedia.');
            $after = $before - $qty;
            $remaining = (int) $lot->remaining - $qty;
            $unitName = $stock->unit?->abbreviation ?? '-';
            $id = DB::table('production_writeoffs')->insertGetId([
                'batch_id' => $data['batch_id'] ?? null,
                'request_key' => $data['request_key'], 'entity' => $entity, 'lot_id' => $lot->id,
                'production_id' => $lot->production_id, 'production_number' => $lot->production_number, 'production_date' => $lot->production_date,
                'product_id' => $product->id, 'unit_id' => $stock->unit_id, 'product_name' => $product->name, 'unit_name' => $unitName,
                'quantity' => $qty, 'remaining_before' => $lot->remaining, 'remaining_after' => $remaining,
                'stock_before' => $before, 'stock_after' => $after, 'reason' => $data['reason'], 'notes' => trim($data['notes']),
                'created_by' => $owner->id, 'owner_name' => $owner->name, 'created_at' => now(),
            ]);
            $this->lots->debit($entity, $product->id, $stock->unit_id, $before, $qty, 'production_writeoff', $id, $owner->id, $lot->id);
            $stock->update(['quantity' => $after, 'last_updated' => now()]);
            // Keep existing movement enums compatible; the dedicated document and lot ledger identify writeoffs.
            $movementTable = $entity === 'hendhys' ? 'hendhys_stock_movements' : 'jihans_gudang_stock_movements';
            $movementId = DB::table($movementTable)->insertGetId([
                'product_id' => $product->id, 'type' => 'out', 'source' => 'adjustment', 'reference_id' => $id,
                'quantity' => $qty, 'quantity_before' => $before, 'quantity_after' => $after,
                'notes' => 'Penghapusan sisa produksi #'.$id.' ('.($lot->production_number ?? 'tanpa nomor produksi').'): '.self::REASONS[$data['reason']].'. '.trim($data['notes']),
                'created_by' => $owner->id, 'created_at' => now(),
            ]);
            DB::table('production_writeoffs')->where('id', $id)->update(['movement_id' => $movementId]);
            $this->logger->log('update', 'owner.production_writeoffs', 'Penghapusan sisa produksi #'.$id.': '.$product->name.' '.$qty.' '.$unitName, null,
                ['stock' => $before, 'remaining' => $lot->remaining], ['stock' => $after, 'remaining' => $remaining, 'reason' => $data['reason']]);
            // Resolve current stock alerts only when the actual outstanding balance is gone.
            if ($entity === 'hendhys') app(ProductionStockMonitor::class)->overview();
            return $id;
        }, 3);
    }

    public function createMany(array $data, User $owner): array
    {
        abort_unless($owner->hasRole('owner'), 403);
        // Sort by physical stock identity to prevent opposite lock orders between bulk requests.
        $items = collect($data['items'])->map(fn ($item) => [
            'entity' => $item['entity'], 'product_id' => (int) $item['product_id'],
            'lot_id' => empty($item['lot_id']) ? null : (int) $item['lot_id'],
            'expected_remaining' => (int) $item['expected_remaining'], 'quantity' => (int) $item['quantity'],
        ])->sortBy(fn ($item) => sprintf('%s-%020d-%020d', $item['entity'], $item['product_id'], $item['lot_id'] ?? 0))->values();
        $identities = $items->map(fn ($item) => $item['entity'].':'.$item['product_id'].':'.($item['lot_id'] ?? 'legacy'));
        if ($identities->unique()->count() !== $items->count()) {
            throw ValidationException::withMessages(['items' => 'Sumber stok yang sama dipilih lebih dari sekali.']);
        }
        $hash = hash('sha256', json_encode(['items' => $items->all(), 'reason' => $data['reason'], 'notes' => trim($data['notes'])], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $owner, $items, $hash) {
            foreach ($items->unique(fn ($item) => $item['entity'].':'.$item['product_id']) as $item) {
                $model = $item['entity'] === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
                $model::where('product_id', $item['product_id'])->lockForUpdate()->first();
            }
            $existing = DB::table('production_writeoff_batches')->where('request_key', $data['request_key'])->first();
            if ($existing) {
                if ($existing->payload_hash !== $hash || (int) $existing->created_by !== $owner->id) {
                    throw ValidationException::withMessages(['items' => 'Permintaan sudah digunakan untuk penghapusan lain. Muat ulang halaman.']);
                }
                return DB::table('production_writeoffs')->where('batch_id', $existing->id)->pluck('id')->all();
            }
            $batchId = DB::table('production_writeoff_batches')->insertGetId([
                'request_key' => $data['request_key'], 'payload_hash' => $hash, 'created_by' => $owner->id, 'created_at' => now(),
            ]);
            $ids = [];
            foreach ($items as $index => $item) {
                try {
                    $ids[] = $this->create($item + ['request_key' => (string) Str::uuid(), 'batch_id' => $batchId,
                        'reason' => $data['reason'], 'notes' => $data['notes']], $owner);
                } catch (ValidationException $error) {
                    throw ValidationException::withMessages(['items' => 'Pilihan '.($index + 1).' gagal: '.collect($error->errors())->flatten()->first().' Seluruh penghapusan dibatalkan; tidak ada stok yang berubah.']);
                }
            }
            return $ids;
        }, 3);
    }

    public function reverse(int $writeoffId, array $data, User $owner): int
    {
        abort_unless($owner->hasRole('owner'), 403);
        $original = DB::table('production_writeoffs')->find($writeoffId);
        abort_unless($original, 404);
        return DB::transaction(function () use ($original, $data, $owner) {
            $model = $original->entity === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
            // Lock the stock before the document/lot, matching dispatch and writeoff lock order.
            $stock = $model::where('product_id', $original->product_id)->lockForUpdate()->first();
            $writeoff = DB::table('production_writeoffs')->where('id', $original->id)->lockForUpdate()->first();
            $existing = DB::table('production_writeoff_reversals')->where('writeoff_id', $writeoff->id)->first();
            if ($existing) {
                if ($existing->request_key === $data['request_key'] && (int) $existing->created_by === $owner->id
                    && $existing->notes === trim($data['notes'])) return (int) $existing->id;
                throw ValidationException::withMessages(['notes' => 'Penghapusan ini sudah dibatalkan. Stok tidak ditambahkan lagi.']);
            }
            if (DB::table('production_writeoff_reversals')->where('request_key', $data['request_key'])->exists()) {
                throw ValidationException::withMessages(['notes' => 'Permintaan pembatalan sudah digunakan. Muat ulang halaman.']);
            }
            $lot = DB::table('production_lots')->where('id', $writeoff->lot_id)->lockForUpdate()->first();
            if (!$stock || !$lot || $lot->entity !== $writeoff->entity || (int) $lot->product_id !== (int) $writeoff->product_id
                || (int) $lot->unit_id !== (int) $writeoff->unit_id || (int) $stock->unit_id !== (int) $writeoff->unit_id
                || $lot->production_id !== $writeoff->production_id) {
                throw ValidationException::withMessages(['notes' => 'Sumber produksi atau satuan stok berubah. Periksa kartu stok sebelum membatalkan.']);
            }
            $before = (int) $stock->quantity;
            $balance = DB::table('production_lots')->where('entity', $writeoff->entity)->where('product_id', $writeoff->product_id)->sum('remaining');
            $outflow = DB::table('production_lot_outflows')->where('lot_id', $lot->id)->where('source', 'production_writeoff')
                ->where('reference_id', $writeoff->id)->lockForUpdate()->first();
            if ($before < 0 || (int) $balance !== $before || !$outflow || (int) $outflow->quantity !== (int) $writeoff->quantity
                || (int) $outflow->restored_quantity !== 0 || $lot->remaining + $writeoff->quantity > $lot->quantity) {
                throw ValidationException::withMessages(['notes' => 'Saldo produksi atau catatan penghapusan tidak cocok. Tidak ada stok yang diubah.']);
            }
            $qty = (int) $writeoff->quantity;
            $after = $before + $qty;
            $remaining = (int) $lot->remaining + $qty;
            $id = DB::table('production_writeoff_reversals')->insertGetId([
                'writeoff_id' => $writeoff->id, 'request_key' => $data['request_key'], 'quantity' => $qty,
                'remaining_before' => $lot->remaining, 'remaining_after' => $remaining, 'stock_before' => $before, 'stock_after' => $after,
                'notes' => trim($data['notes']), 'created_by' => $owner->id, 'owner_name' => $owner->name, 'created_at' => now(),
            ]);
            // Restore the exact original lot, including unidentified legacy stock. Never credit a new lot.
            DB::table('production_lots')->where('id', $lot->id)->update(['remaining' => $remaining, 'updated_at' => now()]);
            DB::table('production_lot_outflows')->where('id', $outflow->id)->update(['restored_quantity' => $qty]);
            $stock->update(['quantity' => $after, 'last_updated' => now()]);
            $movementTable = $writeoff->entity === 'hendhys' ? 'hendhys_stock_movements' : 'jihans_gudang_stock_movements';
            $movementId = DB::table($movementTable)->insertGetId([
                'product_id' => $writeoff->product_id, 'type' => 'in', 'source' => 'adjustment', 'reference_id' => $writeoff->id,
                'quantity' => $qty, 'quantity_before' => $before, 'quantity_after' => $after,
                'notes' => 'Pembatalan penghapusan #'.$writeoff->id.' ('.($writeoff->production_number ?? 'tanpa nomor produksi').'): '.trim($data['notes']),
                'created_by' => $owner->id, 'created_at' => now(),
            ]);
            DB::table('production_writeoff_reversals')->where('id', $id)->update(['movement_id' => $movementId]);
            $this->logger->log('update', 'owner.production_writeoffs', 'Pembatalan penghapusan #'.$writeoff->id.': stok dikembalikan '.$qty.' '.$writeoff->unit_name, null,
                ['stock' => $before, 'remaining' => $lot->remaining], ['stock' => $after, 'remaining' => $remaining, 'reversal_id' => $id]);
            if ($writeoff->entity === 'hendhys') app(ProductionStockMonitor::class)->overview();
            return $id;
        }, 3);
    }

    public static function historyRow(object $row): object
    {
        return (object) [...(array) $row, 'recorded_at' => self::time($row->created_at),
            'reversed_at_label' => $row->reversed_at ? self::time($row->reversed_at) : null];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['quantity' => $message]);
    }

    public static function time(string $timestamp): string
    {
        return Carbon::parse($timestamp)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB';
    }
}
