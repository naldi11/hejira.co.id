<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Render-coverage for the Hendhys create-form + report sub-pages that the existing
 * suite didn't reach. Confirms each controller method returns the right Inertia
 * component (catches prop/controller errors not visible to the build).
 */
class HendhysScreensTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $branchType = 'pusat'): User
    {
        $branch = Branch::create(['code' => 'HND-'.strtoupper($branchType).rand(1, 99), 'name' => "Hendhys $branchType", 'type' => $branchType, 'is_active' => true]);
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');

        return $user;
    }

    private function admin(string $branchType = 'pusat'): User
    {
        $branch = Branch::create(['code' => 'HND-'.strtoupper($branchType).rand(1, 99), 'name' => "Hendhys $branchType", 'type' => $branchType, 'is_active' => true]);
        Role::findOrCreate('admin_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('admin_hendhys');

        return $user;
    }

    /** @return array<string, array{0:string,1:string,2:string}> route, component, branchType */
    public static function createForms(): array
    {
        return [
            'transfer-requests create'  => ['hendhys.transfer-requests.create', 'Hendhys/TransferRequests/Create', 'pusat'],
            'returns create'            => ['hendhys.returns.create', 'Hendhys/Returns/Create', 'cabang'], // returns = cabang → pusat (pusat ditolak 403)
            'returns-to-gudang create'  => ['hendhys.returns-to-gudang.create', 'Hendhys/ReturnsToGudang/Create', 'pusat'],
            'transfer-to-branch create' => ['hendhys.transfer-to-branch.create', 'Hendhys/TransferToBranch/Create', 'pusat'],
            'branch-requests create'    => ['hendhys.branch-requests.create', 'Hendhys/BranchRequests/Create', 'cabang'],
        ];
    }

    #[DataProvider('createForms')]
    public function test_create_forms_render(string $routeName, string $component, string $branchType): void
    {
        $this->actingAs($this->admin($branchType))
            ->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    /**
     * NOTE: `mingguan` & `bulanan` are intentionally excluded — their queries use
     * MySQL-only date functions (`YEARWEEK`, `DATE_FORMAT`) that sqlite (test DB)
     * cannot execute. They work on production MySQL; not portably testable here.
     *
     * @return array<string, array{0:string,1:string}>
     */
    public static function reportPages(): array
    {
        return [
            'harian'    => ['hendhys.reports.harian', 'Hendhys/Reports/Harian'],
            'laci'      => ['hendhys.reports.laci', 'Hendhys/Reports/Laci'],
            'pelanggan' => ['hendhys.reports.pelanggan', 'Hendhys/Reports/Pelanggan'],
        ];
    }

    #[DataProvider('reportPages')]
    public function test_report_pages_render(string $routeName, string $component): void
    {
        $user = $routeName === 'hendhys.reports.laci' ? $this->user('pusat') : $this->admin('pusat');
        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
}
