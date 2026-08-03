<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransaction;
use App\Models\PaymentMethod;
use App\Models\HendhysPendingTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HendhysRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function pusatKasir(): User
    {
        $branch = Branch::firstOrCreate(['code' => 'HND-PST'], ['name' => 'Gudang Hendhys', 'type' => 'pusat', 'is_active' => true]);
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

    private function adminUser(): User
    {
        $branch = Branch::firstOrCreate(['code' => 'HND-PST'], ['name' => 'Gudang Hendhys', 'type' => 'pusat', 'is_active' => true]);
        Role::findOrCreate('admin_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('admin_hendhys');
        return $user;
    }

    private function setupProduct($stock = 5, $price = 25000): array
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Brownies'], ['entity_scope' => 'all']);
        $unit     = Unit::firstOrCreate(['abbreviation' => 'PCS'], ['name' => 'Pieces', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Brownies Coklat',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'selling_price' => $price,
        ]);
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => $stock, 'unit_id' => $unit->id]);
        $payment = PaymentMethod::firstOrCreate(['type' => 'tunai'], ['name' => 'Tunai']);

        return [$product, $payment];
    }

    // 1. Oversell stok
    public function test_oversell_is_rejected_and_stock_is_untouched()
    {
        [$product, $payment] = $this->setupProduct(5);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 250000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 250000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 250000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 10, 'price' => 25000, 'discount' => 0, 'total' => 250000],
                ],
            ])
            ->assertStatus(422);

        $this->assertEquals(5, HendhysStockPusat::where('product_id', $product->id)->value('quantity'));
    }

    public function test_exact_stock_sell_is_successful()
    {
        [$product, $payment] = $this->setupProduct(5);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 125000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 125000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 125000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 5, 'price' => 25000, 'discount' => 0, 'total' => 125000],
                ],
            ])
            ->assertOk();

        $this->assertEquals(0, HendhysStockPusat::where('product_id', $product->id)->value('quantity'));
    }

    public function test_oversell_with_duplicate_rows_is_rejected()
    {
        [$product, $payment] = $this->setupProduct(5);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 150000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 150000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 150000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 3, 'price' => 25000, 'discount' => 0, 'total' => 75000],
                    ['product_id' => $product->id, 'quantity' => 3, 'price' => 25000, 'discount' => 0, 'total' => 75000],
                ],
            ])
            ->assertStatus(422);

        $this->assertEquals(5, HendhysStockPusat::where('product_id', $product->id)->value('quantity'));
    }

    // 2. Verifikasi total POS
    public function test_falsified_grand_total_is_rejected()
    {
        [$product, $payment] = $this->setupProduct(5);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 1,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 1,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 1,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 25000, 'discount' => 0, 'total' => 25000],
                ],
            ])
            ->assertStatus(422);
    }

    // 3. Void/pembatalan transaksi
    public function test_void_transaction_returns_stock()
    {
        [$product, $payment] = $this->setupProduct(5);
        $user = $this->pusatKasir();
        
        $trx = HendhysTransaction::create([
            'transaction_number' => 'HTRX-TEST', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'subtotal' => 25000, 'discount_amount' => 0, 'tax_amount' => 0,
            'other_costs' => 0, 'grand_total' => 25000, 'status' => 'paid', 'created_by' => $user->id, 'branch_id' => null,
        ]);
        $trx->details()->create(['product_id' => $product->id, 'product_name' => 'Brownies', 'quantity' => 2, 'unit_id' => $product->unit_id, 'price' => 25000, 'discount_amount' => 0, 'total' => 50000]);

        // Mock current stock to 3 (assuming 2 were sold earlier)
        HendhysStockPusat::where('product_id', $product->id)->update(['quantity' => 3]);

        $admin = $this->adminUser();
        $this->actingAs($admin)
            ->postJson(route('hendhys.transactions.void', $trx->id), [
                'reason' => 'Salah input'
            ])
            ->assertRedirect();

        $this->assertEquals('cancelled', $trx->fresh()->status);
        $this->assertEquals(5, HendhysStockPusat::where('product_id', $product->id)->value('quantity'));
    }

    public function test_void_transaction_rejected_for_cashier()
    {
        [$product, $payment] = $this->setupProduct(5);
        $user = $this->pusatKasir();
        
        $trx = HendhysTransaction::create([
            'transaction_number' => 'HTRX-TEST2', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'subtotal' => 25000, 'discount_amount' => 0, 'tax_amount' => 0,
            'other_costs' => 0, 'grand_total' => 25000, 'status' => 'paid', 'created_by' => $user->id, 'branch_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson(route('hendhys.transactions.void', $trx->id), [
                'reason' => 'Salah input'
            ])
            ->assertStatus(403);
    }

    // 4. Resume transaksi pending
    public function test_pending_transaction_not_deleted_on_failed_sale()
    {
        [$product, $payment] = $this->setupProduct(5);
        $user = $this->pusatKasir();

        $pending = HendhysPendingTransaction::create([
            'pending_number' => 'PEND-HND-01',
            'date' => today(),
            'created_by' => $user->id,
            'branch_id' => $user->branch_id,
            'entity' => 'hendhys',
            'customer_name' => 'Umum',
            'customer_type' => 'Pelanggan Individual',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('hendhys.pos.store'), [
                'pending_id'        => $pending->id,
                'payment_method_id' => $payment->id,
                'amount_paid'       => 250000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 250000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 250000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 10, 'price' => 25000, 'discount' => 0, 'total' => 250000],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('hendhys_pending_transactions', ['id' => $pending->id]);
    }

    public function test_pending_transaction_deleted_on_successful_sale()
    {
        [$product, $payment] = $this->setupProduct(5);
        $user = $this->pusatKasir();

        $pending = HendhysPendingTransaction::create([
            'pending_number' => 'PEND-HND-02',
            'date' => today(),
            'created_by' => $user->id,
            'branch_id' => $user->branch_id,
            'entity' => 'hendhys',
            'customer_name' => 'Umum',
            'customer_type' => 'Pelanggan Individual',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->postJson(route('hendhys.pos.store'), [
                'pending_id'        => $pending->id,
                'payment_method_id' => $payment->id,
                'amount_paid'       => 25000,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 25000,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 25000,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 25000, 'discount' => 0, 'total' => 25000],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('hendhys_pending_transactions', ['id' => $pending->id]);
    }

    // 6. Kuantitas wajib integer
    public function test_fractional_quantity_is_rejected_in_pos()
    {
        [$product, $payment] = $this->setupProduct(5);

        $this->actingAs($this->pusatKasir())
            ->postJson(route('hendhys.pos.store'), [
                'payment_method_id' => $payment->id,
                'amount_paid'       => 62500,
                'customer_name'     => 'Umum',
                'customer_type'     => 'Pelanggan Individual',
                'ppn_type'          => 'none',
                'subtotal'          => 62500,
                'discount_amount'   => 0,
                'tax_amount'        => 0,
                'other_costs'       => 0,
                'grand_total'       => 62500,
                'items'             => [
                    ['product_id' => $product->id, 'quantity' => 2.5, 'price' => 25000, 'discount' => 0, 'total' => 62500],
                ],
            ])
            ->assertStatus(422)
            // Wajib menyebut field-nya. Tanpa ini test tetap hijau walau aturan
            // 'integer' dicabut — terbukti lewat mutation testing: yang menahan
            // pecahan justru pemeriksaan grand_total, bukan validasinya.
            ->assertJsonValidationErrors(['items.0.quantity']);
    }
}
