<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HendhysPosTest extends TestCase
{
    use RefreshDatabase;

    private function pusatKasir(): User
    {
        $branch = Branch::create(['code' => 'HND-PST', 'name' => 'Hendhys Pusat', 'type' => 'pusat', 'is_active' => true]);
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');

        \App\Models\CashierShift::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'entity' => 'hendhys',
            'status' => 'open',
            'opened_at' => now(),
            'starting_cash' => 100000,
        ]);

        return $user;
    }

    public function test_pos_index_renders_inertia(): void
    {
        $this->actingAs($this->pusatKasir())
            ->get(route('hendhys.pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Pos/Index'));
    }

    public function test_pos_sale_debits_pusat_stock_and_returns_receipt_redirect(): void
    {
        $category = ProductCategory::create(['name' => 'Brownies', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Brownies Coklat',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'selling_price' => 25000,
        ]);
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 20, 'unit_id' => $unit->id]);
        $payment = PaymentMethod::create(['name' => 'Tunai', 'type' => 'tunai']);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 50000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 50000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 50000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 2, 'price' => 25000, 'discount' => 0, 'total' => 50000],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Pusat stock 20 → 18 after selling 2.
        $this->assertEquals(18.0, (float) HendhysStockPusat::where('product_id', $product->id)->value('quantity'));
    }

    public function test_reprint_receipt_is_80mm_thermal_and_preview_first(): void
    {
        $user = $this->pusatKasir();
        $category = ProductCategory::create(['name' => 'Brownies', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Brownies',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'selling_price' => 25000,
        ]);
        $trx = HendhysTransaction::create([
            'transaction_number' => 'HTRX-0001', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'subtotal' => 25000, 'discount_amount' => 0, 'tax_amount' => 0,
            'other_costs' => 0, 'grand_total' => 25000, 'status' => 'paid', 'created_by' => $user->id, 'branch_id' => null,
        ]);
        $trx->details()->create(['product_id' => $product->id, 'product_name' => 'Brownies', 'quantity' => 1, 'unit_id' => $unit->id, 'price' => 25000, 'discount_amount' => 0, 'total' => 25000]);
        $trx->payments()->create(['payment_method' => 'cash', 'amount' => 25000]);

        $res = $this->actingAs($user)->get(route('hendhys.transactions.show', $trx->id))->assertOk();
        $res->assertSee('80mm auto', false);                                      // @page struk thermal
        $res->assertDontSee('setTimeout(function() { window.print(); }', false);  // preview-first (tidak auto-cetak)
    }

    public function test_pos_store_requires_items(): void
    {
        $payment = PaymentMethod::create(['name' => 'Tunai', 'type' => 'tunai']);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 0,
                'items'             => [],
            ])
            ->assertStatus(422);
    }
}
