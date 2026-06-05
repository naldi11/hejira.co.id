<?php

namespace Tests\Feature\Master;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The shared master controllers (Supplier/Customer/Product/Karyawan) render the
 * SAME Inertia pages under different scopes, passing the React layout component
 * name + route prefix. These assertions guard against the layout/route prefix
 * regression (where the pages rendered with no sidebar).
 */
class MasterScopeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $entity): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity]);
        $user->assignRole($role);

        return $user;
    }

    public function test_gudang_scope_passes_gudang_layout_and_route_prefix(): void
    {
        $this->actingAs($this->user('admin_gudang', 'gudang'))
            ->get(route('master.suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Master/Suppliers/Index')
                ->where('layout', 'GudangLayout')
                ->where('routePrefix', 'master.')
                ->where('currentScope', 'gudang'));
    }

    public function test_jihans_scope_passes_jihans_layout_and_route_prefix(): void
    {
        $this->actingAs($this->user('kasir_jihans', 'jihans'))
            ->get(route('jihans.master.suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Master/Suppliers/Index')
                ->where('layout', 'JihansLayout')
                ->where('routePrefix', 'jihans.master.')
                ->where('currentScope', 'jihans'));
    }
}
