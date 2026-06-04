<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HendhysDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function hendhysPusat(): User
    {
        $branch = Branch::create(['code' => 'HND-PST', 'name' => 'Hendhys Pusat', 'type' => 'pusat', 'is_active' => true]);
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');
        return $user;
    }

    public function test_dashboard_renders_inertia_with_stats(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Hendhys/Dashboard')
                ->has('stats')
                ->has('recentTransactions')
                ->has('lowStocks'));
    }

    public function test_gudang_admin_cannot_access_hendhys(): void
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        $this->actingAs($user)
            ->get(route('hendhys.dashboard'))
            ->assertForbidden();
    }
}
