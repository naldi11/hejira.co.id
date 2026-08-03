<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sederhanakan 8 role menjadi 6.
 *
 * Tiga role dilebur menjadi satu `super_admin`:
 *   - admin_gudang        : pengelola Gudang Utama (role yang benar-benar aktif)
 *   - super_admin_hendhys : nol aktivitas, selalu berdampingan dengan admin_hendhys
 *   - super_admin_jihans  : nol aktivitas, selalu berdampingan dengan admin_jihans
 *
 * Diverifikasi dari data produksi sebelum migrasi ini dibuat: ketiga role tersebut
 * tidak pernah membuat transaksi, membuka shift, menerima transfer, maupun mencatat
 * produksi. Satu-satunya aktivitas nyata ada pada admin_gudang di modul Gudang
 * (PO/GRN/transfer keluar), dan kemampuan itu dipertahankan penuh oleh super_admin.
 *
 * Peran akhir: owner, super_admin, admin_hendhys, kasir_hendhys, admin_jihans,
 * kasir_jihans.
 *
 * Reversible: pemetaan lama disimpan di tabel bantu `role_consolidation_backup`
 * sehingga down() bisa mengembalikan role, entity, dan penempatan cabang semula.
 */
return new class extends Migration
{
    private const MERGED = ['admin_gudang', 'super_admin_hendhys', 'super_admin_jihans'];
    private const TARGET = 'super_admin';

    public function up(): void
    {
        if (! Schema::hasTable('role_consolidation_backup')) {
            Schema::create('role_consolidation_backup', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('old_role', 100);
                $table->string('old_entity', 20)->nullable();
                $table->unsignedBigInteger('old_branch_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        $guard = 'web';

        $oldRoles = DB::table('roles')
            ->whereIn('name', self::MERGED)->where('guard_name', $guard)
            ->pluck('id', 'name');

        if ($oldRoles->isEmpty()) {
            return; // sudah pernah dijalankan
        }

        $targetId = DB::table('roles')->where('name', self::TARGET)->where('guard_name', $guard)->value('id')
            ?: DB::table('roles')->insertGetId([
                'name' => self::TARGET, 'guard_name' => $guard,
                'created_at' => now(), 'updated_at' => now(),
            ]);

        // Gabungkan permission dari ketiga role lama supaya super_admin tidak
        // kehilangan cakupan apa pun (permission belum ditegakkan di kode, tetapi
        // dijaga akurat sebagai dokumentasi & jalan naik).
        $permissionIds = DB::table('role_has_permissions')
            ->whereIn('role_id', $oldRoles->values())->pluck('permission_id')->unique();

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId, 'role_id' => $targetId,
            ]);
        }

        foreach ($oldRoles as $roleName => $roleId) {
            $userIds = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', \App\Models\User::class)
                ->pluck('model_id');

            foreach ($userIds as $userId) {
                $user = DB::table('master_users')->where('id', $userId)->first();

                DB::table('role_consolidation_backup')->insert([
                    'user_id'       => $userId,
                    'old_role'      => $roleName,
                    'old_entity'    => $user->entity ?? null,
                    'old_branch_id' => $user->branch_id ?? null,
                    'created_at'    => now(),
                ]);

                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $targetId, 'model_type' => \App\Models\User::class, 'model_id' => $userId,
                ]);

                // super_admin mengawasi keempat lini (Gudang Utama, Hendhys, Jihans,
                // Izzy) sehingga entity-nya 'all' dan tidak ditempatkan di cabang
                // mana pun — CheckEntity meloloskan 'all' untuk semua entitas.
                DB::table('master_users')->where('id', $userId)->update([
                    'entity'    => 'all',
                    'branch_id' => null,
                ]);
            }

            DB::table('model_has_roles')->where('role_id', $roleId)->delete();
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_consolidation_backup')) {
            return;
        }

        $guard = 'web';

        foreach (DB::table('role_consolidation_backup')->get() as $row) {
            $roleId = DB::table('roles')->where('name', $row->old_role)->where('guard_name', $guard)->value('id')
                ?: DB::table('roles')->insertGetId([
                    'name' => $row->old_role, 'guard_name' => $guard,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId, 'model_type' => \App\Models\User::class, 'model_id' => $row->user_id,
            ]);

            DB::table('master_users')->where('id', $row->user_id)->update([
                'entity'    => $row->old_entity,
                'branch_id' => $row->old_branch_id,
            ]);
        }

        $targetId = DB::table('roles')->where('name', self::TARGET)->where('guard_name', $guard)->value('id');
        if ($targetId) {
            $restoredUserIds = DB::table('role_consolidation_backup')->pluck('user_id');
            DB::table('model_has_roles')
                ->where('role_id', $targetId)
                ->whereIn('model_id', $restoredUserIds)
                ->delete();

            // Jangan tinggalkan role super_admin yatim tanpa satu pun pengguna —
            // itu akan tampil di form pembuatan user dan membingungkan.
            // Hanya dihapus bila memang sudah tidak dipakai siapa pun.
            $stillUsed = DB::table('model_has_roles')->where('role_id', $targetId)->exists();
            if (! $stillUsed) {
                DB::table('role_has_permissions')->where('role_id', $targetId)->delete();
                DB::table('roles')->where('id', $targetId)->delete();
            }
        }

        Schema::dropIfExists('role_consolidation_backup');
    }
};
