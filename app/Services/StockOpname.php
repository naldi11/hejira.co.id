<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpname
{
    public function __construct(private ProductionLots $lots, private ActivityLogService $logger) {}

    public function locations(): array
    {
        $locations = [[
            'key' => 'gudang', 'stock_key' => 'gudang', 'entity' => 'gudang', 'branch_id' => null,
            'name' => 'Gudang Utama / stok gudang dan produksi Jihan’s',
            'description' => 'Saldo gudang yang juga menyimpan hasil produksi Jihan’s. Terpisah dari stok penjualan Jihan’s.',
        ], [
            'key' => 'hendhys-pusat', 'stock_key' => 'hendhys-pusat', 'entity' => 'hendhys', 'branch_id' => null,
            'name' => 'Hendhys Produksi / pusat', 'description' => 'Stok siap distribusi di pusat produksi Hendhys.',
        ]];
        foreach (Branch::where('is_active', true)->whereIn('entity', ['hendhys', 'jihans'])->orderBy('name')->get() as $branch) {
            if ($branch->entity === 'hendhys' && $branch->type === 'pusat') continue;
            $locations[] = [
                'key' => 'branch-'.$branch->id, 'stock_key' => $branch->entity === 'jihans' ? 'jihans-retail' : 'branch-'.$branch->id,
                'entity' => $branch->entity, 'branch_id' => $branch->id, 'name' => $branch->name,
                'description' => $branch->entity === 'jihans'
                    ? 'Saldo penjualan bersama seluruh cabang Jihan’s. Hitung total stok seluruh lokasi yang masuk saldo ini; hasilnya mengubah saldo bersama.'
                    : 'Hanya stok siap jual cabang ini. Saldo cabang lain dan stok retur tidak diubah.',
            ];
        }
        return $locations;
    }

    public function allowed(User $user, object|array $location): bool
    {
        $l = (object) $location;
        if ($user->hasRole('owner')) return true;
        if ($user->hasRole('super_admin')) return in_array($user->entity, [$l->entity, 'all'], true);
        if (!in_array($l->entity, ['hendhys', 'jihans'], true) || !$user->hasAnyRole(['kasir_'.$l->entity, 'admin_'.$l->entity])) return false;
        if ($user->entity !== $l->entity) return false;
        // Use the selected branch only after validating its entity and active state.
        $branch = Branch::find(session('active_branch_id') ?: $user->branch_id);
        if (!$branch || !$branch->is_active || $branch->entity !== $user->entity) return false;
        if (($l->location_key ?? $l->key) === 'hendhys-pusat') return $branch->type === 'pusat';
        return $l->branch_id !== null && (int) $branch->id === (int) $l->branch_id;
    }

    public function location(string $key): array
    {
        $location = collect($this->locations())->firstWhere('key', $key);
        if (!$location) throw ValidationException::withMessages(['location_key' => 'Lokasi tidak aktif atau tidak tersedia.']);
        return $location;
    }

    public function accessForStock(User $user, string $stockKey): array
    {
        $keys = collect($this->locations())->filter(fn ($l) => $l['stock_key'] === $stockKey && $this->allowed($user, $l))->pluck('key');
        $session = $user->hasRole('owner') ? null : DB::table('stock_opname_sessions')
            ->where('stock_key', $stockKey)->whereIn('location_key', $keys)
            ->where('status', 'open')->where('expires_at', '>', now())->orderByDesc('id')->first();

        return ['can_count' => $session !== null, 'session' => $session ? $this->serialize($session) : null];
    }

    public function visibleSessions(User $user)
    {
        $query = DB::table('stock_opname_sessions');
        if ($user->hasRole('owner')) return $query;
        if ($user->hasRole('super_admin')) return $query->whereIn('entity', $user->entity === 'all' ? ['gudang', 'hendhys', 'jihans'] : [$user->entity]);
        $keys = collect($this->locations())->filter(fn ($l) => $this->allowed($user, $l))->pluck('key');
        return $query->whereIn('location_key', $keys);
    }

    public function serializeCount(object $count): array
    {
        $row = (array) $count;
        $row['number'] = 'SO-'.str_pad((string) $count->id, 6, '0', STR_PAD_LEFT);
        $row['created_at'] = Carbon::parse($count->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB';
        foreach (['quantity_before', 'quantity_after', 'difference'] as $key) $row[$key] = (int) $count->$key;
        $labels = ['super_admin' => 'Super Admin', 'admin_hendhys' => 'Admin Hendhys', 'kasir_hendhys' => 'Kasir Hendhys', 'admin_jihans' => 'Admin Jihan’s', 'kasir_jihans' => 'Kasir Jihan’s'];
        $row['role_label'] = $count->counted_by_role ? implode(', ', array_map(fn ($role) => $labels[$role] ?? $role, explode(',', $count->counted_by_role))) : null;
        return $row;
    }

    public function open(array $data, User $owner): int
    {
        abort_unless($owner->hasRole('owner'), 403);
        $location = $this->location($data['location_key']);
        return DB::transaction(function () use ($data, $owner, $location) {
            DB::table('stock_opname_locations')->insertOrIgnore(['key' => $location['stock_key']]);
            DB::table('stock_opname_locations')->where('key', $location['stock_key'])->lockForUpdate()->first();
            $old = DB::table('stock_opname_sessions')->where('request_key', $data['request_key'])->first();
            $expires = Carbon::parse($data['expires_at'], 'Asia/Jakarta');
            if ($old) {
                if ($old->opened_by != $owner->id || $old->location_key !== $location['key'] || $old->notes !== trim($data['notes']) || !Carbon::parse($old->expires_at)->equalTo($expires)) {
                    throw ValidationException::withMessages(['location_key' => 'Permintaan ini sudah dipakai untuk sesi lain. Muat ulang halaman.']);
                }
                return $old->id;
            }
            if ($expires->lte(now())) throw ValidationException::withMessages(['expires_at' => 'Batas waktu harus setelah waktu sekarang.']);
            if (DB::table('stock_opname_sessions')->where('stock_key', $location['stock_key'])->where('status', 'open')->where('expires_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['location_key' => 'Masih ada sesi aktif untuk saldo stok ini. Tutup sesi tersebut terlebih dahulu.']);
            }
            $id = DB::table('stock_opname_sessions')->insertGetId([
                'request_key' => $data['request_key'], 'location_key' => $location['key'], 'stock_key' => $location['stock_key'],
                'location_name' => $location['name'], 'entity' => $location['entity'], 'branch_id' => $location['branch_id'],
                'notes' => trim($data['notes']), 'opened_by' => $owner->id, 'opened_by_name' => $owner->name,
                'opened_at' => now(), 'expires_at' => $expires, 'status' => 'open',
            ]);
            $this->logger->log('create', 'stock_opname', "Owner membuka sesi opname #{$id}: {$location['name']}");
            DB::table('stock_opname_session_events')->insert(['session_id' => $id, 'action' => 'open', 'owner_id' => $owner->id, 'owner_name' => $owner->name, 'expires_at' => $expires, 'notes' => trim($data['notes']), 'created_at' => now()]);
            return $id;
        });
    }

    public function close(int $id, User $owner): void
    {
        abort_unless($owner->hasRole('owner'), 403);
        DB::transaction(function () use ($id, $owner) {
            $session = DB::table('stock_opname_sessions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($session, 404);
            if ($session->status === 'closed') return;
            DB::table('stock_opname_session_events')->insert(['session_id' => $id, 'action' => 'close', 'owner_id' => $owner->id, 'owner_name' => $owner->name, 'previous_status' => $session->status, 'previous_expires_at' => $session->expires_at, 'created_at' => now()]);
            DB::table('stock_opname_sessions')->where('id', $id)->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $owner->id, 'closed_by_name' => $owner->name]);
            $this->logger->log('update', 'stock_opname', "Owner menutup sesi opname #{$id}: {$session->location_name}");
        });
    }

    public function reopen(int $id, array $data, User $owner): void
    {
        abort_unless($owner->hasRole('owner'), 403);
        $initial = DB::table('stock_opname_sessions')->where('id', $id)->first();
        abort_unless($initial, 404);
        $this->location($initial->location_key);
        DB::transaction(function () use ($id, $data, $owner, $initial) {
            DB::table('stock_opname_locations')->where('key', $initial->stock_key)->lockForUpdate()->first();
            $s = DB::table('stock_opname_sessions')->where('id', $id)->lockForUpdate()->first();
            $expires = Carbon::parse($data['expires_at'], 'Asia/Jakarta');
            $notes = trim($data['notes']);
            if (mb_strlen($notes) < 5) throw ValidationException::withMessages(['notes' => 'Tuliskan alasan pembukaan kembali, minimal 5 karakter.']);
            $old = DB::table('stock_opname_session_events')->where('request_key', $data['request_key'])->first();
            if ($old) {
                if ($old->session_id != $id || $old->owner_id != $owner->id || $old->notes !== $notes || !Carbon::parse($old->expires_at)->equalTo($expires)) throw ValidationException::withMessages(['expires_at' => 'Permintaan telah digunakan. Muat ulang halaman.']);
                return;
            }
            if ($expires->lte(now())) throw ValidationException::withMessages(['expires_at' => 'Batas waktu baru harus setelah waktu sekarang.']);
            if ($this->active($s)) throw ValidationException::withMessages(['expires_at' => 'Sesi ini masih aktif. Tutup sesi terlebih dahulu.']);
            if (DB::table('stock_opname_sessions')->where('stock_key', $s->stock_key)->where('id', '<>', $id)->where('status', 'open')->where('expires_at', '>', now())->exists()) throw ValidationException::withMessages(['expires_at' => 'Ada sesi lain yang aktif untuk saldo ini. Tutup sesi tersebut dahulu.']);
            DB::table('stock_opname_session_events')->insert(['session_id' => $id, 'request_key' => $data['request_key'], 'action' => 'reopen', 'owner_id' => $owner->id, 'owner_name' => $owner->name, 'previous_status' => $s->status, 'previous_closed_at' => $s->closed_at, 'previous_closed_by' => $s->closed_by, 'previous_closed_by_name' => $s->closed_by_name, 'previous_expires_at' => $s->expires_at, 'expires_at' => $expires, 'notes' => $notes, 'created_at' => now()]);
            DB::table('stock_opname_sessions')->where('id', $id)->update(['status' => 'open', 'expires_at' => $expires, 'closed_at' => null, 'closed_by' => null, 'closed_by_name' => null]);
            $this->logger->log('update', 'stock_opname', "Owner membuka kembali sesi opname #{$id}: {$s->location_name}");
        });
    }

    public function table(object $session): array
    {
        return match ($session->stock_key) {
            'gudang' => ['jihans_gudang_stock', 'jihans_gudang_stock_movements', null, 'jihans'],
            'jihans-retail' => ['jihans_retail_stock', 'jihans_retail_stock_movements', null, null],
            'hendhys-pusat' => ['hendhys_stock_pusat', 'hendhys_stock_movements', null, 'hendhys'],
            default => ['hendhys_stock_branch', 'hendhys_stock_movements', (int) $session->branch_id, null],
        };
    }

    public function active(object $session): bool
    {
        return $session->status === 'open' && Carbon::parse($session->expires_at)->gt(now());
    }

    public function count(int $id, array $data, User $user): int
    {
        return DB::transaction(function () use ($id, $data, $user) {
            $session = DB::table('stock_opname_sessions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($session, 404);
            // Owner grants access, operational users perform the count.
            abort_if($user->hasRole('owner'), 403);
            abort_unless($this->allowed($user, $session), 403, 'Sesi opname bukan untuk lokasi Anda.');
            $location = $this->location($session->location_key);
            abort_unless($this->allowed($user, $location), 403);
            if (!$this->active($session)) throw ValidationException::withMessages(['session_id' => 'Sesi opname ditutup atau waktunya habis. Minta Owner membuka kembali sesi ini untuk melanjutkan.']);
            $product = Product::with('unit')->findOrFail($data['product_id']);
            $previous = DB::table('stock_opname_counts')->where('session_id', $id)->where('product_id', $product->id)->first();
            if ($previous) {
                if ($previous->counted_by == $user->id && $previous->quantity_before == $data['expected_quantity'] && $previous->quantity_after == $data['quantity'] && $previous->notes === trim($data['notes'])) return $previous->id;
                throw ValidationException::withMessages(['product_id' => 'Produk ini sudah dihitung pada sesi ini. Hasil tersimpan di riwayat.']);
            }
            [$table, $movements, $branchId, $lotEntity] = $this->table($session);
            $query = DB::table($table)->where('product_id', $product->id)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
            // Only count stock rows belonging to the selected location.
            $stock = $query->lockForUpdate()->first();
            if (!$stock) throw ValidationException::withMessages(['product_id' => 'Produk belum memiliki kartu stok di lokasi ini.']);
            if ((int) $stock->unit_id !== (int) $product->unit_id) throw ValidationException::withMessages(['product_id' => 'Satuan kartu stok berbeda dari produk. Periksa data master.']);
            $before = (int) round((float) $stock->quantity);
            if ($before !== (int) $data['expected_quantity']) throw ValidationException::withMessages(['expected_quantity' => 'Stok berubah sejak halaman dibuka. Muat ulang lalu hitung kembali sebelum menyimpan.']);
            $after = (int) $data['quantity'];
            $diff = $after - $before;
            $countId = DB::table('stock_opname_counts')->insertGetId([
                'session_id' => $id, 'product_id' => $product->id, 'unit_id' => $stock->unit_id, 'product_name' => $product->name, 'product_code' => $product->code,
                'unit_name' => $product->unit?->abbreviation ?? '-', 'quantity_before' => $before, 'quantity_after' => $after, 'difference' => $diff,
                'notes' => trim($data['notes']), 'counted_by' => $user->id, 'counted_by_name' => $user->name,
                'counted_by_role' => $user->getRoleNames()->implode(','), 'created_at' => now(),
            ]);
            if ($lotEntity && $diff > 0) $this->lots->credit($lotEntity, $product->id, $stock->unit_id, $before, $diff, 'stock_opname', $countId);
            if ($lotEntity && $diff < 0) $this->lots->debit($lotEntity, $product->id, $stock->unit_id, $before, -$diff, 'stock_opname', $countId, $user->id);
            $query->update(['quantity' => $after, 'last_updated' => now()]);
            $movement = [
                'product_id' => $product->id, 'source' => 'adjustment', 'reference_id' => $countId,
                'type' => $diff < 0 ? 'out' : 'in', 'quantity' => abs($diff), 'quantity_before' => $before, 'quantity_after' => $after,
                'notes' => "Stock opname sesi #{$id}, hasil #{$countId} — {$session->location_name}: ".trim($data['notes']), 'created_by' => $user->id, 'created_at' => now(),
            ];
            if ($session->entity === 'hendhys') $movement['branch_id'] = $branchId;
            $movementId = DB::table($movements)->insertGetId($movement);
            DB::table('stock_opname_counts')->where('id', $countId)->update(['stock_movement_table' => $movements, 'stock_movement_id' => $movementId]);
            $this->logger->log('update', 'stock_opname', "Opname #{$id}: {$product->name}, {$before} → {$after}, petugas {$user->name}");
            return $countId;
        });
    }

    public function serialize(object $s): array
    {
        $row = (array) $s;
        $row['active'] = $this->active($s);
        $row['expires_at_iso'] = Carbon::parse($s->expires_at)->toIso8601String();
        $row['state_label'] = $s->status === 'closed' ? 'Ditutup owner' : ($row['active'] ? 'Aktif' : 'Kedaluwarsa');
        foreach (['opened_at', 'expires_at', 'closed_at'] as $field) $row[$field] = $s->$field ? Carbon::parse($s->$field)->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : null;
        return $row;
    }
}
