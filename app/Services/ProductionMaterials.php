<?php

namespace App\Services;

use App\Models\{Branch, Product, User};
use App\Services\Izzi\Quantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionMaterials
{
    public const KINDS = ['warehouse_transfer' => 'Gudang → Hendhys Produksi', 'production_usage' => 'Pemakaian di Hendhys Produksi', 'branch_usage' => 'Pemakaian stok cabang untuk produksi'];
    private const SOURCE = 'production_material';

    public function __construct(private ProductionLots $lots, private ActivityLogService $logger) {}

    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin') && in_array($user->entity, ['hendhys', 'all'], true)
            && !$user->hasAnyRole(['owner', 'kasir_hendhys', 'admin_hendhys', 'admin_jihans', 'kasir_jihans', 'admin_izzi', 'kasir_izzi']);
    }

    public function authorize(User $user): void
    {
        $isKasir = $user->hasRole('kasir_hendhys') && $user->entity === 'hendhys'
            && !$user->hasAnyRole(['owner', 'super_admin', 'admin_hendhys', 'admin_jihans', 'kasir_jihans', 'admin_izzi', 'kasir_izzi']);
        abort_unless($isKasir || $this->isSuperAdmin($user),
            403, 'Pemakaian stok untuk produksi hanya dapat dicatat Kasir Hendhys atau Super Admin.');
    }

    public function branches(User $user)
    {
        $this->authorize($user);
        $query = Branch::whereIn('entity', ['gudang', 'hendhys', 'jihans'])->where('is_active', true)->whereIn('type', ['pusat', 'cabang']);
        if ($user->entity !== 'all') $query->where('entity', $user->entity);
        if (!$this->isSuperAdmin($user)) {
            $query->whereKey(session('active_branch_id') ?: $user->branch_id);
        }
        return $query->orderByRaw("CASE WHEN type = 'pusat' THEN 0 ELSE 1 END")->orderBy('name')->get();
    }

    public function location(User $user, ?int $branchId = null): array
    {
        $branches = $this->branches($user);
        $branch = $this->isSuperAdmin($user)
            ? ($branchId !== null ? $branches->firstWhere('id', $branchId) : $branches->first())
            : $branches->first();
        if ($this->isSuperAdmin($user) && $branchId !== null && !$branch) {
            throw ValidationException::withMessages(['branch_id' => 'Pilih cabang atau gudang aktif yang tersedia terlebih dahulu.']);
        }
        abort_unless($branch && in_array($branch->type, ['pusat', 'cabang'], true), 403, 'Pilih cabang atau gudang aktif yang tersedia terlebih dahulu.');
        return ['branch_id' => $branch->id, 'branch_name' => $branch->name,
            'stock_location' => match ($branch->entity) {
                'gudang' => 'jihans_gudang_stock',
                'jihans' => 'jihans_retail_stock',
                'izzi' => 'izzi_stock',
                'hendhys' => $branch->type === 'pusat' ? 'hendhys_stock_pusat' : 'hendhys_stock_branch',
            }];
    }

    public function assertTransaction(object $tx, ?array $location = null, ?User $user = null): void
    {
        $allowedBranch = $user && $this->isSuperAdmin($user)
            ? $tx->branch_id !== null && $this->branches($user)->contains('id', $tx->branch_id)
            : $location && (int) $tx->branch_id === (int) $location['branch_id'];
        abort_unless($tx->kind === 'branch_usage' && $allowedBranch, 403,
            'Riwayat ini bukan pemakaian stok cabang yang sedang aktif.');
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['items' => $message.' Tidak ada saldo stok yang diubah.']);
    }

    public function hasBranchStock(array $location): bool
    {
        return in_array($location['stock_location'], ['hendhys_stock_branch', 'izzi_stock'], true);
    }

    public function lotEntity(array $location): ?string
    {
        return match ($location['stock_location']) {
            'hendhys_stock_pusat' => 'hendhys', 'jihans_gudang_stock' => 'jihans', default => null,
        };
    }

    public function quantityScale(array $location): int
    {
        return $location['stock_location'] === 'izzi_stock' ? Quantity::SCALE : 1;
    }

    public function displayQuantity(int|string $quantity, int $scale): int|string
    {
        return $scale === Quantity::SCALE ? Quantity::display($quantity) : (int) $quantity;
    }

    public function serializeItem(object $item): array
    {
        $row = (array) $item;
        foreach (['quantity', 'source_before', 'source_after'] as $key) {
            $row[$key] = $this->displayQuantity($item->$key, (int) $item->quantity_scale);
        }
        return $row;
    }

    public function products(array $location)
    {
        $query = Product::where('status', 'active');
        return match ($location['stock_location']) {
            'jihans_gudang_stock' => $query->visibleInGudang(),
            'jihans_retail_stock' => $query->visibleInJihans(),
            'hendhys_stock_pusat', 'hendhys_stock_branch' => $query->visibleInHendhys(),
            // Izzi's catalogue shares all master products; the stock row determines availability.
            'izzi_stock' => $query,
        };
    }

    private function stock(array $location, int $productId, int $unitId): object
    {
        $stock = DB::table($location['stock_location'])->where('product_id', $productId)
            ->when($this->hasBranchStock($location), fn ($q) => $q->where('branch_id', $location['branch_id']))
            ->lockForUpdate()->first();
        if (!$stock || (int) $stock->unit_id !== $unitId) $this->invalid('Kartu stok atau satuan produk di cabang aktif belum sesuai. Periksa master produk dan kartu stok.');
        if (($entity = $this->lotEntity($location)) && DB::table('production_lots')
            ->where('entity', $entity)->where('product_id', $productId)->where('remaining', '>', 0)->where('unit_id', '!=', $unitId)->exists()) {
            $this->invalid('Satuan stok produksi berbeda dari kartu stok.');
        }
        return $stock;
    }

    private function balance(array $location, object $stock, int $after): void
    {
        if ($after < 0 || $after > ($this->quantityScale($location) === Quantity::SCALE ? Quantity::MAX : 4294967295)) $this->invalid('Saldo stok tidak cukup atau melebihi batas.');
        DB::table($location['stock_location'])->where('id', $stock->id)->update(['quantity' => $after, 'last_updated' => now()]);
    }

    private function movement(array $location, object $stock, int $after, object $tx, User $user, string $event): array
    {
        $before = (int) $stock->quantity;
        $table = match ($location['stock_location']) {
            'jihans_gudang_stock' => 'jihans_gudang_stock_movements',
            'jihans_retail_stock' => 'jihans_retail_stock_movements',
            'izzi_stock' => 'izzi_stock_movements',
            default => 'hendhys_stock_movements',
        };
        $extra = [];
        if ($this->hasBranchStock($location)) $extra['branch_id'] = $location['branch_id'];
        if ($location['stock_location'] === 'izzi_stock') $extra += ['unit_id' => $stock->unit_id, 'actor_name' => $user->name];
        DB::table($table)->insert([
            ...$extra,
            'product_id' => $stock->product_id, 'type' => $after < $before ? 'out' : 'in', 'source' => 'adjustment',
            'reference_id' => null, 'production_material_id' => $tx->id,
            'quantity' => abs($after - $before), 'quantity_before' => $before, 'quantity_after' => $after,
            'notes' => $event.' '.$tx->number.' · Pemakaian untuk produksi di '.$tx->branch_name.' · '.$tx->reason,
            'created_by' => $user->id, 'created_at' => now(),
        ]);
        return ['location' => $tx->branch_name, 'branch_id' => $location['branch_id'],
            'product_id' => $stock->product_id, 'before' => $this->displayQuantity($before, $this->quantityScale($location)),
            'after' => $this->displayQuantity($after, $this->quantityScale($location))];
    }

    public function record(array $data, User $user): int
    {
        $location = $this->isSuperAdmin($user)
            ? $this->location($user, (int) ($data['branch_id'] ?? 0))
            : $this->location($user);
        if ((int) ($data['branch_id'] ?? 0) !== (int) $location['branch_id']) {
            throw ValidationException::withMessages(['branch_id' => 'Cabang aktif berubah. Buka ulang form sebelum mencatat pemakaian.']);
        }
        if (!empty($data['kind']) || !empty($data['production_id'])) $this->invalid('Gunakan form pemakaian stok cabang, tanpa pengiriman atau nomor produksi tujuan.');
        $items = collect($data['items'])->sortBy('product_id')->values()->all();
        $payload = $data; unset($payload['request_key'], $payload['confirmed']);
        $payload['items'] = $items; $payload['actor'] = $user->id; $payload['stock_location'] = $location['stock_location'];
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $items, $user, $hash, $location) {
            DB::table('production_material_transactions')->insertOrIgnore([
                'request_key' => $data['request_key'], 'payload_hash' => $hash, 'kind' => 'branch_usage', 'status' => 'processing',
                ...$location, 'date' => $data['date'], 'occurred_at' => Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta'),
                'sender_name' => $user->name, 'receiver_name' => $data['receiver_name'], 'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null, 'evidence_reference' => $data['evidence_reference'] ?? null,
                'created_by' => $user->id, 'created_by_name' => $user->name, 'created_at' => now(),
            ]);
            $tx = DB::table('production_material_transactions')->where('request_key', $data['request_key'])->lockForUpdate()->first();
            if ($tx->payload_hash !== $hash) $this->invalid('Permintaan sudah digunakan untuk data berbeda. Muat ulang halaman.');
            $this->assertTransaction($tx, $location);
            if ($tx->status !== 'processing') return (int) $tx->id;
            DB::table('production_material_sequences')->insertOrIgnore(['date' => $data['date'], 'last_number' => 0]);
            $seq = DB::table('production_material_sequences')->where('date', $data['date'])->lockForUpdate()->first();
            $next = (int) $seq->last_number + 1;
            DB::table('production_material_sequences')->where('date', $data['date'])->update(['last_number' => $next]);
            $tx->number = 'BPR-'.str_replace('-', '', $data['date']).'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            DB::table('production_material_transactions')->where('id', $tx->id)->update(['number' => $tx->number]);
            $changes = [];
            foreach ($items as $input) {
                $p = $this->products($location)->with('unit')->find($input['product_id']);
                if (!$p || !$p->unit) $this->invalid('Produk atau satuan master tidak tersedia untuk lokasi yang dipilih.');
                $source = $this->stock($location, $p->id, $p->unit_id);
                $scale = $this->quantityScale($location);
                $parsed = Quantity::parse($input['quantity'], 'items');
                if ($scale === 1 && $parsed % Quantity::SCALE !== 0) $this->invalid('Jumlah stok Gudang, Jihans, dan Hendhys harus bulat sesuai satuan master.');
                $qty = $scale === Quantity::SCALE ? $parsed : intdiv($parsed, Quantity::SCALE);
                $before = (int) $source->quantity;
                if ($qty < 1 || $qty > $before) $this->invalid('Stok '.$p->name.' tidak cukup. Tersedia '.$this->displayQuantity($before, $scale).' '.$p->unit->abbreviation.'.');
                $itemId = DB::table('production_material_items')->insertGetId([
                    'transaction_id' => $tx->id, 'product_id' => $p->id, 'unit_id' => $p->unit_id,
                    'product_name' => $p->name, 'product_code' => $p->code, 'unit_name' => $p->unit->abbreviation,
                    'quantity' => $qty, 'quantity_scale' => $scale, 'source_before' => $before, 'source_after' => $before - $qty,
                    'notes' => $input['notes'] ?? null,
                ]);
                if ($entity = $this->lotEntity($location)) {
                    $this->lots->debit($entity, $p->id, $p->unit_id, $before, $qty, self::SOURCE, $tx->id, $user->id, null, $itemId);
                }
                $this->balance($location, $source, $before - $qty);
                $changes[] = $this->movement($location, $source, $before - $qty, $tx, $user, 'Pencatatan');
            }
            DB::table('production_material_transactions')->where('id', $tx->id)->update(['status' => 'completed']);
            $this->event($tx, $user, 'recorded', $data['reason'], $changes);
            return (int) $tx->id;
        }, 3);
    }

    private function restoreLots(object $stock, object $item, object $tx, string $entity): void
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
        $userLocation = $this->isSuperAdmin($user) ? null : $this->location($user);
        if ($userLocation === null) $this->authorize($user);
        DB::transaction(function () use ($id, $data, $user, $userLocation) {
            $tx = DB::table('production_material_transactions')->where('id', $id)->lockForUpdate()->first(); abort_unless($tx, 404);
            $this->assertTransaction($tx, $userLocation, $user);
            $location = $userLocation ?? $this->location($user, (int) $tx->branch_id);
            if ($tx->stock_location !== $location['stock_location']) $this->invalid('Jenis lokasi stok cabang berubah sejak pencatatan. Periksa kartu stok asal sebelum pembatalan.');
            if ($tx->status === 'cancelled') {
                if ($tx->cancel_reason === $data['reason'] && (int) $tx->cancelled_by === $user->id) return;
                $this->invalid('Transaksi sudah dibatalkan; stok tidak dikembalikan dua kali.');
            }
            if ($tx->status !== 'completed') $this->invalid('Status transaksi belum dapat dibatalkan.');
            $changes = [];
            foreach (DB::table('production_material_items')->where('transaction_id', $id)->orderBy('product_id')->get() as $item) {
                $source = $this->stock($location, $item->product_id, $item->unit_id);
                if ($entity = $this->lotEntity($location)) $this->restoreLots($source, $item, $tx, $entity);
                $this->balance($location, $source, (int) $source->quantity + $item->quantity);
                $changes[] = $this->movement($location, $source, (int) $source->quantity + $item->quantity, $tx, $user, 'Pembatalan');
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
