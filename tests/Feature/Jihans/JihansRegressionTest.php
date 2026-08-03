<?php

namespace Tests\Feature\Jihans;

use App\Models\JihansRetailStock;
use App\Models\JihansTransaction;
use App\Models\JihansPendingTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansRegressionTest extends TestCase
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

    private function adminUser(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');
        return $user;
    }

    private function setupProduct($stock = 5, $price = 10000): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        $unit     = Unit::firstOrCreate(['abbreviation' => 'KG'], ['name' => 'Kilogram', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Tepung', 'price' => $price,
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'source_type' => 'purchased', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
        JihansRetailStock::create(['product_id' => $product->id, 'quantity' => $stock, 'unit_id' => $unit->id]);

        return $product;
    }

    public function test_oversell_is_rejected_and_stock_is_untouched()
    {
        $product = $this->setupProduct(5);

        $this->actingAs($this->kasirJihans())
            ->postJson(route('jihans.pos.store'), [
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 100000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 100000,
                'amount_paid'      => 100000,
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => 10, 'price' => 10000, 'total' => 100000]
                ]
            ])
            ->assertStatus(422);

        $this->assertEquals(5, JihansRetailStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_exact_stock_sell_is_successful()
    {
        $product = $this->setupProduct(5);

        $this->actingAs($this->kasirJihans())
            ->postJson(route('jihans.pos.store'), [
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 50000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 50000,
                'amount_paid'      => 50000,
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => 5, 'price' => 10000, 'total' => 50000]
                ]
            ])
            ->assertOk();

        $this->assertEquals(0, JihansRetailStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_oversell_with_duplicate_rows_is_rejected()
    {
        $product = $this->setupProduct(5);

        $this->actingAs($this->kasirJihans())
            ->postJson(route('jihans.pos.store'), [
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 60000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 60000,
                'amount_paid'      => 60000,
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => 3, 'price' => 10000, 'total' => 30000],
                    ['product_id' => $product->id, 'quantity' => 3, 'price' => 10000, 'total' => 30000],
                ]
            ])
            ->assertStatus(422);

        $this->assertEquals(5, JihansRetailStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_inconsistent_row_total_is_rejected()
    {
        $product = $this->setupProduct(5);

        $this->actingAs($this->kasirJihans())
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
                    ['product_id' => $product->id, 'quantity' => 2, 'price' => 10000, 'total' => 10000] // total salah
                ]
            ])
            ->assertStatus(422);
    }

    public function test_void_transaction_returns_stock()
    {
        $product = $this->setupProduct(5);
        $user = $this->kasirJihans();
        
        $trx = JihansTransaction::create([
            'transaction_number' => 'JTRX-TEST', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'subtotal' => 20000, 'discount_amount' => 0, 'tax_amount' => 0,
            'other_costs' => 0, 'grand_total' => 20000, 'status' => 'paid', 'created_by' => $user->id,
        ]);
        $trx->details()->create(['product_id' => $product->id, 'product_name' => 'Tepung', 'quantity' => 2, 'unit_id' => $product->unit_id, 'price' => 10000, 'discount_amount' => 0, 'total' => 20000]);

        JihansRetailStock::where('product_id', $product->id)->update(['quantity' => 3]);

        $admin = $this->adminUser();
        $this->actingAs($admin)
            ->postJson(route('jihans.transactions.void', $trx->id), [
                'reason' => 'Salah input'
            ])
            ->assertRedirect();

        $this->assertEquals('cancelled', $trx->fresh()->status);
        $this->assertEquals(5, JihansRetailStock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_void_transaction_rejected_for_cashier()
    {
        $product = $this->setupProduct(5);
        $user = $this->kasirJihans();
        
        $trx = JihansTransaction::create([
            'transaction_number' => 'JTRX-TEST2', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'subtotal' => 20000, 'discount_amount' => 0, 'tax_amount' => 0,
            'other_costs' => 0, 'grand_total' => 20000, 'status' => 'paid', 'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('jihans.transactions.void', $trx->id), [
                'reason' => 'Salah input'
            ])
            ->assertStatus(403);
    }

    public function test_pending_transaction_not_deleted_on_failed_sale()
    {
        $product = $this->setupProduct(5);
        $user = $this->kasirJihans();

        $pending = JihansPendingTransaction::create([
            'pending_number' => 'PEND-JIH-01',
            'date' => today(),
            'created_by' => $user->id,
            'entity' => 'jihans',
            'customer_name' => 'Umum',
            'customer_type' => 'Pelanggan Individual',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('jihans.pos.store'), [
                'pending_id'       => $pending->id,
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 100000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 100000,
                'amount_paid'      => 100000,
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => 10, 'price' => 10000, 'total' => 100000] // fail
                ]
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('jihans_pending_transactions', ['id' => $pending->id]);
    }

    public function test_pending_transaction_deleted_on_successful_sale()
    {
        $product = $this->setupProduct(5);
        $user = $this->kasirJihans();

        $pending = JihansPendingTransaction::create([
            'pending_number' => 'PEND-JIH-02',
            'date' => today(),
            'created_by' => $user->id,
            'entity' => 'jihans',
            'customer_name' => 'Umum',
            'customer_type' => 'Pelanggan Individual',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('jihans.pos.store'), [
                'pending_id'       => $pending->id,
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
                    ['product_id' => $product->id, 'quantity' => 2, 'price' => 10000, 'total' => 20000]
                ]
            ])
            ->assertOk();

        $this->assertDatabaseMissing('jihans_pending_transactions', ['id' => $pending->id]);
    }

    public function test_fractional_quantity_is_rejected_in_pos()
    {
        $product = $this->setupProduct(5);

        $this->actingAs($this->kasirJihans())
            ->postJson(route('jihans.pos.store'), [
                'transaction_date' => now()->toDateString(),
                'customer_name'    => 'Pelanggan Umum',
                'customer_type'    => 'Pelanggan Retail',
                'ppn_type'         => 'none',
                'ppn_rate'         => 0,
                'subtotal'         => 25000,
                'discount_amount'  => 0,
                'tax_amount'       => 0,
                'other_costs'      => 0,
                'grand_total'      => 25000,
                'amount_paid'      => 25000,
                'items'            => [
                    ['product_id' => $product->id, 'quantity' => 2.5, 'price' => 10000, 'total' => 25000]
                ]
            ])
            ->assertStatus(422)
            // Wajib menyebut field-nya. Tanpa ini test tetap hijau walau aturan
            // 'integer' dicabut — terbukti lewat mutation testing: yang menahan
            // pecahan justru pemeriksaan konsistensi total, bukan validasinya.
            ->assertJsonValidationErrors(['items.0.quantity']);
    }
}
