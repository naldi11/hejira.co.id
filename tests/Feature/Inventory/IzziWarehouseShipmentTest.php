<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, TransferOut, Unit, User};
use App\Services\{ActivityLogService, TransferReceiving};
use App\Services\Izzi\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IzziWarehouseShipmentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Product $product;
    private User $sender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = $this->branch('izzi');
        $this->sender = $this->actor('super_admin', 'all');
        $this->product = $this->product();
        DB::table('jihans_gudang_stock')->insert(['product_id' => $this->product->id, 'unit_id' => $this->product->unit_id, 'quantity' => 20, 'last_updated' => now()]);
    }

    private function branch(string $entity): Branch
    {
        return Branch::create(['code' => Str::random(8), 'name' => 'Outlet '.$entity.' '.Str::random(6), 'entity' => $entity, 'type' => 'cabang', 'is_active' => true]);
    }

    private function actor(string $role, string $entity, ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Kilogram warehouse test'], ['abbreviation' => 'KG', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Warehouse shipment test'], ['entity_scope' => 'all']);

        return Product::create(['code' => Str::random(10), 'name' => 'Bahan Izzi '.Str::random(6), 'unit_id' => $unit->id,
            'category_id' => $category->id, 'source_type' => 'purchased', 'product_type' => 'INV', 'status' => 'active', 'entity_scope' => 'all']);
    }

    private function data(int $quantity = 6): array
    {
        return ['request_key' => (string) Str::uuid(), 'to_entity' => 'izzi', 'branch_id' => $this->branch->id,
            'date' => today()->toDateString(), 'notes' => 'Pengiriman bahan dari Gudang',
            'items' => [['product_id' => $this->product->id, 'unit_id' => $this->product->unit_id, 'quantity' => $quantity, 'hpp_price' => 15000]]];
    }

    private function send(int $quantity = 6): TransferOut
    {
        $this->actingAs($this->sender)->withSession(['active_branch_id' => null]);
        $this->post(route('gudang.transfer-out.store'), $this->data($quantity))->assertSessionHasNoErrors()->assertSessionHas('success');

        return TransferOut::latest('id')->firstOrFail()->load('details');
    }

    private function receiveData(TransferOut $document, int $good = 3, int $damaged = 2): array
    {
        $detail = $document->details->first();

        return ['quantity_bagus' => [$detail->id => $good], 'quantity_rusak' => [$detail->id => $damaged], 'receive_notes' => 'Diperiksa sesuai fisik barang'];
    }

    private function asReceiver(?Branch $branch = null, string $role = 'kasir_izzi'): void
    {
        $branch ??= $this->branch;
        $this->actingAs($this->actor($role, 'izzi', $branch))->withSession(['active_branch_id' => $branch->id]);
    }

    public function test_dispatch_creates_delivery_note_and_debits_only_warehouse_until_confirmation(): void
    {
        $this->actingAs($this->sender)->get(route('gudang.transfer-out.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('izziBranches', 1)->where('izziBranches.0.id', $this->branch->id)->missing('izziTransferUrl'));
        $document = $this->send();
        $this->assertStringStartsWith('GDG-TRF-', $document->transfer_number);
        $this->assertSame('izzi', $document->to_entity);
        $this->assertSame($this->branch->id, $document->branch_id);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 14]);
        $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseCount('branch_stock_transfers', 0);
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->get(route('gudang.transfer-out.print', $document))->assertOk()->assertSee('Surat Jalan')->assertSee('Izzi Kebab')->assertSee($document->transfer_number);
        $this->get(route('gudang.transfer-out.index', ['to_entity' => 'izzi']))->assertInertia(fn (Assert $page) => $page->has('transfers.data', 1));
        $this->asReceiver();
        $this->get(route('izzi.warehouse-shipments.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('transfers.data', 1));
        $this->get(route('izzi.warehouse-shipments.receive-form', $document))->assertOk()->assertInertia(fn (Assert $page) => $page->where('currentScope', 'izzi'));
        $this->get(route('izzi.warehouse-shipments.bast', $document))->assertUnprocessable();
    }

    public function test_receipt_separates_good_damaged_missing_and_replay_does_not_duplicate_stock_or_bast(): void
    {
        $document = $this->send();
        // Packaging metadata must never multiply a warehouse shipment a second time.
        DB::table('izzi_ingredients')->insert(['product_id' => $this->product->id, 'purchase_unit' => 'KARUNG', 'purchase_factor' => 25000, 'created_at' => now(), 'updated_at' => now()]);
        $this->asReceiver();
        foreach ([1, 2] as $_) {
            $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertSessionHasNoErrors()->assertSessionHas('success');
        }
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $this->product->id, 'branch_id' => $this->branch->id, 'unit_id' => $this->product->unit_id, 'quantity' => 3000]);
        $this->assertDatabaseCount('izzi_stock_movements', 1);
        $this->assertDatabaseHas('izzi_stock_movements', ['source' => 'transfer_gudang', 'reference_id' => $document->id, 'quantity' => 3000]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 14]);
        $this->assertDatabaseCount('receipt_confirmations', 1);
        foreach (['baik' => 3, 'rusak' => 2, 'kurang' => 1] as $condition => $quantity) {
            $this->assertDatabaseHas('receipt_confirmation_details', ['product_id' => $this->product->id, 'condition' => $condition, 'actual_qty' => $quantity]);
        }
        $this->assertDatabaseHas('return_quarantine_entries', ['destination_key' => 'izzi-branch-'.$this->branch->id, 'quantity' => 2, 'source_type' => 'warehouse_receipt']);
        $this->assertDatabaseCount('hendhys_stock_in', 0);
        $this->get(route('izzi.warehouse-shipments.show', $document))->assertInertia(fn (Assert $page) => $page->where('canReceive', false)->has('receipt.items', 3));
        $this->get(route('izzi.warehouse-shipments.bast', $document))->assertOk()->assertSee('IZZI KEBAB')->assertSee('BAST-GDG-')->assertSee($document->transfer_number);
        $this->get(route('return-stock.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('entries.data', 1));
        $this->get(route('izzi.warehouse-shipments.receive-form', $document))->assertRedirect();
    }

    public function test_other_branches_units_and_owner_cannot_read_print_or_receive_the_document(): void
    {
        $document = $this->send();
        $other = $this->branch('izzi');
        $this->asReceiver($other);
        $this->get(route('izzi.warehouse-shipments.index'))->assertInertia(fn (Assert $page) => $page->has('transfers.data', 0));
        $this->get(route('izzi.warehouse-shipments.index', ['branch_id' => $this->branch->id]))->assertForbidden();
        foreach (['izzi.warehouse-shipments.show', 'izzi.warehouse-shipments.print', 'izzi.warehouse-shipments.bast', 'izzi.warehouse-shipments.receive-form'] as $route) {
            $this->get(route($route, $document))->assertForbidden();
        }
        $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertForbidden();
        foreach (['owner', 'admin_hendhys', 'kasir_jihans'] as $role) {
            $this->actingAs($this->actor($role, $role === 'owner' ? 'all' : explode('_', $role)[1]));
            $this->get(route('izzi.warehouse-shipments.show', $document))->assertForbidden();
            $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertForbidden();
        }
        $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseCount('receipt_confirmations', 0);
    }

    public function test_invalid_destination_duplicates_missing_key_and_insufficient_stock_leave_no_document(): void
    {
        $this->actingAs($this->sender);
        $data = $this->data();
        $data['branch_id'] = $this->branch('hendhys')->id;
        $this->post(route('gudang.transfer-out.store'), $data)->assertSessionHasErrors('branch_id');
        $data = $this->data(); unset($data['request_key']);
        $this->post(route('gudang.transfer-out.store'), $data)->assertSessionHasErrors('request_key');
        $data = $this->data(); $data['items'][] = $data['items'][0];
        $this->post(route('gudang.transfer-out.store'), $data)->assertSessionHasErrors('items.0.product_id');
        $this->post(route('gudang.transfer-out.store'), $this->data(21))->assertSessionHasErrors('items');
        $this->branch->update(['is_active' => false]);
        $this->post(route('gudang.transfer-out.store'), $this->data())->assertSessionHasErrors('branch_id');
        $this->assertDatabaseCount('gudang_transfer_out', 0);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 0);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 20]);
    }

    public function test_dispatch_retry_is_idempotent_even_if_remaining_stock_is_now_insufficient(): void
    {
        $this->actingAs($this->sender);
        $data = $this->data(15);
        foreach ([1, 2] as $_) $this->post(route('gudang.transfer-out.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $changed = $data; $changed['items'][0]['quantity'] = 2;
        $this->post(route('gudang.transfer-out.store'), $changed)->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('gudang_transfer_out', 1);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 1);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 5]);
    }

    public function test_invalid_receipt_details_over_receipt_and_changed_unit_roll_back_every_receiving_record(): void
    {
        $document = $this->send(); $data = $this->receiveData($document);
        $this->asReceiver();
        $bad = $data; $bad['quantity_bagus'][999999] = 1;
        $this->post(route('izzi.warehouse-shipments.receive', $document), $bad)->assertSessionHasErrors('quantity_bagus');
        $bad = $data; $bad['quantity_bagus'][$document->details->first()->id] = 6;
        $this->post(route('izzi.warehouse-shipments.receive', $document), $bad)->assertSessionHasErrors('received_quantities');
        $unit = Unit::create(['name' => 'Changed master unit', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        // Simulate a historical unit change that bypassed normal master protections.
        DB::table('master_products')->where('id', $this->product->id)->update(['unit_id' => $unit->id]);
        $this->post(route('izzi.warehouse-shipments.receive', $document), $data)->assertSessionHasErrors('received_quantities');
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->assertSame('sent', $document->fresh()->status);
    }

    public function test_logging_failure_rolls_back_dispatch_and_receipt_without_changing_other_balances(): void
    {
        $this->actingAs($this->sender);
        $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new \RuntimeException('log unavailable'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('gudang.transfer-out.store'), $this->data());
            $this->fail('Dispatch logging failure did not roll back.');
        } catch (\RuntimeException $e) { $this->assertSame('log unavailable', $e->getMessage()); }
        $this->assertDatabaseCount('gudang_transfer_out', 0);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 20]);
        $this->forgetMock(ActivityLogService::class);
        app()->forgetInstance(\App\Services\WarehouseDispatch::class);
        $document = $this->send();
        $this->asReceiver();
        $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new \RuntimeException('log unavailable'));
        app()->forgetInstance(TransferReceiving::class);
        try {
            $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document));
            $this->fail('Receipt logging failure did not roll back.');
        } catch (\RuntimeException $e) { $this->assertSame('log unavailable', $e->getMessage()); }
        $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->assertSame('sent', $document->fresh()->status);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 14]);
    }

    public function test_master_rename_preserves_documents_and_destination_cannot_be_reassigned_or_deleted(): void
    {
        $document = $this->send(); $name = $this->branch->name; $productName = $this->product->name;
        $this->branch->update(['name' => 'Renamed outlet']); $this->product->update(['name' => 'Renamed product']);
        $this->get(route('gudang.transfer-out.print', $document))->assertOk()->assertSee($name)->assertSee($productName);
        $this->put(route('master.branches.update', $this->branch), ['code' => $this->branch->code, 'name' => 'Renamed outlet', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true])->assertSessionHasErrors('entity');
        $this->delete(route('master.branches.destroy', $this->branch))->assertSessionHas('error');
        $this->asReceiver();
        $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertSessionHas('success');
        $this->get(route('izzi.warehouse-shipments.bast', $document))->assertOk()->assertSee($name)->assertSee($productName);
    }

    public function test_non_active_destination_and_overflow_cannot_complete_a_receipt(): void
    {
        $document = $this->send();
        $this->branch->update(['is_active' => false]);
        $this->actingAs($this->sender);
        $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertSessionHasErrors('received_quantities');
        $this->branch->update(['is_active' => true]);
        DB::table('izzi_stock')->insert(['branch_id' => $this->branch->id, 'product_id' => $this->product->id, 'unit_id' => $this->product->unit_id, 'quantity' => Quantity::MAX - 1000, 'last_updated' => now()]);
        $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document))->assertSessionHasErrors('items');
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $this->product->id, 'quantity' => Quantity::MAX - 1000]);
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertSame('sent', $document->fresh()->status);
    }

    public function test_zero_good_receipt_records_damage_and_shortfall_without_creating_sellable_stock(): void
    {
        $document = $this->send(); $this->asReceiver(null, 'admin_izzi');
        $this->post(route('izzi.warehouse-shipments.receive', $document), $this->receiveData($document, 0, 2))->assertSessionHas('success');
        $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseCount('izzi_stock_movements', 0);
        $this->assertDatabaseHas('receipt_confirmation_details', ['condition' => 'kurang', 'actual_qty' => 4]);
        $this->assertDatabaseHas('return_quarantine_entries', ['destination_key' => 'izzi-branch-'.$this->branch->id, 'quantity' => 2]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $this->product->id, 'quantity' => 14]);
    }

    public function test_later_item_failure_rolls_back_an_earlier_stock_credit_and_all_receipt_details(): void
    {
        $second = $this->product();
        DB::table('jihans_gudang_stock')->insert(['product_id' => $second->id, 'unit_id' => $second->unit_id, 'quantity' => 10, 'last_updated' => now()]);
        $data = $this->data(); $data['items'][] = ['product_id' => $second->id, 'unit_id' => $second->unit_id, 'quantity' => 4, 'hpp_price' => 1000];
        $this->actingAs($this->sender)->post(route('gudang.transfer-out.store'), $data)->assertSessionHas('success');
        $document = TransferOut::latest('id')->firstOrFail()->load('details');
        DB::table('izzi_stock')->insert(['branch_id' => $this->branch->id, 'product_id' => $second->id, 'unit_id' => $second->unit_id, 'quantity' => Quantity::MAX, 'last_updated' => now()]);
        $good = $document->details->mapWithKeys(fn ($d) => [$d->id => 1])->all();
        $damaged = $document->details->mapWithKeys(fn ($d) => [$d->id => 0])->all();
        $this->asReceiver();
        $this->post(route('izzi.warehouse-shipments.receive', $document), ['quantity_bagus' => $good, 'quantity_rusak' => $damaged])->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('izzi_stock', ['product_id' => $this->product->id]);
        $this->assertDatabaseCount('izzi_stock_movements', 0);
        $this->assertDatabaseCount('receipt_confirmations', 0);
        $this->assertDatabaseCount('receipt_confirmation_details', 0);
        $this->assertSame('sent', $document->fresh()->status);
    }

    public function test_receiving_evidence_is_linked_to_bast_and_pending_notification_is_branch_scoped(): void
    {
        Storage::fake('public');
        $document = $this->send(); $this->asReceiver();
        $this->get(route('izzi.warehouse-shipments.index'))->assertInertia(fn (Assert $page) => $page
            ->where('notifications.items', fn ($items) => collect($items)->contains('id', 'izzi_warehouse_pending')));
        $data = $this->receiveData($document); $data['photos'] = [UploadedFile::fake()->image('penerimaan.jpg')];
        $this->post(route('izzi.warehouse-shipments.receive', $document), $data)->assertSessionHas('success');
        $photo = $document->photos()->firstOrFail();
        Storage::disk('public')->assertExists($photo->path);
        $this->assertDatabaseHas('receipt_photos', ['photo_path' => $photo->path]);
        $entry = DB::table('return_quarantine_entries')->where('product_id', $this->product->id)->first();
        $this->get(route('izzi.warehouse-shipments.index'))->assertInertia(fn (Assert $page) => $page
            ->where('notifications.items', fn ($items) => !collect($items)->contains('id', 'izzi_warehouse_pending')));
        $this->asReceiver($this->branch('izzi'));
        $this->get(route('return-stock.index'))->assertInertia(fn (Assert $page) => $page->has('entries.data', 0));
        $this->get(route('return-stock.show', $entry->id))->assertForbidden();
    }
}
