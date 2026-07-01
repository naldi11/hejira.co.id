<?php

namespace Tests\Feature\Jihans;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TransferRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansTransferRequestTest extends TestCase
{
    use RefreshDatabase;

    private function kasirJihans(): User
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');

        \App\Models\CashierShift::create([
            'user_id' => $user->id,
            'entity' => 'jihans',
            'status' => 'open',
            'opened_at' => now(),
            'starting_cash' => 100000,
        ]);

        return $user;
    }

    private function adminJihans(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');

        return $user;
    }

    private function product(string $source = 'purchased'): Product
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);

        return Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Tepung',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'source_type' => $source, 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
    }

    public function test_index_renders_inertia_with_incoming_transfers_prop(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.transfer-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jihans/TransferRequests/Index')
                ->has('requests')
                ->has('incomingTransfers'));
    }

    public function test_store_creates_request_with_details(): void
    {
        $product = $this->product();

        $this->actingAs($this->adminJihans())
            ->post(route('jihans.transfer-requests.store'), [
                'date'  => now()->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_id' => $product->unit_id]],
            ])
            ->assertRedirect(route('jihans.transfer-requests.index'));

        $tr = TransferRequest::where('from_entity', 'jihans')->first();
        $this->assertNotNull($tr);
        $this->assertSame('pending', $tr->status);
        $this->assertDatabaseHas('gudang_transfer_request_details', [
            'request_id' => $tr->id, 'product_id' => $product->id, 'quantity_requested' => 4,
        ]);
    }

    public function test_store_blocks_self_produced_products(): void
    {
        $produced = $this->product('produced');

        $this->actingAs($this->adminJihans())
            ->from(route('jihans.transfer-requests.create'))
            ->post(route('jihans.transfer-requests.store'), [
                'date'  => now()->toDateString(),
                'items' => [['product_id' => $produced->id, 'quantity' => 2, 'unit_id' => $produced->unit_id]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, TransferRequest::count());
    }

    public function test_pending_index_renders_inertia(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.pending.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Pending/Index')->has('pendings'));
    }
}
