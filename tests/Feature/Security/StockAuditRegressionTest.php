<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Jihans\TransactionController;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\GudangReturn;
use App\Models\HendhysProduction;
use App\Models\HendhysStockPusat;
use App\Models\JihansTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TransferOut;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Prevent the stock and payment failures demonstrated during the audit. */
class StockAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function branch(string $name, string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => $name, 'name' => $name, 'entity' => 'hendhys', 'type' => $type, 'is_active' => true]);
    }

    private function product(string $scope = 'all', string $source = 'purchased'): Product
    {
        $unit = Unit::create(['name' => 'Pcs'.fake()->unique()->numberBetween(1, 99999), 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $cat = ProductCategory::create(['name' => 'Produk'.fake()->unique()->numberBetween(1, 99999), 'entity_scope' => 'all']);

        return Product::create(['code' => fake()->unique()->numerify('P-#####'), 'name' => 'Produk'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('######'), 'unit_id' => $unit->id, 'category_id' => $cat->id,
            'product_type' => 'INV', 'source_type' => $source, 'entity_scope' => $scope, 'status' => 'active', 'selling_price' => 1000]);
    }

    public function test_receipt_rejects_confirmation_from_the_wrong_hendhys_branch(): void
    {
        $a = $this->branch('A');
        $b = $this->branch('B');
        $cashier = $this->user('kasir_hendhys', 'hendhys', $a);
        $p = $this->product();
        $transfer = TransferOut::create(['transfer_number' => 'AUDIT-TRF', 'to_entity' => 'hendhys', 'branch_id' => $b->id, 'date' => now()->toDateString(), 'status' => 'sent', 'created_by' => $cashier->id]);
        $detail = $transfer->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 5, 'hpp_price' => 1, 'total' => 5]);
        $this->actingAs($cashier)->get(route('hendhys.transfer-requests.receive-form-gudang', $transfer->id))->assertForbidden();
        $this->post(route('hendhys.transfer-requests.receive-gudang', $transfer->id), ['quantity_bagus' => [$detail->id => 5], 'quantity_rusak' => [$detail->id => 0]])->assertForbidden();
        $this->assertDatabaseCount('hendhys_stock_branch', 0);
        $this->assertSame('sent', $transfer->fresh()->status);
    }

    public function test_cancelled_po_cannot_be_received_or_reactivated(): void
    {
        $admin = $this->user('super_admin');
        $p = $this->product();
        $supplierA = Supplier::create(['code' => 'A', 'name' => 'A', 'is_active' => true]);
        $supplierB = Supplier::create(['code' => 'B', 'name' => 'B', 'is_active' => true]);
        $po = PurchaseOrder::create(['po_number' => 'AUDIT-PO', 'supplier_id' => $supplierA->id, 'date' => now()->toDateString(), 'status' => 'cancelled', 'total_amount' => 5, 'created_by' => $admin->id]);
        $po->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_ordered' => 5, 'quantity_received' => 0, 'price' => 1, 'total' => 5]);
        $this->actingAs($admin)->post(route('gudang.receiving.store'), ['po_id' => $po->id, 'supplier_id' => $supplierB->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_bagus' => 5, 'quantity_rusak' => 0, 'hpp_price' => 1]]])
            ->assertRedirect()->assertSessionHasErrors('po_id');
        $this->assertSame('cancelled', $po->fresh()->status);
        $this->assertDatabaseCount('gudang_receivings', 0);
        $this->assertDatabaseCount('jihans_gudang_stock', 0);
    }

    public function test_return_receiving_cannot_mutate_another_document(): void
    {
        $admin = $this->user('super_admin');
        $p = $this->product();
        $attrs = ['from_entity' => 'jihans', 'date' => now()->toDateString(), 'status' => 'sent', 'created_by' => $admin->id];
        $a = GudangReturn::create($attrs + ['return_number' => 'AUDIT-A']);
        $b = GudangReturn::create($attrs + ['return_number' => 'AUDIT-B']);
        $a->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 2, 'condition' => 'Bagus (Siap Jual)']);
        $otherDetail = $b->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 4, 'condition' => 'Bagus (Siap Jual)']);
        $this->actingAs($admin)->post(route('gudang.returns.receive', $a->id), ['items' => [$otherDetail->id => ['received_quantity' => 4, 'condition' => 'Bagus (Siap Jual)']]])
            ->assertRedirect()->assertSessionHasErrors('items');
        $this->assertSame('sent', $a->fresh()->status);
        $this->assertSame('sent', $b->fresh()->status);
        $this->assertNull($otherDetail->fresh()->received_quantity);
        $this->assertDatabaseCount('jihans_gudang_stock', 0);
    }

    public function test_replaying_prediction_conversion_preserves_one_actual_and_one_stock_credit(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat'));
        $p = $this->product('hendhys', 'produced');
        $prediction = HendhysProduction::create(['production_number' => 'AUDIT-PRED', 'date' => now()->toDateString(), 'type' => 'prediksi', 'created_by' => $admin->id]);
        $prediction->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => 10]);
        $payload = ['prediction_id' => $prediction->id, 'date' => now()->toDateString(), 'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => 10]]];
        $this->actingAs($admin);
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('hendhys.productions.store'), $payload)->assertRedirect()->assertSessionHas('success');
        }
        $this->assertSame(1, HendhysProduction::where('type', 'aktual')->count());
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 10]);
    }

    public function test_two_stale_void_requests_restore_stock_only_once(): void
    {
        $cashier = $this->user('kasir_jihans', 'jihans');
        $admin = $this->user('admin_jihans', 'jihans');
        $p = $this->product('jihans');
        CashierShift::create(['user_id' => $cashier->id, 'entity' => 'jihans', 'status' => 'open', 'opened_at' => now()->subMinute(), 'starting_cash' => 0]);
        $transaction = JihansTransaction::create(['transaction_number' => 'AUDIT-VOID', 'date' => now()->toDateString(), 'time' => now()->format('H:i:s'), 'status' => 'paid', 'grand_total' => 5000, 'created_by' => $cashier->id]);
        $transaction->details()->create(['product_id' => $p->id, 'product_name' => $p->name, 'unit_id' => $p->unit_id, 'quantity' => 5, 'price' => 1000, 'total' => 5000]);
        $firstSnapshot = $transaction->fresh();
        $secondSnapshot = $transaction->fresh();
        $this->actingAs($admin);
        $controller = app(TransactionController::class);
        foreach ([$firstSnapshot, $secondSnapshot] as $snapshot) {
            $request = Request::create('/audit-void', 'POST', ['reason' => 'Bukti audit permintaan bersamaan']);
            $request->setLaravelSession(app('session.store'));
            $controller->void($request, $snapshot);
        }
        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertDatabaseHas('jihans_retail_stock', ['product_id' => $p->id, 'quantity' => 5]);
    }

    public function test_hendhys_production_rejects_ineligible_products_and_units(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat'));
        $p = $this->product('jihans', 'purchased');
        $wrongUnit = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);
        $this->actingAs($admin)->post(route('hendhys.productions.store'), ['request_key' => (string) Str::uuid(), 'date' => now()->toDateString(), 'items' => [
            ['product_id' => $p->id, 'unit_id' => $wrongUnit->id, 'quantity_produced' => 10],
        ]])->assertRedirect()->assertSessionHasErrors('items');
        $this->assertDatabaseCount('hendhys_stock_pusat', 0);
        $this->assertDatabaseCount('production_lots', 0);
    }

    public function test_hendhys_payment_rejects_conflicting_cash_and_transfer_types(): void
    {
        $centre = $this->branch('CENTRE', 'pusat');
        $cashier = $this->user('kasir_hendhys', 'hendhys', $centre);
        $p = $this->product('hendhys');
        CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $centre->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        HendhysStockPusat::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 1]);
        $cash = PaymentMethod::create(['name' => 'Tunai', 'type' => 'tunai', 'is_active' => true]);
        $this->actingAs($cashier)->postJson(route('hendhys.pos.store'), [
            'checkout_key' => (string) Str::uuid(), 'payment_type' => 'transfer', 'payment_method_id' => $cash->id,
            'amount_paid' => 1000, 'subtotal' => 1000, 'grand_total' => 1000,
            'items' => [['product_id' => $p->id, 'quantity' => 1, 'price' => 1000, 'total' => 1000]],
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_method_id');
        $this->assertDatabaseCount('hendhys_transactions', 0);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 1]);
    }
}
