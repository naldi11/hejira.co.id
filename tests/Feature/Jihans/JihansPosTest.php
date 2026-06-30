<?php

namespace Tests\Feature\Jihans;

use App\Models\Customer;
use App\Models\JihansStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansPosTest extends TestCase
{
    use RefreshDatabase;

    private function kasirJihans(): User
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');
        return $user;
    }

    public function test_pos_index_renders_inertia(): void
    {
        $this->actingAs($this->kasirJihans())
            ->get(route('jihans.pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Pos/Index')->has('products')->has('customers'));
    }

    public function test_pos_store_creates_transaction_and_redirects_to_receipt(): void
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-POS', 'name' => 'Tepung', 'price' => 10000,
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'source_type' => 'purchased', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
        JihansStock::create(['product_id' => $product->id, 'quantity' => 20, 'unit_id' => $unit->id]);

        $response = $this->actingAs($this->kasirJihans())
            ->postJson(route('jihans.pos.store'), [
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 20000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 20000,
                'amount_paid'      => 20000,
                'items'            => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 2,
                        'price'      => 10000,
                        'total'      => 20000,
                    ]
                ]
            ]);

        $response->assertOk()->assertJsonStructure(['success', 'transaction_id', 'redirect']);
        
        $this->assertEquals(18.0, (float) JihansStock::where('product_id', $product->id)->value('quantity'));
    }
}
