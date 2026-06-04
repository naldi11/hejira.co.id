<?php

namespace Tests\Feature\Gudang;

use App\Models\GudangStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Receiving;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReceivingTest extends TestCase
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
        $unit     = Unit::create(['name' => 'Box', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);

        return Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Cokelat',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'hpp' => 5000,
        ]);
    }

    private function supplier(): Supplier
    {
        return Supplier::create(['code' => 'SUP-'.fake()->unique()->numberBetween(100, 999), 'name' => 'PT Bahan', 'is_active' => true]);
    }

    public function test_index_renders_inertia_page(): void
    {
        $this->actingAs($this->adminGudang())
            ->get(route('gudang.receiving.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Gudang/Receivings/Index')->has('receivings'));
    }

    public function test_store_manual_grn_credits_good_quantity_to_stock(): void
    {
        $product  = $this->product();
        $supplier = $this->supplier();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.receiving.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [[
                    'product_id'     => $product->id,
                    'quantity_bagus' => 8,
                    'quantity_rusak' => 2,
                    'unit_id'        => $product->unit_id,
                    'hpp_price'      => 5000,
                    'batch_number'   => '',
                    'expired_date'   => '',
                ]],
            ])
            ->assertRedirect(route('gudang.receiving.index'))
            ->assertSessionHas('success');

        $grn = Receiving::first();
        $this->assertNotNull($grn);
        $this->assertSame('open', $grn->status);

        // Only the "bagus" quantity (8) enters active warehouse stock; the 2 damaged do not.
        $this->assertSame(8, (int) GudangStock::where('product_id', $product->id)->value('quantity'));

        // Both good and damaged rows are recorded as receiving details.
        $this->assertDatabaseHas('gudang_receiving_details', ['receiving_id' => $grn->id, 'kondisi' => 'baik', 'quantity' => 8]);
        $this->assertDatabaseHas('gudang_receiving_details', ['receiving_id' => $grn->id, 'kondisi' => 'rusak', 'quantity' => 2]);
    }

    public function test_store_requires_items(): void
    {
        $supplier = $this->supplier();

        $this->actingAs($this->adminGudang())
            ->from(route('gudang.receiving.create'))
            ->post(route('gudang.receiving.store'), [
                'supplier_id' => $supplier->id,
                'date'        => now()->toDateString(),
                'items'       => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Receiving::count());
    }
}
