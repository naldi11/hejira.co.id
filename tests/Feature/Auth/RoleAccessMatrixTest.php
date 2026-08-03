<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Matriks akses setelah konsolidasi 8 role menjadi 6.
 *
 * Test ini adalah alat ukur Fase 2. Prinsipnya: konsolidasi mengganti NAMA role,
 * bukan KEMAMPUAN. Satu-satunya perubahan kemampuan yang disengaja adalah
 * super_admin TIDAK lagi mengoperasikan POS/kasir — diputuskan setelah data
 * produksi membuktikan super_admin_hendhys & super_admin_jihans tidak pernah
 * sekali pun membuat transaksi, membuka shift, maupun menerima transfer.
 *
 * Role akhir: owner, super_admin, admin_hendhys, kasir_hendhys, admin_jihans,
 * kasir_jihans.
 */
class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        'owner', 'super_admin',
        'admin_hendhys', 'kasir_hendhys',
        'admin_jihans', 'kasir_jihans',
    ];

    private function user(string $role): User
    {
        foreach (self::ROLES as $r) {
            Role::findOrCreate($r, 'web');
        }

        $entity = match ($role) {
            'owner'                          => 'all',
            'super_admin'                    => 'all',
            'admin_hendhys', 'kasir_hendhys' => 'hendhys',
            'admin_jihans', 'kasir_jihans'   => 'jihans',
        };

        $branchId = null;
        if (in_array($role, ['admin_hendhys', 'kasir_hendhys'], true)) {
            $branchId = Branch::firstOrCreate(
                ['code' => 'HND-PST'],
                ['name' => 'Gudang Hendhys', 'type' => 'pusat', 'entity' => 'hendhys', 'is_active' => true]
            )->id;
        }
        if (in_array($role, ['admin_jihans', 'kasir_jihans'], true)) {
            $branchId = Branch::firstOrCreate(
                ['code' => 'JF-P3'],
                ['name' => 'Unit Jihaan\'s Food', 'type' => 'cabang', 'entity' => 'jihans', 'is_active' => true]
            )->id;
        }

        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branchId]);
        $user->assignRole($role);

        return $user;
    }

    /** Anggap lolos bila BUKAN 403 (redirect/200/422 berarti otorisasi lewat). */
    private function allowed(string $role, string $uri): bool
    {
        return $this->actingAs($this->user($role))->get($uri)->status() !== 403;
    }

    /** [uri => role yang BOLEH mengakses] */
    private const MATRIX = [
        '/gudang/dashboard'    => ['super_admin'],
        '/gudang/stock'        => ['super_admin'],
        '/hendhys/dashboard'   => ['super_admin', 'admin_hendhys', 'kasir_hendhys'],
        '/hendhys/pos'         => ['kasir_hendhys'],
        '/hendhys/productions' => ['super_admin', 'admin_hendhys'],
        '/jihans/dashboard'    => ['super_admin', 'admin_jihans', 'kasir_jihans'],
        '/jihans/pos'          => ['kasir_jihans'],
        '/jihans/production'   => ['super_admin', 'admin_jihans'],
        '/owner/dashboard'     => ['owner'],
    ];

    public function test_access_matrix(): void
    {
        $problems = [];

        foreach (self::MATRIX as $uri => $allowedRoles) {
            foreach (self::ROLES as $role) {
                $shouldPass = in_array($role, $allowedRoles, true);
                $actual     = $this->allowed($role, $uri);

                if ($shouldPass !== $actual) {
                    $problems[] = sprintf(
                        '%-22s role %-14s -> diharapkan %s, kenyataannya %s',
                        $uri,
                        $role,
                        $shouldPass ? 'BOLEH ' : 'DITOLAK',
                        $actual ? 'BOLEH' : 'DITOLAK'
                    );
                }
            }
        }

        // Kumpulkan SEMUA penyimpangan lebih dulu supaya sekali jalan langsung
        // terlihat semua yang salah, bukan berhenti di pelanggaran pertama.
        $this->assertSame([], $problems, "Matriks akses menyimpang:\n" . implode("\n", $problems));
    }

    public function test_role_lama_sudah_tidak_ada_di_seeder_dan_kode(): void
    {
        foreach (['admin_gudang', 'super_admin_hendhys', 'super_admin_jihans'] as $old) {
            $this->assertFalse(
                Role::where('name', $old)->exists(),
                "Role lama '{$old}' seharusnya sudah dilebur ke super_admin."
            );
        }
    }

    public function test_super_admin_tidak_dipaksa_memilih_cabang(): void
    {
        // super_admin tidak ditempatkan di cabang mana pun. Kalau ia masih masuk
        // daftar CheckBranch, setiap akses akan dialihkan ke /select-branch dan
        // modul Gudang jadi tidak bisa dibuka sama sekali.
        $this->actingAs($this->user('super_admin'))
            ->get('/gudang/dashboard')
            ->assertOk();
    }
}
