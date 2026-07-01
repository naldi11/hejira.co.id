<?php

namespace Tests\Feature\Gudang;

use App\Models\JihansGudangStock;
use App\Models\JihansGudangStockMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('admin_gudang', 'web');

        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        return $user;
    }

    private function makeProduct(array $overrides = [], ?float $stock = null): Product
    {
        $category = ProductCategory::create(['name' => 'Frozen', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);

        $product = Product::create(array_merge([
            'code'         => 'P-'.fake()->unique()->numberBetween(10000, 99999),
            'name'         => 'Test Product',
            'category_id'  => $category->id,
            'unit_id'      => $unit->id,
            'product_type' => 'INV',
            'entity_scope' => 'all',
            'status'       => 'active',
            'stock_min'    => 5,
        ], $overrides));

        if ($stock !== null) {
            JihansGudangStock::create([
                'product_id' => $product->id,
                'quantity'   => $stock,
                'unit_id'    => $unit->id,
            ]);
        }

        return $product;
    }

    public function test_index_renders_inertia_page_with_stock_props(): void
    {
        $product = $this->makeProduct(['name' => 'Ayam Frozen'], stock: 12);

        $this->actingAs($this->adminGudang())
            ->get(route('gudang.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gudang/Stock/Index')
                ->has('stocks.data', 1, fn (Assert $row) => $row
                    ->where('name', 'Ayam Frozen')
                    ->where('current_stock', 12)
                    ->where('is_low', false)
                    ->etc())
                ->has('units')
                ->where('filters.search', '')
            );
    }

    public function test_search_filter_narrows_results(): void
    {
        $this->makeProduct(['name' => 'Ayam Frozen'], stock: 3);
        $this->makeProduct(['name' => 'Sapi Beku'], stock: 3);

        $this->actingAs($this->adminGudang())
            ->get(route('gudang.stock.index', ['search' => 'Ayam']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.name', 'Ayam Frozen')
                ->where('filters.search', 'Ayam')
            );
    }

    public function test_low_stock_filter_only_returns_products_at_or_below_minimum(): void
    {
        $this->makeProduct(['name' => 'Cukup', 'stock_min' => 5], stock: 20);
        $this->makeProduct(['name' => 'Menipis', 'stock_min' => 5], stock: 2);

        $this->actingAs($this->adminGudang())
            ->get(route('gudang.stock.index', ['low_stock' => '1']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.name', 'Menipis')
                ->where('stocks.data.0.is_low', true)
            );
    }

    public function test_adjust_updates_balance_and_records_movement(): void
    {
        $product = $this->makeProduct(stock: 10);

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.stock.adjust'), [
                'product_id' => $product->id,
                'unit_id'    => $product->unit_id,
                'quantity'   => 7,
                'notes'      => 'Stock opname bulanan',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(7, (int) JihansGudangStock::where('product_id', $product->id)->value('quantity'));

        $this->assertDatabaseHas('jihans_gudang_stock_movements', [
            'product_id'      => $product->id,
            'source'          => 'adjustment',
            'type'            => 'out',
            'quantity'        => 3,
            'quantity_before' => 10,
            'quantity_after'  => 7,
        ]);
    }

    public function test_adjust_requires_notes(): void
    {
        $product = $this->makeProduct(stock: 10);

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.stock.adjust'), [
                'product_id' => $product->id,
                'unit_id'    => $product->unit_id,
                'quantity'   => 7,
                'notes'      => '',
            ])
            ->assertSessionHasErrors('notes');

        // Balance must remain untouched when validation fails.
        $this->assertSame(10, (int) JihansGudangStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_adjust_rejects_negative_quantity(): void
    {
        $product = $this->makeProduct(stock: 10);

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.stock.adjust'), [
                'product_id' => $product->id,
                'unit_id'    => $product->unit_id,
                'quantity'   => -3,
                'notes'      => 'invalid',
            ])
            ->assertSessionHasErrors('quantity');
    }

    public function test_user_without_gudang_role_is_forbidden(): void
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');

        $this->actingAs($user)
            ->get(route('gudang.stock.index'))
            ->assertForbidden();
    }
}
