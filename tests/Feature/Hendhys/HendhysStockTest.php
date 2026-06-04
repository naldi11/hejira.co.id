<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HendhysStockTest extends TestCase
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

    public function test_stock_index_renders_inertia(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Stock/Index')->has('stocks'));
    }

    public function test_stock_movements_renders_inertia(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.stock.movements'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Stock/Movements')->has('movements'));
    }

    public function test_transactions_index_renders_inertia(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Transactions/Index')->has('transactions'));
    }

    public function test_pending_index_renders_inertia(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.pending.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Pending/Index')->has('pendings'));
    }
}
