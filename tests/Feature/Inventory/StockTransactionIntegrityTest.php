<?php

namespace Tests\Feature\Inventory;

use App\Http\Controllers\Hendhys\TransactionController;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransferToBranch;
use App\Models\JihansProductionSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TransferOut;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\CheckoutService;
use App\Services\ReturnQuarantine;
use App\Services\SaleShiftGuard;
use App\Services\TransferReceiving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Regression coverage for inventory and cashier integrity. */
class StockTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function branch(string $code, string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => $code, 'name' => $code, 'entity' => 'hendhys', 'type' => $type, 'is_active' => true]);
    }

    private function cashier(Branch $branch): User
    {
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');

        return $user;
    }

    private function product(): Product
    {
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::create(['name' => 'Audit category', 'entity_scope' => 'all']);

        return Product::create(['code' => 'REVIEW-P', 'name' => 'Audit product', 'unit_id' => $unit->id,
            'category_id' => $category->id, 'entity_scope' => 'all', 'source_type' => 'purchased',
            'status' => 'active', 'product_type' => 'INV', 'selling_price' => 1000]);
    }

    private function shipment(Branch $branch, User $user, Product $product, int $quantity): TransferOut
    {
        $transfer = TransferOut::create(['transfer_number' => 'REVIEW-TRF', 'to_entity' => 'hendhys',
            'branch_id' => $branch->id, 'date' => now()->toDateString(), 'status' => 'sent', 'created_by' => $user->id]);
        $transfer->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id,
            'quantity' => $quantity, 'hpp_price' => 1, 'total' => $quantity]);

        return $transfer;
    }

    private function admin(Branch $branch, string $entity = 'hendhys'): User
    {
        Role::findOrCreate('admin_'.$entity, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch->id]);
        $user->assignRole('admin_'.$entity);

        return $user;
    }

    private function paymentPayload(Product $product, array $overrides = []): array
    {
        return array_replace(['checkout_key' => (string) Str::uuid(), 'payment_type' => 'tunai', 'amount_paid' => 1000,
            'subtotal' => 1000, 'grand_total' => 1000,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000, 'total' => 1000]]], $overrides);
    }

    public function test_legacy_receipt_quarantines_damaged_goods_without_sellable_credit(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 5);
        $detail = $transfer->details()->firstOrFail();
        $this->actingAs($user)->post(route('hendhys.gudang-transfers.receive', $transfer->id), [
            'received_quantities' => [$detail->id => 5], 'kondisi' => [$detail->id => 'rusak'],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('gudang_transfer_out_details', ['id' => $detail->id, 'kondisi' => 'rusak']);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 0, 'quantity_return' => 5]);
        $this->assertDatabaseHas('return_quarantine_entries', ['product_id' => $product->id, 'quantity' => 5, 'condition' => 'damaged']);
    }

    public function test_shortfall_is_recorded_without_inventing_warehouse_stock_or_a_received_return(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 10);
        $detail = $transfer->details()->firstOrFail();
        $this->actingAs($user)->post(route('hendhys.gudang-transfers.receive', $transfer->id), [
            'received_quantities' => [$detail->id => 7], 'kondisi' => [$detail->id => 'kurang'],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('jihans_gudang_stock', 0);
        $this->assertDatabaseCount('gudang_returns', 0);
        $this->assertDatabaseHas('receipt_confirmation_details', ['condition' => 'kurang', 'actual_qty' => 3]);
    }

    public function test_unified_receipt_accepts_good_damaged_and_missing_goods_separately(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 10);
        $detail = $transfer->details()->firstOrFail();
        $this->actingAs($user)->post(route('hendhys.transfer-requests.receive-gudang', $transfer->id), [
            'quantity_bagus' => [$detail->id => 7], 'quantity_rusak' => [$detail->id => 0],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('received', $transfer->fresh()->status);
        $this->assertDatabaseHas('receipt_confirmation_details', ['condition' => 'kurang', 'actual_qty' => 3]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $product->id, 'quantity' => 7]);
    }

    public function test_non_cash_overpayment_is_rejected_without_a_sale_or_stock_change(): void
    {
        $branch = $this->branch('REVIEW-CENTRE', 'pusat');
        $user = $this->cashier($branch);
        $product = $this->product();
        $shift = CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => 'hendhys',
            'status' => 'open', 'opened_at' => now()->subMinute(), 'starting_cash' => 0]);
        HendhysStockPusat::create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 1]);
        $this->actingAs($user)->postJson(route('hendhys.pos.store'), [
            'checkout_key' => (string) Str::uuid(), 'payment_type' => 'transfer',
            'amount_paid' => 2000, 'subtotal' => 1000, 'grand_total' => 1000,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000, 'total' => 1000]],
        ])->assertUnprocessable()->assertJsonValidationErrors('amount_paid');
        $this->assertDatabaseCount('hendhys_transactions', 0);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_new_branch_shift_excludes_sales_from_another_branch(): void
    {
        $a = $this->branch('REVIEW-A');
        $b = $this->branch('REVIEW-B');
        $user = $this->cashier($a);
        CashierShift::create(['user_id' => $user->id, 'branch_id' => $a->id, 'entity' => 'hendhys',
            'status' => 'closed', 'opened_at' => now()->subHours(2), 'closed_at' => now()->subHour(), 'starting_cash' => 0]);
        HendhysTransaction::create(['transaction_number' => 'REVIEW-SALE', 'branch_id' => $a->id,
            'date' => now()->toDateString(), 'time' => '08:30:00', 'status' => 'paid', 'grand_total' => 1000,
            'created_by' => $user->id, 'created_at' => now()->subMinutes(90)]);
        $second = CashierShift::create(['user_id' => $user->id, 'branch_id' => $b->id, 'entity' => 'hendhys',
            'status' => 'open', 'opened_at' => now()->subMinutes(30), 'starting_cash' => 0]);
        $this->assertEquals(0, $second->calculateSalesSummary()['total']);
        $this->assertSame(0, HendhysTransaction::where('branch_id', $b->id)->count());
    }

    public function test_receiving_two_stale_snapshots_creates_one_balance_and_one_bast(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 5);
        $detail = $transfer->details()->firstOrFail();
        $first = $transfer->fresh();
        $second = $transfer->fresh();
        $data = ['received_quantities' => [$detail->id => 5], 'kondisi' => [$detail->id => 'baik']];
        foreach ([$first, $second] as $snapshot) {
            app(TransferReceiving::class)->receive($snapshot, $data, $user);
        }
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $product->id, 'quantity' => 5]);
        $this->assertDatabaseCount('receipt_confirmations', 1);
        $this->assertDatabaseCount('hendhys_stock_in', 1);
    }

    public function test_receiving_rejects_foreign_details_and_over_receiving_without_side_effects(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 5);
        $detail = $transfer->details()->firstOrFail();
        $this->actingAs($user);
        foreach ([[$detail->id => 6], [$detail->id + 1000 => 5]] as $quantities) {
            $this->post(route('hendhys.gudang-transfers.receive', $transfer->id), ['received_quantities' => $quantities])
                ->assertRedirect()->assertSessionHasErrors('received_quantities');
        }
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertDatabaseCount('hendhys_stock_branch', 0);
        $this->assertSame('sent', $transfer->fresh()->status);
    }

    public function test_receipt_audit_failure_rolls_back_stock_damage_and_document(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = $this->shipment($branch, $user, $product, 5);
        $detail = $transfer->details()->firstOrFail();
        $this->mock(ActivityLogService::class, fn ($mock) => $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit unavailable')));
        $this->actingAs($user)->postJson(route('hendhys.transfer-requests.receive-gudang', $transfer->id), [
            'quantity_bagus' => [$detail->id => 3], 'quantity_rusak' => [$detail->id => 2],
        ])->assertStatus(500);
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->assertDatabaseCount('hendhys_stock_branch', 0);
        $this->assertSame('sent', $transfer->fresh()->status);
    }

    public function test_centre_shipment_separates_damage_and_shortfall_without_restoring_centre_stock(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        $transfer = HendhysTransferToBranch::create(['transfer_number' => 'REVIEW-CENTRE', 'branch_id' => $branch->id,
            'date' => today(), 'status' => 'sent', 'created_by' => $user->id]);
        $detail = $transfer->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 5]);
        $this->actingAs($user)->post(route('hendhys.transfer-to-branch.receive', $transfer), [
            'received_quantities' => [$detail->id => 3], 'kondisi' => [$detail->id => 'rusak'],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $product->id, 'quantity' => 0, 'quantity_return' => 3]);
        $this->assertDatabaseHas('receipt_confirmation_details', ['condition' => 'kurang', 'actual_qty' => 2]);
        $this->assertDatabaseCount('hendhys_stock_pusat', 0);
        $this->assertDatabaseCount('hendhys_returns_from_branch', 0);
        $this->assertSame(1, app(ReturnQuarantine::class)->visible($user)->count());
    }

    public function test_hendhys_void_rechecks_stale_state_and_logs_only_one_reversal(): void
    {
        $branch = $this->branch('REVIEW-CENTRE', 'pusat');
        $user = $this->cashier($branch);
        $product = $this->product();
        CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        HendhysStockPusat::create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 1]);
        $this->actingAs($user)->postJson(route('hendhys.pos.store'), $this->paymentPayload($product))->assertOk();
        $sale = HendhysTransaction::firstOrFail();
        $snapshots = [$sale->fresh(), $sale->fresh()];
        $this->actingAs($this->admin($branch));
        foreach ($snapshots as $snapshot) {
            $request = Request::create('/void', 'POST', ['reason' => 'Koreksi audit transaksi']);
            $request->setLaravelSession(app('session.store'));
            app(TransactionController::class)->void($request, $snapshot);
        }
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $product->id, 'quantity' => 1]);
        $this->assertSame(1, DB::table('master_activity_logs')->where('action', 'void')->count());
    }

    public function test_same_second_shifts_keep_explicit_sales_separate_in_summaries_and_details(): void
    {
        $branch = $this->branch('REVIEW-CENTRE', 'pusat');
        $user = $this->cashier($branch);
        $product = $this->product();
        HendhysStockPusat::create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 2]);
        $this->actingAs($user)->post(route('hendhys.shifts.open'), ['starting_cash' => 0])->assertSessionHas('success');
        $first = CashierShift::firstOrFail();
        $this->postJson(route('hendhys.pos.store'), $this->paymentPayload($product))->assertOk();
        $this->post(route('hendhys.shifts.close'), ['actual_cash' => 1000])->assertSessionHas('success');
        $this->post(route('hendhys.shifts.open'), ['starting_cash' => 0])->assertSessionHas('success');
        $second = CashierShift::latest('id')->firstOrFail();
        $this->postJson(route('hendhys.pos.store'), $this->paymentPayload($product))->assertOk();
        foreach ([$first->fresh(), $second] as $shift) {
            $this->assertEquals(1000, $shift->calculateSalesSummary()['total']);
            $this->assertEquals(1000, $shift->calculatePaymentSummary()['tunai']);
            $this->getJson(route('hendhys.shifts.details', $shift))->assertOk()->assertJsonCount(1, 'transactions')->assertJsonPath('payment_summary.tunai', fn ($value) => (float) $value === 1000.0);
        }
        $this->assertDatabaseHas('master_cashier_shifts', ['id' => $first->id, 'expected_cash' => 1000, 'discrepancy' => 0]);
        $this->assertSame(2, HendhysTransaction::whereNotNull('shift_id')->distinct()->count('shift_id'));
    }

    public function test_closed_shift_blocks_late_checkout_and_stale_sale_mutations_at_the_service_boundary(): void
    {
        $branch = $this->branch('REVIEW-CENTRE', 'pusat');
        $user = $this->cashier($branch);
        $product = $this->product();
        $shift = CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        HendhysStockPusat::create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 1]);
        $this->actingAs($user)->postJson(route('hendhys.pos.store'), $this->paymentPayload($product))->assertOk();
        $sale = HendhysTransaction::firstOrFail();
        $shift->update(['status' => 'closed', 'closed_at' => now()]);
        foreach (['checkout', 'edit'] as $operation) {
            try {
                DB::transaction(fn () => $operation === 'checkout'
                    ? app(CheckoutService::class)->run(HendhysTransaction::class, $this->paymentPayload($product), fn () => throw new \LogicException('Should not create a sale'))
                    : app(SaleShiftGuard::class)->sale($sale, 'hendhys'));
                $this->fail('A closed shift accepted a mutation.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertDatabaseCount('hendhys_transactions', 1);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $product->id, 'quantity' => 0]);
    }

    public function test_production_key_replays_once_and_rejects_changed_payload_and_wrong_unit(): void
    {
        $branch = $this->branch('REVIEW-CENTRE', 'pusat');
        $admin = $this->admin($branch);
        $product = $this->product();
        $product->update(['source_type' => 'produced']);
        $data = ['request_key' => (string) Str::uuid(), 'date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_produced' => 10]]];
        $this->actingAs($admin);
        foreach ([1, 2] as $_) {
            $this->post(route('hendhys.productions.store'), $data)->assertSessionHas('success');
        }
        $changed = $data;
        $changed['items'][0]['quantity_produced'] = 20;
        $this->post(route('hendhys.productions.store'), $changed)->assertSessionHasErrors('request_key');
        $wrongUnit = Unit::create(['name' => 'Box', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        $changed['request_key'] = (string) Str::uuid();
        $changed['items'][0]['unit_id'] = $wrongUnit->id;
        $this->post(route('hendhys.productions.store'), $changed)->assertSessionHasErrors('items.0.unit_id');
        $this->assertDatabaseCount('hendhys_productions', 1);
        $this->assertDatabaseCount('production_submissions', 1);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $product->id, 'quantity' => 10]);
    }

    public function test_jihans_prediction_replay_credits_warehouse_once_and_rejects_changed_actualization(): void
    {
        $branch = $this->branch('REVIEW-J');
        $branch->update(['entity' => 'jihans']);
        $admin = $this->admin($branch, 'jihans');
        $product = $this->product();
        $product->update(['source_type' => 'produced']);
        $prediction = JihansProductionSession::create(['session_number' => 'REVIEW-PRED', 'date' => today(), 'type' => 'prediksi', 'created_by' => $admin->id]);
        $prediction->details()->create(['product_id' => $product->id, 'quantity' => 10]);
        $payload = ['prediction_id' => $prediction->id, 'date' => today()->toDateString(), 'details' => [['product_id' => $product->id, 'quantity' => 10]]];
        $this->actingAs($admin);
        foreach ([1, 2] as $_) {
            $this->post(route('jihans.production.store'), $payload)->assertSessionHas('success');
        }
        $payload['details'][0]['quantity'] = 20;
        $this->post(route('jihans.production.store'), $payload)->assertSessionHasErrors('prediction_id');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 10]);
        $this->assertDatabaseCount('jihans_production_sessions', 1);
    }

    public function test_super_admin_force_receipt_remains_available_and_cannot_duplicate_stock(): void
    {
        $branch = $this->branch('REVIEW-A');
        $user = $this->cashier($branch);
        $product = $this->product();
        Role::findOrCreate('super_admin', 'web');
        $superAdmin = User::factory()->create(['entity' => 'all', 'branch_id' => null]);
        $superAdmin->assignRole('super_admin');
        $transfer = \App\Models\HendhysTransferToBranch::create(['transfer_number' => 'REVIEW-FORCE', 'branch_id' => $branch->id,
            'date' => today(), 'status' => 'sent', 'created_by' => $user->id]);
        $transfer->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 5]);
        $this->actingAs($superAdmin);
        foreach ([1, 2] as $_) {
            $this->post(route('hendhys.transfer-to-branch.force-receive', $transfer))->assertRedirect()->assertSessionHas('success');
        }
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $product->id, 'quantity' => 5]);
        $this->assertDatabaseCount('receipt_confirmations', 1);
    }
}
