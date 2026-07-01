<?php

namespace Tests\Feature\Owner;

use App\Models\JihansTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        Role::findOrCreate('owner', 'web');
        $user = User::factory()->create(['entity' => 'all']);
        $user->assignRole('owner');

        return $user;
    }

    public static function ownerPages(): array
    {
        return [
            'dashboard' => ['owner.dashboard', 'Owner/Dashboard'],
            'reports'   => ['owner.reports', 'Owner/Reports'],
        ];
    }

    #[DataProvider('ownerPages')]
    public function test_owner_pages_render(string $routeName, string $component): void
    {
        $this->actingAs($this->owner())
            ->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    public function test_consolidation_dashboard_sums_paid_revenue(): void
    {
        $owner = $this->owner();

        JihansTransaction::create([
            'transaction_number' => 'JHS-TEST-1', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Retail',
            'ppn_type' => 'none', 'ppn_rate' => 0, 'subtotal' => 30000, 'discount_amount' => 0,
            'tax_amount' => 0, 'other_costs' => 0, 'grand_total' => 30000, 'status' => 'paid', 'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('owner.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Dashboard')
                ->where('stats.jihans_revenue', 30000));
    }

    public function test_non_owner_is_forbidden(): void
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        $this->actingAs($user)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    public function test_owner_reports_export_csv(): void
    {
        $this->actingAs($this->owner())
            ->get(route('owner.reports.export', ['format' => 'csv', 'periode' => 'bulanan']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=Laporan_Omset_bulanan_' . date('Ymd') . '.csv');
    }

    public function test_owner_reports_export_pdf(): void
    {
        // Assert it returns PDF stream
        $this->actingAs($this->owner())
            ->get(route('owner.reports.export', ['format' => 'pdf', 'periode' => 'bulanan']))
            ->assertOk();
    }
}
