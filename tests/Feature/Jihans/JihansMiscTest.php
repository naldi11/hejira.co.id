<?php

namespace Tests\Feature\Jihans;

use App\Models\GudangReturn;
use App\Models\GudangStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansMiscTest extends TestCase
{
    use RefreshDatabase;

    private function kasirJihans(): User
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');

        return $user;
    }

    private function adminJihans(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');

        return $user;
    }

    public function test_dashboard_renders_inertia_with_stats(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jihans/Dashboard')
                ->has('stats')
                ->has('recentTransactions')
                ->has('lowStocks'));
    }

    public function test_production_config_edit_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.master.production-config.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/ProductionConfig')->has('config')->has('products'));
    }

    public function test_returns_index_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.returns-to-gudang.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Returns/Index')->has('returns'));
    }

    public function test_returns_store_debits_jihans_stock(): void
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Tepung',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'source_type' => 'purchased', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
        GudangStock::create(['product_id' => $product->id, 'quantity' => 20, 'unit_id' => $unit->id]);

        $this->actingAs($this->adminJihans())
            ->post(route('jihans.returns-to-gudang.store'), [
                'date'  => now()->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_id' => $unit->id, 'condition' => 'Rusak']],
            ])
            ->assertRedirect(route('jihans.returns-to-gudang.index'));

        $this->assertSame(1, GudangReturn::where('from_entity', 'jihans')->count());
        $this->assertEquals(15.0, (float) GudangStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_returns_store_rejects_quantity_above_stock(): void
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pcs', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Saus',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'source_type' => 'purchased', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
        GudangStock::create(['product_id' => $product->id, 'quantity' => 3, 'unit_id' => $unit->id]);

        $this->actingAs($this->adminJihans())
            ->post(route('jihans.returns-to-gudang.store'), [
                'date'  => now()->toDateString(),
                'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_id' => $unit->id, 'condition' => 'Rusak']],
            ])
            ->assertSessionHas('error');

        // Stock unchanged — the transaction rolled back.
        $this->assertEquals(3.0, (float) GudangStock::where('product_id', $product->id)->value('quantity'));
    }
}
