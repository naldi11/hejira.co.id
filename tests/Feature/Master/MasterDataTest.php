<?php

namespace Tests\Feature\Master;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        return $user;
    }

    // ── Supplier ─────────────────────────────────────────────────────────────

    public function test_supplier_index_renders_inertia_for_gudang_scope(): void
    {
        $this->actingAs($this->adminGudang())
            ->get(route('master.suppliers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Suppliers/Index')->has('suppliers'));
    }

    public function test_supplier_store_generates_code_and_persists(): void
    {
        $this->actingAs($this->adminGudang())
            ->post(route('master.suppliers.store'), ['name' => 'PT Maju', 'is_active' => true])
            ->assertRedirect(route('master.suppliers.index'));

        $this->assertDatabaseHas('master_suppliers', ['name' => 'PT Maju', 'entity_scope' => 'all']);
        $this->assertNotNull(\App\Models\Supplier::first()->code);
    }

    public function test_supplier_store_requires_name(): void
    {
        $this->actingAs($this->adminGudang())->from(route('master.suppliers.create'))
            ->post(route('master.suppliers.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // ── Customer ─────────────────────────────────────────────────────────────

    public function test_customer_store_persists_with_default_type(): void
    {
        $this->actingAs($this->adminGudang())
            ->post(route('master.customers.store'), ['name' => 'Toko Bu Ani', 'is_active' => true])
            ->assertRedirect(route('master.customers.index'));

        $this->assertDatabaseHas('master_customers', ['name' => 'Toko Bu Ani', 'type' => 'Pelanggan Individual']);
    }

    // ── Product ──────────────────────────────────────────────────────────────

    public function test_product_index_renders_inertia(): void
    {
        $this->actingAs($this->adminGudang())
            ->get(route('master.products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Products/Index')->has('products'));
    }

    public function test_product_store_firstorcreates_category_and_unit_by_name(): void
    {
        $this->actingAs($this->adminGudang())
            ->post(route('master.products.store'), [
                'name' => 'Keju Cheddar',
                'category_id' => 'Dairy',   // new category name
                'unit_id' => 'Kilogram',    // new unit name
                'hpp' => 50000, 'selling_price' => 65000, 'stock_min' => 3,
                'ppn_type' => 'none', 'ppn_rate' => 11,
                'product_type' => 'INV', 'source_type' => 'purchased', 'status' => 'active',
                'visible_gudang' => true,
            ])
            ->assertRedirect(route('master.products.index'));

        $product = Product::first();
        $this->assertNotNull($product);
        $this->assertSame('Keju Cheddar', $product->name);

        // Category + unit were created from the free-text names.
        $this->assertDatabaseHas('master_product_categories', ['name' => 'Dairy']);
        $this->assertDatabaseHas('master_units', ['name' => 'Kilogram']);
        $this->assertSame(ProductCategory::where('name', 'Dairy')->value('id'), $product->category_id);
        $this->assertSame(Unit::where('name', 'Kilogram')->value('id'), $product->unit_id);
    }

    public function test_product_store_requires_core_fields(): void
    {
        $this->actingAs($this->adminGudang())->from(route('master.products.create'))
            ->post(route('master.products.store'), ['name' => ''])
            ->assertSessionHasErrors(['name', 'category_id', 'unit_id', 'hpp', 'selling_price']);
    }
}
