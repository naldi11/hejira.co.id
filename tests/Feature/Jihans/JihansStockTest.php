<?php

namespace Tests\Feature\Jihans;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansStockTest extends TestCase
{
    use RefreshDatabase;

    private function kasirJihans(): User
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');

        return $user;
    }

    public function test_stock_index_renders_inertia(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Stock/Index')->has('stocks'));
    }

    public function test_stock_movements_renders_inertia(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.stock.movements'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Stock/Movements')->has('movements'));
    }

    public function test_transactions_index_renders_inertia(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Transactions/Index')->has('transactions'));
    }

    public function test_gudang_admin_cannot_access_jihans_stock(): void
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        $this->actingAs($user)
            ->get(route('jihans.stock.index'))
            ->assertForbidden();
    }
}
