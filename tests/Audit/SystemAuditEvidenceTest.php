<?php

namespace Tests\Audit;

use App\Models\{Branch, CashierShift, GudangReturn, HendhysProduction, HendhysStockPusat, Product, ProductCategory, PurchaseOrder, Supplier, TransferOut, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Isolated evidence of audit findings, not safety regression tests. Assertions document current unsafe behavior. */
class SystemAuditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]); $user->assignRole($role);
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

    public function test_receipt_endpoint_accepts_confirmation_from_the_wrong_hendhys_branch(): void
    {
        $a = $this->branch('A'); $b = $this->branch('B'); $cashier = $this->user('kasir_hendhys', 'hendhys', $a); $p = $this->product();
        $transfer = TransferOut::create(['transfer_number' => 'AUDIT-TRF', 'to_entity' => 'hendhys', 'branch_id' => $b->id, 'date' => now()->toDateString(), 'status' => 'sent', 'created_by' => $cashier->id]);
        $detail = $transfer->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 5, 'hpp_price' => 1, 'total' => 5]);
        $this->actingAs($cashier)->get(route('hendhys.transfer-requests.receive-form-gudang', $transfer->id))->assertOk();
        $this->post(route('hendhys.transfer-requests.receive-gudang', $transfer->id), ['quantity_bagus' => [$detail->id => 5], 'quantity_rusak' => [$detail->id => 0]])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'product_id' => $p->id, 'quantity' => 5]);
        $this->assertSame('received', $transfer->fresh()->status);
    }

    public function test_assigned_branch_admin_can_switch_to_production_centre_without_new_assignment(): void
    {
        $a = $this->branch('A'); $centre = $this->branch('CENTRE', 'pusat'); $admin = $this->user('admin_hendhys', 'hendhys', $a);
        $this->actingAs($admin)->get(route('hendhys.productions.create'))->assertForbidden();
        $this->post(route('select-branch.post'), ['branch_id' => $centre->id])->assertRedirect()->assertSessionHas('active_branch_id', $centre->id);
        $this->get(route('hendhys.productions.create'))->assertOk();
        $this->assertSame($a->id, $admin->fresh()->branch_id);
    }

    public function test_hendhys_excel_import_can_modify_a_jihans_only_product(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat')); $p = $this->product('jihans');
        $csv = "Template\n\n\n\nnama_produk,barcode,kategori,satuan,harga_jual,entitas_scope\n{$p->name},{$p->barcode},Umum,Pcs,9999,hendhys\n";
        $this->actingAs($admin)->post(route('hendhys.master.products.import'), ['file' => UploadedFile::fake()->createWithContent('products.csv', $csv)])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('hendhys', $p->fresh()->entity_scope);
        $this->assertEquals(9999, $p->fresh()->selling_price);
    }

    public function test_photo_url_accepts_local_file_and_publishes_its_contents_without_image_validation(): void
    {
        Storage::fake('public'); $admin = $this->user('super_admin');
        $supplier = Supplier::create(['code' => 'AUDIT-SUP', 'name' => 'Supplier', 'is_active' => true]); $p = $this->product();
        $markerPath = tempnam(sys_get_temp_dir(), 'hejira-audit-marker-'); file_put_contents($markerPath, 'SAFE_AUDIT_MARKER_ONLY');
        try {
            $this->actingAs($admin)->post(route('gudang.receiving.store'), [
                'supplier_id' => $supplier->id, 'date' => now()->toDateString(),
                'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_bagus' => 1, 'quantity_rusak' => 0, 'hpp_price' => 1]],
                'photo_urls' => ['file://'.$markerPath],
            ])->assertRedirect()->assertSessionHas('success');
            $photo = \App\Models\ReceivingPhoto::firstOrFail();
            $this->assertSame('SAFE_AUDIT_MARKER_ONLY', Storage::disk('public')->get($photo->path));
        } finally { unlink($markerPath); }
    }

    public function test_cancelled_po_can_be_received_and_reactivated(): void
    {
        $admin = $this->user('super_admin'); $p = $this->product();
        $supplierA = Supplier::create(['code' => 'A', 'name' => 'A', 'is_active' => true]); $supplierB = Supplier::create(['code' => 'B', 'name' => 'B', 'is_active' => true]);
        $po = PurchaseOrder::create(['po_number' => 'AUDIT-PO', 'supplier_id' => $supplierA->id, 'date' => now()->toDateString(), 'status' => 'cancelled', 'total_amount' => 5, 'created_by' => $admin->id]);
        $po->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_ordered' => 5, 'quantity_received' => 0, 'price' => 1, 'total' => 5]);
        $this->actingAs($admin)->post(route('gudang.receiving.store'), ['po_id' => $po->id, 'supplier_id' => $supplierB->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_bagus' => 5, 'quantity_rusak' => 0, 'hpp_price' => 1]]])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('received', $po->fresh()->status);
        $this->assertDatabaseHas('gudang_receivings', ['po_id' => $po->id, 'supplier_id' => $supplierA->id]);
    }

    public function test_return_receiving_can_mutate_details_from_another_return_document(): void
    {
        $admin = $this->user('super_admin'); $p = $this->product();
        $attrs = ['from_entity' => 'jihans', 'date' => now()->toDateString(), 'status' => 'sent', 'created_by' => $admin->id];
        $a = GudangReturn::create($attrs + ['return_number' => 'AUDIT-A']); $b = GudangReturn::create($attrs + ['return_number' => 'AUDIT-B']);
        $a->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 2, 'condition' => 'Bagus (Siap Jual)']);
        $otherDetail = $b->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 4, 'condition' => 'Bagus (Siap Jual)']);
        $this->actingAs($admin)->post(route('gudang.returns.receive', $a->id), ['items' => [$otherDetail->id => ['received_quantity' => 4, 'condition' => 'Bagus (Siap Jual)']]])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('received', $a->fresh()->status); $this->assertSame('sent', $b->fresh()->status);
        $this->assertSame(4, $otherDetail->fresh()->received_quantity);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 4]);
    }

    public function test_replaying_prediction_conversion_creates_another_actual_and_duplicates_stock(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat')); $p = $this->product('hendhys', 'produced');
        $prediction = HendhysProduction::create(['production_number' => 'AUDIT-PRED', 'date' => now()->toDateString(), 'type' => 'prediksi', 'created_by' => $admin->id]);
        $prediction->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => 10]);
        $payload = ['prediction_id' => $prediction->id, 'date' => now()->toDateString(), 'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => 10]]];
        $this->actingAs($admin);
        for ($i = 0; $i < 2; $i++) $this->post(route('hendhys.productions.store'), $payload)->assertRedirect()->assertSessionHas('success');
        $this->assertSame(2, HendhysProduction::where('type', 'aktual')->count());
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 20]);
    }

    public function test_last_owner_can_delete_their_own_account_through_profile(): void
    {
        $owner = $this->user('owner');
        $owner->update(['password' => \Illuminate\Support\Facades\Hash::make('audit-password')]);
        $this->actingAs($owner)->delete(route('profile.destroy'), ['password' => 'audit-password'])->assertRedirect('/');
        $this->assertSoftDeleted($owner);
        $this->assertSame(0, User::role('owner')->count());
    }

    public function test_two_stale_void_requests_restore_the_same_stock_twice(): void
    {
        $cashier = $this->user('kasir_jihans', 'jihans');
        $admin = $this->user('admin_jihans', 'jihans'); $p = $this->product('jihans');
        CashierShift::create(['user_id' => $cashier->id, 'entity' => 'jihans', 'status' => 'open', 'opened_at' => now()->subMinute(), 'starting_cash' => 0]);
        $transaction = \App\Models\JihansTransaction::create(['transaction_number' => 'AUDIT-VOID', 'date' => now()->toDateString(), 'time' => now()->format('H:i:s'), 'status' => 'paid', 'grand_total' => 5000, 'created_by' => $cashier->id]);
        $transaction->details()->create(['product_id' => $p->id, 'product_name' => $p->name, 'unit_id' => $p->unit_id, 'quantity' => 5, 'price' => 1000, 'total' => 5000]);
        $firstSnapshot = $transaction->fresh(); $secondSnapshot = $transaction->fresh();
        $this->actingAs($admin);
        $controller = app(\App\Http\Controllers\Jihans\TransactionController::class);
        foreach ([$firstSnapshot, $secondSnapshot] as $snapshot) {
            $request = \Illuminate\Http\Request::create('/audit-void', 'POST', ['reason' => 'Bukti audit permintaan bersamaan']);
            $request->setLaravelSession(app('session.store'));
            $controller->void($request, $snapshot);
        }
        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertDatabaseHas('jihans_retail_stock', ['product_id' => $p->id, 'quantity' => 10]);
    }

    public function test_hendhys_production_accepts_another_unit_purchased_product_and_wrong_measurement_unit(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat'));
        $p = $this->product('jihans', 'purchased');
        $wrongUnit = Unit::create(['name' => 'Kilogram', 'abbreviation' => 'KG', 'entity_scope' => 'all']);
        $this->actingAs($admin)->post(route('hendhys.productions.store'), ['date' => now()->toDateString(), 'items' => [
            ['product_id' => $p->id, 'unit_id' => $wrongUnit->id, 'quantity_produced' => 10],
        ]])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'unit_id' => $wrongUnit->id, 'quantity' => 10]);
        $this->assertDatabaseHas('production_lots', ['entity' => 'hendhys', 'product_id' => $p->id, 'unit_id' => $wrongUnit->id, 'remaining' => 10]);
    }

    public function test_centre_admin_is_excluded_from_pending_request_notifications(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat'));
        \App\Models\HendhysBranchRequest::create(['request_number' => 'AUDIT-REQUEST', 'branch_id' => $this->branch('A')->id,
            'date' => now()->toDateString(), 'status' => 'pending', 'requested_by' => $admin->id]);
        $this->actingAs($admin);
        $counts = app(\App\Http\Controllers\Api\NotificationController::class)->getCounts()->getData(true);
        $this->assertSame(1, \App\Models\HendhysBranchRequest::where('status', 'pending')->count());
        $this->assertSame(0, $counts['hendhys_pusat_pending']);
    }

    public function test_hendhys_payment_can_store_conflicting_cash_and_transfer_types(): void
    {
        $centre = $this->branch('CENTRE', 'pusat'); $cashier = $this->user('kasir_hendhys', 'hendhys', $centre); $p = $this->product('hendhys');
        CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $centre->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        HendhysStockPusat::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 1]);
        $cash = \App\Models\PaymentMethod::create(['name' => 'Tunai', 'type' => 'tunai', 'is_active' => true]);
        $this->actingAs($cashier)->postJson(route('hendhys.pos.store'), [
            'checkout_key' => (string) \Illuminate\Support\Str::uuid(), 'payment_type' => 'transfer', 'payment_method_id' => $cash->id,
            'amount_paid' => 1000, 'subtotal' => 1000, 'grand_total' => 1000,
            'items' => [['product_id' => $p->id, 'quantity' => 1, 'price' => 1000, 'total' => 1000]],
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('hendhys_transaction_payments', ['payment_method_id' => $cash->id, 'payment_type' => 'transfer', 'payment_method' => 'transfer']);
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$values) => min($values), -1);
        $summary = CashierShift::firstOrFail()->calculatePaymentSummary();
        $this->assertEquals(1000, $summary['tunai']);
        $this->assertEquals(0, $summary['transfer']);
    }
}
