<?php

namespace Tests\Feature\Gudang;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GudangRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('super_admin');

        return $user;
    }

    private function product(): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        $unit     = Unit::firstOrCreate(['abbreviation' => 'KG'], ['name' => 'Kilogram', 'entity_scope' => 'all']);

        return Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Gula',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'hpp' => 12000,
        ]);
    }

    public function test_po_with_duplicate_products_is_rejected()
    {
        $supplier = Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100,999), 'name' => 'PT Sumber', 'is_active' => true]);
        $product  = $this->product();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.po.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_id' => $product->unit_id, 'price' => 12000],
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_id' => $product->unit_id, 'price' => 12000],
                ],
            ])
            ->assertSessionHasErrors('items.*.product_id');

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_fractional_quantity_is_rejected_in_po()
    {
        $supplier = Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100,999), 'name' => 'PT Sumber', 'is_active' => true]);
        $product  = $this->product();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.po.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [
                    ['product_id' => $product->id, 'quantity' => 2.5, 'unit_id' => $product->unit_id, 'price' => 12000],
                ],
            ])
            ->assertSessionHasErrors('items.*.quantity');
    }

    public function test_fractional_quantity_is_rejected_in_receiving()
    {
        $supplier = Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100,999), 'name' => 'PT Sumber', 'is_active' => true]);
        $product  = $this->product();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.receiving.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [[
                    'product_id'     => $product->id,
                    'quantity_bagus' => 2.5,
                    'quantity_rusak' => 0,
                    'unit_id'        => $product->unit_id,
                    'hpp_price'      => 5000,
                ]],
            ])
            ->assertSessionHasErrors(['items.0.quantity_bagus']);
    }
}
