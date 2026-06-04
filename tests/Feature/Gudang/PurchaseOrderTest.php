<?php

namespace Tests\Feature\Gudang;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        return $user;
    }

    private function product(): Product
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);

        return Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Gula',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'hpp' => 12000,
        ]);
    }

    public function test_index_renders_inertia_page(): void
    {
        $this->actingAs($this->adminGudang())
            ->get(route('gudang.po.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Gudang/PurchaseOrders/Index')->has('orders'));
    }

    public function test_store_creates_draft_po_with_details(): void
    {
        $supplier = Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100,999), 'name' => 'PT Sumber', 'is_active' => true]);
        $product  = $this->product();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.po.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_id' => $product->unit_id, 'price' => 12000],
                ],
            ])
            ->assertRedirect();

        $po = PurchaseOrder::first();
        $this->assertNotNull($po);
        $this->assertSame('draft', $po->status);
        $this->assertEquals(60000.0, (float) $po->total_amount);
        $this->assertDatabaseHas('gudang_po_details', [
            'po_id'            => $po->id,
            'product_id'       => $product->id,
            'quantity_ordered' => 5,
            'total'            => 60000,
        ]);
    }

    public function test_store_requires_at_least_one_item(): void
    {
        $supplier = Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100,999), 'name' => 'PT Sumber', 'is_active' => true]);

        $this->actingAs($this->adminGudang())
            ->from(route('gudang.po.create'))
            ->post(route('gudang.po.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseOrder::count());
    }
}
