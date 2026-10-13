<?php

namespace App\Services;

use App\Models\{HendhysProduction, Product, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionMaterials
{
    public const KINDS = ['warehouse_transfer' => 'Gudang → Hendhys Produksi', 'production_usage' => 'Pemakaian di Hendhys Produksi'];
    private const SOURCE = 'production_material';

    public function __construct(private ProductionLots $lots, private ActivityLogService $logger) {}

    public function authorize(User $user): void
    {
        abort_unless($user->hasRole('super_admin') && !$user->hasRole('owner'), 403, 'Barang untuk produksi hanya dapat dikelola Super Admin.');
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['items' => $message.' Tidak ada saldo stok yang diubah.']);
    }

    private function stock(string $entity, int $productId, int $unitId, bool $create = false): object
    {
        $table = $entity === 'jihans' ? 'jihans_gudang_stock' : 'hendhys_stock_pusat';
        if ($create) DB::table($table)->insertOrIgnore(['product_id' => $productId, 'unit_id' => $unitId, 'quantity' => 0, 'last_updated' => now()]);
        $stock = DB::table($table)->where('product_id', $productId)->lockForUpdate()->first();
        $label = $entity === 'jihans' ? 'Gudang Utama' : 'Hendhys Produksi';
        if (!$stock || (int) $stock->unit_id !== $unitId) $this->invalid('Kartu stok atau satuan di '.$label.' belum sesuai. Periksa master produk dan kartu stok.');
        if (DB::table('production_lots')->where('entity', $entity)->where('product_id', $productId)->where('remaining', '>', 0)->where('unit_id', '!=', $unitId)->exists()) {
            $this->invalid('Satuan nomor produksi di '.$label.' berbeda dari kartu stok. Periksa sebelum melanjutkan.');
        }
        return $stock;
    }

    private function balance(string $entity, object $stock, int $after): void
    {
        if ($after < 0 || $after > 4294967295) $this->invalid('Saldo stok tidak cukup atau melebihi batas.');
        DB::table($entity === 'jihans' ? 'jihans_gudang_stock' : 'hendhys_stock_pusat')->where('id', $stock->id)->update(['quantity' => $after, 'last_updated' => now()]);
    }

    private function movement(string $entity, object $stock, int $after, object $tx, User $user, string $event): array
    {
        $before = (int) $stock->quantity;
        DB::table($entity === 'jihans' ? 'jihans_gudang_stock_movements' : 'hendhys_stock_movements')->insert([
            'product_id' => $stock->product_id, 'type' => $after < $before ? 'out' : 'in', 'source' => 'adjustment',
            // Dedicated reference prevents collisions with existing SO/writeoff document IDs.
            'reference_id' => null, 'production_material_id' => $tx->id,
            'quantity' => abs($after - $before), 'quantity_before' => $before, 'quantity_after' => $after,
            'notes' => $event.' '.$tx->number.' · '.self::KINDS[$tx->kind], 'created_by' => $user->id, 'created_at' => now(),
        ]);
        return ['location' => $entity === 'jihans' ? 'Gudang Utama' : 'Hendhys Produksi', 'product_id' => $stock->product_id, 'before' => $before, 'after' => $after];
    }

    public function record(array $data, User $user): int
    {
        $this->authorize($user);
        $items = collect($data['items'])->sortBy('product_id')->values()->all();
        $payload = $data; unset($payload['request_key'], $payload['confirmed']); $payload['items'] = $items; $payload['actor'] = $user->id;
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $items, $user, $hash) {
            DB::table('production_material_transactions')->insertOrIgnore([
                'request_key' => $data['request_key'], 'payload_hash' => $hash, 'kind' => $data['kind'], 'status' => 'processing',
                'date' => $data['date'], 'occurred_at' => Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta'),
                'sender_name' => $data['sender_name'], 'receiver_name' => $data['receiver_name'], 'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null, 'evidence_reference' => $data['evidence_reference'] ?? null,
                'created_by' => $user->id, 'created_by_name' => $user->name, 'created_at' => now(),
            ]);
            $tx = DB::table('production_material_transactions')->where('request_key', $data['request_key'])->lockForUpdate()->first();
            if ($tx->payload_hash !== $hash) $this->invalid('Permintaan sudah digunakan untuk data berbeda. Muat ulang halaman.');
            if ($tx->status !== 'processing') return (int) $tx->id;
            $production = !empty($data['production_id']) ? HendhysProduction::whereKey($data['production_id'])->lockForUpdate()->first() : null;
            if ($data['kind'] === 'production_usage' && !$production) $this->invalid('Pilih nomor produksi yang menggunakan barang ini.');
            DB::table('production_material_sequences')->insertOrIgnore(['date' => $data['date'], 'last_number' => 0]);
            $seq = DB::table('production_material_sequences')->where('date', $data['date'])->lockForUpdate()->first();
            $next = (int) $seq->last_number + 1;
            DB::table('production_material_sequences')->where('date', $data['date'])->update(['last_number' => $next]);
            $tx->number = 'BPR-'.str_replace('-', '', $data['date']).'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            DB::table('production_material_transactions')->where('id', $tx->id)->update(['number' => $tx->number,
                'production_id' => $production?->id, 'production_number' => $production?->production_number, 'production_type' => $production?->type]);
            $changes = [];
            foreach ($items as $input) {
                $p = Product::with('unit')->where('status', 'active')->findOrFail($input['product_id']);
                if (!$p->unit) $this->invalid('Satuan master '.$p->name.' belum tersedia.');
                $entity = $data['kind'] === 'warehouse_transfer' ? 'jihans' : 'hendhys';
                $source = $this->stock($entity, $p->id, $p->unit_id);
                $qty = (int) $input['quantity']; $before = (int) $source->quantity;
                if ($qty > $before) $this->invalid('Stok '.$p->name.' tidak cukup. Tersedia '.$before.' '.$p->unit->abbreviation.'.');
                $dest = $data['kind'] === 'warehouse_transfer' ? $this->stock('hendhys', $p->id, $p->unit_id, true) : null;
                $itemId = DB::table('production_material_items')->insertGetId([
                    'transaction_id' => $tx->id, 'product_id' => $p->id, 'unit_id' => $p->unit_id,
                    'product_name' => $p->name, 'product_code' => $p->code, 'unit_name' => $p->unit->abbreviation,
                    'quantity' => $qty, 'source_before' => $before, 'source_after' => $before - $qty,
                    'destination_before' => $dest?->quantity, 'destination_after' => $dest ? (int) $dest->quantity + $qty : null,
                    'notes' => $input['notes'] ?? null,
                ]);
                $this->lots->debit($entity, $p->id, $p->unit_id, $before, $qty, self::SOURCE, $tx->id, $user->id, $input['lot_id'] ?? null, $itemId);
                $this->balance($entity, $source, $before - $qty);
                $changes[] = $this->movement($entity, $source, $before - $qty, $tx, $user, 'Pencatatan');
                if ($dest) {
                    $this->lots->credit('hendhys', $p->id, $p->unit_id, (int) $dest->quantity, $qty, self::SOURCE, $tx->id);
                    // Credits with no Hendhys production provenance have their own unidentified lot.
                    $lot = DB::table('production_lots')->where('entity', 'hendhys')->where('product_id', $p->id)->orderByDesc('id')->first();
                    DB::table('production_material_items')->where('id', $itemId)->update(['destination_lot_id' => $lot?->id]);
                    $this->balance('hendhys', $dest, (int) $dest->quantity + $qty);
                    $changes[] = $this->movement('hendhys', $dest, (int) $dest->quantity + $qty, $tx, $user, 'Pencatatan');
                }
            }
            DB::table('production_material_transactions')->where('id', $tx->id)->update(['status' => 'completed']);
            $this->event($tx, $user, 'recorded', $data['reason'], $changes);
            return (int) $tx->id;
        }, 3);
    }

    private function restore(string $entity, object $stock, object $item, object $tx): void
    {
        $flows = DB::table('production_lot_outflows as o')->join('production_lots as l', 'l.id', '=', 'o.lot_id')
            ->where('l.entity', $entity)->where('l.product_id', $item->product_id)->where('o.source', self::SOURCE)
            ->where('o.reference_id', $tx->id)->where('o.detail_id', $item->id)->select('o.*', 'l.unit_id', 'l.remaining', 'l.quantity as lot_quantity')->lockForUpdate()->get();
        if ($flows->isEmpty()) {
            $this->lots->credit($entity, $item->product_id, $item->unit_id, (int) $stock->quantity, $item->quantity, 'production_material_reversal', $tx->id);
            return;
        }
        if ((int) $flows->sum('quantity') !== (int) $item->quantity || (int) DB::table('production_lots')->where('entity', $entity)->where('product_id', $item->product_id)->sum('remaining') !== (int) $stock->quantity) $this->invalid('Saldo nomor produksi tidak sesuai untuk pembatalan.');
        foreach ($flows as $flow) {
            if ((int) $flow->unit_id !== (int) $item->unit_id || (int) $flow->restored_quantity !== 0 || $flow->remaining + $flow->quantity > $flow->lot_quantity) $this->invalid('Rincian stok produksi asal berubah. Periksa sebelum pembatalan.');
            DB::table('production_lots')->where('id', $flow->lot_id)->increment('remaining', $flow->quantity, ['updated_at' => now()]);
            DB::table('production_lot_outflows')->where('id', $flow->id)->update(['restored_quantity' => $flow->quantity]);
        }
    }

    public function cancel(int $id, array $data, User $user): void
    {
        $this->authorize($user);
        DB::transaction(function () use ($id, $data, $user) {
            $tx = DB::table('production_material_transactions')->where('id', $id)->lockForUpdate()->first(); abort_unless($tx, 404);
            if ($tx->status === 'cancelled') {
                if ($tx->cancel_reason === $data['reason'] && (int) $tx->cancelled_by === $user->id) return;
                $this->invalid('Transaksi sudah dibatalkan; stok tidak dikembalikan dua kali.');
            }
            if ($tx->status !== 'completed') $this->invalid('Status transaksi belum dapat dibatalkan.');
            $changes = [];
            foreach (DB::table('production_material_items')->where('transaction_id', $id)->orderBy('product_id')->get() as $item) {
                $entity = $tx->kind === 'warehouse_transfer' ? 'jihans' : 'hendhys';
                $source = $this->stock($entity, $item->product_id, $item->unit_id);
                if ($tx->kind === 'warehouse_transfer') {
                    $dest = $this->stock('hendhys', $item->product_id, $item->unit_id);
                    if ((int) $dest->quantity < (int) $item->quantity) $this->invalid('Stok penerima tidak cukup untuk membalik pengiriman.');
                    $this->lots->debit('hendhys', $item->product_id, $item->unit_id, (int) $dest->quantity, $item->quantity, 'production_material_reversal', $id, $user->id, $item->destination_lot_id, $item->id);
                    $this->balance('hendhys', $dest, (int) $dest->quantity - $item->quantity);
                    $changes[] = $this->movement('hendhys', $dest, (int) $dest->quantity - $item->quantity, $tx, $user, 'Pembatalan');
                }
                $this->restore($entity, $source, $item, $tx);
                $this->balance($entity, $source, (int) $source->quantity + $item->quantity);
                $changes[] = $this->movement($entity, $source, (int) $source->quantity + $item->quantity, $tx, $user, 'Pembatalan');
            }
            DB::table('production_material_transactions')->where('id', $id)->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancelled_by_name' => $user->name, 'cancel_reason' => $data['reason']]);
            $this->event($tx, $user, 'cancelled', $data['reason'], $changes);
        }, 3);
    }

    private function event(object $tx, User $user, string $action, string $reason, array $changes): void
    {
        DB::table('production_material_events')->insert(['transaction_id' => $tx->id, 'action' => $action,
            'created_by' => $user->id, 'actor_name' => $user->name, 'reason' => $reason, 'stock_changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now()]);
        $this->logger->log($action === 'recorded' ? 'create' : 'update', 'production_materials', $tx->number.' · '.$action.': '.$reason, null, null, ['id' => $tx->id, 'stock_changes' => $changes]);
    }
}
