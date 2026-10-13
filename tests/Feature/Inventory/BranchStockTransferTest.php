<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, Unit, User};
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchStockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 16:00', 'Asia/Jakarta'));
    }

    private function branch(string $entity = 'hendhys', string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => Str::random(6), 'name' => 'Cabang Uji '.Str::random(4), 'entity' => $entity, 'type' => $type, 'is_active' => true]);
    }

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $u = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $u->assignRole($role);
        return $u;
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $cat = ProductCategory::firstOrCreate(['name' => 'Brownies'], ['entity_scope' => 'all']);
        return Product::create(['code' => Str::random(6), 'name' => 'Brownies Cokelat', 'category_id' => $cat->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'source_type' => 'produced']);
    }

    private function stock(Branch $b, Product $p, int $qty = 20): void
    {
        $row = ['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => $qty, 'last_updated' => now()];
        if ($b->entity === 'hendhys') $row += ['branch_id' => $b->id, 'quantity_return' => 2];
        DB::table($b->entity === 'jihans' ? 'jihans_retail_stock' : 'hendhys_stock_branch')->insert($row);
    }

    private function data(Branch $from, Branch $to, Product $p, int $qty = 7): array
    {
        return ['request_key' => (string) Str::uuid(), 'from_branch_id' => $from->id, 'to_branch_id' => $to->id, 'date' => '2026-10-12', 'occurred_time' => '15:30', 'physical_sender_name' => 'Pengirim Barang Uji', 'physical_receiver_name' => 'Penerima Barang Uji',
            'reason' => 'Cabang tujuan kekurangan stok penjualan sore.', 'courier_name' => 'Petugas Antar Uji', 'notes' => 'Barang dibungkus dalam kotak.',
            'items' => [['product_id' => $p->id, 'quantity' => $qty, 'notes' => 'Kondisi barang baik.']], 'confirmed' => true];
    }

    private function send(Branch $from, Branch $to, Product $p, int $qty = 7): int
    {
        $this->actingAs($this->user('kasir_'.$from->entity, $from->entity, $from));
        $this->legacySend($this->data($from, $to, $p, $qty));
        return (int) DB::table('branch_stock_transfers')->max('id');
    }

    // Historical documents retain the old send/receive workflow; new HTTP input is completed on save.
    private function legacySend(array $data): int
    {
        return app(\App\Services\BranchStockTransfers::class)->send($data, auth()->user());
    }

    private function receiveData(int $id, int $qty): array
    {
        return ['request_key' => (string) Str::uuid(), 'physical_receiver_name' => 'Penerima Barang Uji', 'notes' => 'Barang sudah diperiksa dan diterima baik.', 'confirmed' => true,
            'items' => DB::table('branch_stock_transfer_items')->where('transfer_id', $id)->orderBy('id')->get()->map(fn ($d) => ['id' => $d->id, 'quantity' => $qty, 'expected_received_quantity' => (int) $d->received_quantity])->all()];
    }

    public function test_hendhys_to_hendhys_debits_sender_and_waits_for_receiver(): void
    {
        $a = $this->branch(); $b = $this->branch(); $c = $this->branch(); $p = $this->product();
        $this->stock($a, $p); $this->stock($b, $p, 5); $this->stock($c, $p, 30);
        DB::table('hendhys_stock_pusat')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 50, 'last_updated' => now()]);
        $data = $this->data($a, $b, $p);
        $sender = $this->user('kasir_hendhys', 'hendhys', $a);
        $this->actingAs($sender); $this->legacySend($data);
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $this->assertDatabaseHas('branch_stock_transfers', ['id' => $id, 'from_branch_id' => $a->id, 'to_branch_id' => $b->id, 'sent_by' => $sender->id, 'sent_by_name' => $sender->name, 'status' => 'sent', 'date' => '2026-10-12']);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 13, 'quantity_return' => 2]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 5]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $c->id, 'quantity' => 30]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 50]);
        $this->assertDatabaseCount('production_lots', 0);
        $this->get(route('branch-transfers.show', $id))->assertInertia(fn (Assert $page) => $page->component('BranchTransfers/Show')->where('items.0.remaining', 7)->where('canReceive', false)->where('canCancel', true));
        $receiver = $this->user('kasir_hendhys', 'hendhys', $b);
        $this->actingAs($receiver)->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 12]);
        $this->assertDatabaseHas('branch_stock_transfers', ['id' => $id, 'status' => 'received']);
        $this->assertDatabaseHas('branch_stock_transfer_receipts', ['transfer_id' => $id, 'received_by' => $receiver->id, 'received_by_name' => $receiver->name, 'physical_receiver_name' => 'Penerima Barang Uji']);
        $this->assertDatabaseHas('hendhys_stock_movements', ['branch_id' => $a->id, 'branch_transfer_id' => $id, 'type' => 'out', 'quantity' => 7]);
        $this->assertDatabaseHas('hendhys_stock_movements', ['branch_id' => $b->id, 'branch_transfer_id' => $id, 'type' => 'in', 'quantity' => 7]);
        $this->get(route('hendhys.stock.movements'))->assertInertia(fn (Assert $page) => $page->where('movements.data.0.branch_transfer_id', $id));
    }

    public function test_jihans_shared_retail_to_hendhys_preserves_gudang_and_production(): void
    {
        $a = $this->branch('jihans'); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        DB::table('jihans_gudang_stock')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 60, 'last_updated' => now()]);
        $id = $this->send($a, $b, $p);
        $this->get(route('branch-transfers.create'))->assertInertia(fn (Assert $page) => $page->where('from.entity', 'jihans')->where('products.0.quantity', 13));
        $this->get(route('jihans.stock.movements'))->assertInertia(fn (Assert $page) => $page->where('movements.data.0.branch_transfer_id', $id));
        $this->actingAs($this->user('admin_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_retail_stock', ['quantity' => 13]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 60]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 7]);
        $this->assertDatabaseHas('branch_stock_transfers', ['from_entity' => 'jihans', 'from_branch_id' => $a->id]);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 0); $this->assertDatabaseCount('production_lots', 0);
    }

    public function test_partial_receipts_record_each_receiver_and_leave_stock_in_transit(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p, 10);
        $this->actingAs($first = $this->user('kasir_hendhys', 'hendhys', $b));
        $data = $this->receiveData($id, 4);
        $this->post(route('branch-transfers.receive', $id), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('branch_stock_transfers', ['status' => 'partial']);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 4]);
        $this->get(route('branch-transfers.show', $id))->assertInertia(fn (Assert $page) => $page->where('items.0.remaining', 6)->has('receipts', 1)->where('canReceive', true));
        $this->travel(15)->minutes();
        $this->actingAs($last = $this->user('admin_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 6))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 10]);
        $this->assertDatabaseCount('branch_stock_transfer_receipts', 2);
        $this->assertDatabaseHas('branch_stock_transfer_receipts', ['received_by' => $first->id]);
        $this->assertDatabaseHas('branch_stock_transfer_receipts', ['received_by' => $last->id]);
        $this->get(route('branch-transfers.index'))->assertInertia(fn (Assert $page) => $page->where('transfers.data.0.latest_receiver', $last->name));
    }

    public function test_replays_do_not_double_debit_or_credit_and_stale_receipts_fail(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        $data = $this->data($a, $b, $p, 10); $sender = $this->user('kasir_hendhys', 'hendhys', $a);
        $this->actingAs($sender); $this->legacySend($data);
        $this->legacySend($data);
        $this->assertDatabaseCount('branch_stock_transfers', 1);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 10]);
        $data['reason'] = 'Isi berbeda dengan kunci sama.';
        try { $this->legacySend($data); $this->fail('Changed replay should fail'); } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('request_key', $e->errors()); }
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b));
        $receipt = $this->receiveData($id, 4); $stale = $this->receiveData($id, 4);
        $this->post(route('branch-transfers.receive', $id), $receipt)->assertSessionHas('success');
        $this->post(route('branch-transfers.receive', $id), $receipt)->assertSessionHas('success');
        $this->post(route('branch-transfers.receive', $id), $stale)->assertSessionHasErrors('items');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 4]);
        $this->assertDatabaseCount('branch_stock_transfer_receipts', 1);
        $receipt['notes'] = 'Isi berbeda dengan kunci sama.';
        $this->post(route('branch-transfers.receive', $id), $receipt)->assertSessionHasErrors('request_key');
        $complete = $this->receiveData($id, 6);
        $this->post(route('branch-transfers.receive', $id), $complete)->assertSessionHas('success');
        $this->post(route('branch-transfers.receive', $id), $complete)->assertSessionHas('success');
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 1))->assertSessionHasErrors('items');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 10]);
    }

    public function test_other_branches_owner_and_limited_superadmin_cannot_operate(): void
    {
        $a = $this->branch(); $b = $this->branch(); $c = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $c));
        $this->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 0));
        $this->get(route('branch-transfers.show', $id))->assertForbidden();
        $this->post(route('branch-transfers.store'), $this->data($a, $b, $p))->assertForbidden();
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertForbidden();
        $this->post(route('branch-transfers.cancel', $id), ['reason' => 'Pengiriman salah.', 'confirmed' => true])->assertForbidden();
        $this->actingAs($this->user('super_admin', 'gudang'))->get(route('branch-transfers.create'))->assertForbidden();
        $this->actingAs($this->user('owner'))->get(route('branch-transfers.index'))->assertForbidden();
        $this->get(route('branch-transfers.create'))->assertForbidden();
        $this->post(route('branch-transfers.store'), $this->data($a, $b, $p))->assertForbidden();
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertForbidden();
        $this->get(route('branch-transfers.show', $id))->assertForbidden();
        $this->post(route('branch-transfers.cancel', $id), ['reason' => 'Pengiriman salah.', 'confirmed' => true])->assertForbidden();
        $this->actingAs($this->user('super_admin', 'all'))->get(route('branch-transfers.create'))->assertOk();
    }

    public function test_active_branch_is_checked_against_entity_and_no_permanent_reassignment(): void
    {
        $a = $this->branch(); $b = $this->branch(); $c = $this->branch('jihans'); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $user = $this->user('kasir_hendhys', 'hendhys', $b);
        $this->actingAs($user)->withSession(['active_branch_id' => $c->id])->get(route('branch-transfers.show', $id))->assertForbidden();
        $this->withSession(['active_branch_id' => $a->id])->get(route('branch-transfers.show', $id))->assertOk();
        $this->assertSame($b->id, $user->fresh()->branch_id);
    }

    public function test_destinations_products_and_quantities_are_validated(): void
    {
        $a = $this->branch(); $b = $this->branch(); $j = $this->branch('jihans'); $centre = $this->branch('hendhys', 'pusat');
        $p = $this->product(); $this->stock($a, $p); $missing = $this->product();
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a));
        foreach ([$a, $j, $centre] as $target) $this->post(route('branch-transfers.store'), $this->data($a, $target, $p))->assertSessionHasErrors();
        $b->update(['is_active' => false]);
        $this->post(route('branch-transfers.store'), $this->data($a, $b, $p))->assertSessionHasErrors('to_branch_id'); $b->update(['is_active' => true]);
        foreach ([['quantity' => 21], ['quantity' => 0], ['quantity' => 1.5], ['product_id' => $missing->id]] as $extra) {
            $data = $this->data($a, $b, $p); $data['items'][0] = $extra + $data['items'][0];
            $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors();
        }
        foreach ([['reason' => '    '], ['confirmed' => false], ['date' => '2026-10-13']] as $extra) $this->post(route('branch-transfers.store'), $extra + $this->data($a, $b, $p))->assertSessionHasErrors();
        $data = $this->data($a, $b, $p); $data['items'][] = $data['items'][0];
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors();
        $this->assertDatabaseCount('branch_stock_transfers', 0); $this->assertDatabaseCount('hendhys_stock_movements', 0);
        $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 20]);
    }

    public function test_multi_item_insufficient_stock_rolls_back_entire_shipment(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $other = $this->product(); $this->stock($a, $p); $this->stock($a, $other, 2);
        $data = $this->data($a, $b, $p); $data['items'][] = ['product_id' => $other->id, 'quantity' => 3];
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a))->post(route('branch-transfers.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('branch_stock_transfers', 0); $this->assertDatabaseCount('branch_transfer_actions', 0);
        $this->assertDatabaseCount('branch_stock_transfer_items', 0); $this->assertDatabaseCount('hendhys_stock_movements', 0);
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $p->id, 'quantity' => 20]);
    }

    public function test_receipts_cannot_include_foreign_missing_extra_or_duplicate_details(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        $id = $this->send($a, $b, $p); $otherId = $this->send($a, $b, $p, 2);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b));
        $data = $this->receiveData($id, 8);
        $this->post(route('branch-transfers.receive', $id), $data)->assertSessionHasErrors('items');
        $foreign = $this->receiveData($otherId, 2);
        $this->post(route('branch-transfers.receive', $id), $foreign)->assertSessionHasErrors('items');
        $data = $this->receiveData($id, 2); $data['items'][] = $data['items'][0];
        $this->post(route('branch-transfers.receive', $id), $data)->assertSessionHasErrors();
        foreach ([['items' => []], ['notes' => '   '], ['confirmed' => false]] as $extra) $this->post(route('branch-transfers.receive', $id), $extra + $this->receiveData($id, 7))->assertSessionHasErrors();
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 0))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('branch_stock_transfer_receipts', 0);
        $this->assertDatabaseMissing('hendhys_stock_branch', ['branch_id' => $b->id]);
    }

    public function test_cancellation_restores_sender_once_and_preserves_history(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        $sender = $this->user('kasir_hendhys', 'hendhys', $a); $data = $this->data($a, $b, $p);
        $this->actingAs($sender); $this->legacySend($data);
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $payload = ['reason' => 'Tujuan salah dan seluruh barang masih di cabang asal.', 'confirmed' => true];
        $this->post(route('branch-transfers.cancel', $id), ['confirmed' => false] + $payload)->assertSessionHasErrors('confirmed');
        $this->post(route('branch-transfers.cancel', $id), $payload)->assertSessionHas('success');
        $this->post(route('branch-transfers.cancel', $id), $payload)->assertSessionHas('success');
        $this->legacySend($data);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 20]);
        $this->assertDatabaseHas('branch_stock_transfers', ['status' => 'cancelled', 'cancelled_by' => $sender->id, 'cancel_reason' => $payload['reason']]);
        $this->assertDatabaseCount('hendhys_stock_movements', 2);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertSessionHasErrors('items');
    }

    public function test_partly_received_mutation_cannot_be_cancelled(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 3));
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a))->post(route('branch-transfers.cancel', $id), ['reason' => 'Tidak boleh membatalkan setelah diterima.', 'confirmed' => true])->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 13]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 3]);
    }

    public function test_deleted_product_and_renamed_actor_do_not_erase_document_snapshots(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $oldName = auth()->user()->name; auth()->user()->update(['name' => 'Nama Baru']); $p->delete(); $a->update(['name' => 'Cabang Diganti']);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertSessionHas('success');
        $this->get(route('branch-transfers.show', $id))->assertInertia(fn (Assert $page) => $page->where('transfer.sent_by_name', $oldName)->where('items.0.product_name', 'Brownies Cokelat')->where('items.0.received_quantity', 7));
    }

    public function test_logging_failure_rolls_back_receipt_and_balance(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new \RuntimeException('Simulated log failure'));
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b))->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7))->assertServerError();
        $this->assertDatabaseCount('branch_stock_transfer_receipts', 0); $this->assertDatabaseCount('branch_stock_transfer_receipt_items', 0);
        $this->assertDatabaseHas('branch_stock_transfer_items', ['transfer_id' => $id, 'received_quantity' => 0]);
        $this->assertDatabaseMissing('hendhys_stock_branch', ['branch_id' => $b->id]);
        $this->assertDatabaseCount('hendhys_stock_movements', 1);
    }

    public function test_index_filters_and_missing_documents(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $this->get(route('branch-transfers.index', ['status' => 'sent', 'date_to' => '2026-10-12']))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 1));
        $this->get(route('branch-transfers.index', ['to_branch_id' => $a->id]))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 0));
        $this->get(route('branch-transfers.show', 99999))->assertNotFound();
    }

    public function test_pending_notification_only_reaches_destination_and_excludes_owner(): void
    {
        $a = $this->branch(); $b = $this->branch(); $c = $this->branch(); $p = $this->product(); $this->stock($a, $p); $id = $this->send($a, $b, $p);
        $this->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->where('notifications.items', fn ($items) => !collect($items)->contains('id', 'branch_mutation_pending')));
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $c))->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->where('notifications.items', fn ($items) => !collect($items)->contains('id', 'branch_mutation_pending')));
        $owner = $this->user('owner');
        $this->actingAs($owner)->get(route('stock-opname.index'))->assertInertia(fn (Assert $p) => $p->where('notifications.items', fn ($items) => !collect($items)->contains('id', 'branch_mutation_pending')));
        $this->assertSame(0, app(\App\Services\BranchStockTransfers::class)->pendingIncomingCount($owner));
        $this->assertSame(0, app(\App\Services\BranchStockTransfers::class)->visibleQuery($owner)->count());
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $b))->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->where('notifications.items', fn ($items) => collect($items)->contains('id', 'branch_mutation_pending')));
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 7));
        $this->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->where('notifications.items', fn ($items) => !collect($items)->contains('id', 'branch_mutation_pending')));
    }

    public function test_completed_input_updates_both_balances_once_and_records_physical_people_separately(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product();
        $this->stock($a, $p); $this->stock($b, $p, 5);
        $actor = $this->user('super_admin', 'all'); $data = $this->data($a, $b, $p);
        $this->actingAs($actor)->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        $this->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $this->assertDatabaseCount('branch_stock_transfers', 1);
        $this->assertDatabaseCount('branch_stock_transfer_receipts', 1);
        $this->assertDatabaseCount('hendhys_stock_movements', 2);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 13]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 12]);
        $this->assertDatabaseHas('branch_stock_transfers', ['status' => 'received', 'recording_mode' => 'after_receipt',
            'number' => 'MUT-20261012-0001', 'physical_sender_name' => 'Pengirim Barang Uji', 'sent_by' => $actor->id]);
        $this->assertDatabaseHas('branch_stock_transfer_receipts', ['physical_receiver_name' => 'Penerima Barang Uji', 'received_by' => $actor->id]);
        $this->get(route('branch-transfers.show', $id))->assertInertia(fn (Assert $p) => $p->where('canReceive', false)->where('canCancel', false)
            ->where('items.0.remaining', 0)->has('stockChanges', 2)->where('stockChanges.0.before', 20)->where('stockChanges.0.after', 13)->where('stockChanges.0.unit', 'PCS')
            ->where('transfer.sent_at', '12/10/2026 15:30:00 WIB')->where('transfer.recorded_at', '12/10/2026 16:00:00 WIB'));
        $data['physical_receiver_name'] = 'Penerima berbeda';
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors('request_key');
        $this->assertDatabaseCount('hendhys_stock_movements', 2);
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 1))->assertSessionHasErrors('items');
        $this->post(route('branch-transfers.cancel', $id), ['reason' => 'Tidak boleh membatalkan stok yang sudah diterima.', 'confirmed' => true])->assertSessionHasErrors('reason');
    }

    public function test_daily_numbers_follow_selected_date_and_restart_without_renumbering_existing_records(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p, 100);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a));
        foreach (['2026-10-12', '2026-10-11', '2026-10-12'] as $date) {
            $data = $this->data($a, $b, $p, 1); $data['date'] = $date;
            $this->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        }
        $this->assertSame(['MUT-20261012-0001', 'MUT-20261011-0001', 'MUT-20261012-0002'], DB::table('branch_stock_transfers')->orderBy('id')->pluck('number')->all());
        $this->get(route('branch-transfers.history'))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 3)
            ->where('transfers.data.0.number', 'MUT-20261012-0002')->where('transfers.data.2.number', 'MUT-20261011-0001'));
        $this->get(route('branch-transfers.index'))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 2)->where('filters.date_from', '2026-10-12'));
    }

    public function test_completed_jihans_mutation_keeps_shared_retail_separate_from_production(): void
    {
        $a = $this->branch('jihans'); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        DB::table('jihans_gudang_stock')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 50, 'last_updated' => now()]);
        $this->actingAs($this->user('kasir_jihans', 'jihans', $a))->post(route('branch-transfers.store'), $this->data($a, $b, $p))->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_retail_stock', ['quantity' => 13]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 7]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 50]);
        $this->assertDatabaseCount('production_lots', 0);
        $this->assertDatabaseHas('branch_stock_transfers', ['status' => 'received']);
    }

    public function test_completed_input_requires_people_and_rejects_future_time_in_wib(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a));
        foreach (['physical_sender_name', 'physical_receiver_name', 'occurred_time'] as $key) {
            $data = $this->data($a, $b, $p); $data[$key] = '';
            $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors($key);
        }
        $data = $this->data($a, $b, $p); $data['occurred_time'] = '16:01';
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors('occurred_time');
        $this->get(route('branch-transfers.create'))->assertInertia(fn (Assert $p) => $p->where('today', '2026-10-12')->where('currentTime', '16:00'));
        $this->assertDatabaseCount('branch_stock_transfers', 0);
    }

    public function test_completed_input_rolls_back_credit_and_daily_sequence_if_destination_unit_is_wrong(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p); $this->stock($b, $p, 5);
        $wrong = Unit::create(['name' => 'Kotak', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        DB::table('hendhys_stock_branch')->where('branch_id', $b->id)->update(['unit_id' => $wrong->id]);
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $a));
        $data = $this->data($a, $b, $p);
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors('items');
        foreach (['branch_stock_transfers', 'branch_stock_transfer_receipts', 'branch_stock_transfer_items', 'branch_transfer_daily_sequences', 'hendhys_stock_movements'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 20]);
        DB::table('hendhys_stock_branch')->where('branch_id', $b->id)->update(['unit_id' => $p->unit_id]);
        $this->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('branch_stock_transfers', ['number' => 'MUT-20261012-0001']);
    }

    public function test_mutation_uses_product_master_unit_for_hendhys_and_jihans_without_defaulting_to_pcs(): void
    {
        $pack = Unit::create(['name' => 'Kemasan', 'abbreviation' => 'PACK', 'entity_scope' => 'all']);
        foreach (['hendhys', 'jihans'] as $entity) {
            $a = $this->branch($entity); $b = $this->branch(); $p = $this->product();
            $p->update(['unit_id' => $pack->id]); $this->stock($a, $p);
            $this->actingAs($this->user('kasir_'.$entity, $entity, $a));
            $this->get(route('branch-transfers.create'))->assertInertia(fn (Assert $page) => $page
                ->where('products.0.unit', 'PACK')->where('products.0.stock_unit', 'PACK')->where('products.0.unit_matches', true));
            $data = $this->data($a, $b, $p);
            // A manually supplied unit cannot override the product master.
            $data['items'][0]['unit_id'] = Unit::where('abbreviation', 'PCS')->value('id');
            $this->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
            $this->assertDatabaseHas('branch_stock_transfer_items', ['product_id' => $p->id, 'unit_id' => $pack->id, 'unit_name' => 'PACK', 'quantity' => 7, 'received_quantity' => 7]);
            $this->assertDatabaseHas($entity === 'jihans' ? 'jihans_retail_stock' : 'hendhys_stock_branch', ['product_id' => $p->id, 'unit_id' => $pack->id, 'quantity' => 13]);
            $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'product_id' => $p->id, 'unit_id' => $pack->id, 'quantity' => 7]);
        }
    }

    public function test_wrong_source_unit_is_visible_in_form_and_cannot_change_stocks_even_with_direct_post(): void
    {
        $pack = Unit::create(['name' => 'Kemasan', 'abbreviation' => 'PACK', 'entity_scope' => 'all']);
        foreach (['hendhys', 'jihans'] as $entity) {
            $a = $this->branch($entity); $b = $this->branch(); $p = $this->product(); $this->stock($a, $p);
            $table = $entity === 'jihans' ? 'jihans_retail_stock' : 'hendhys_stock_branch';
            DB::table($table)->where('product_id', $p->id)->update(['unit_id' => $pack->id]);
            $this->actingAs($this->user('kasir_'.$entity, $entity, $a));
            $this->get(route('branch-transfers.create'))->assertInertia(fn (Assert $page) => $page
                ->where('products.0.unit', 'PCS')->where('products.0.stock_unit', 'PACK')->where('products.0.unit_matches', false)->where('products.0.quantity', 20));
            $this->post(route('branch-transfers.store'), $this->data($a, $b, $p))->assertSessionHasErrors('items')
                ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('items'), $a->name)
                    && str_contains($errors->first('items'), 'PACK') && str_contains($errors->first('items'), 'PCS'));
            $this->assertDatabaseHas($table, ['product_id' => $p->id, 'unit_id' => $pack->id, 'quantity' => 20]);
            $this->assertDatabaseMissing('hendhys_stock_branch', ['branch_id' => $b->id, 'product_id' => $p->id]);
        }
        foreach (['branch_stock_transfers', 'branch_stock_transfer_receipts', 'branch_stock_transfer_items', 'branch_transfer_daily_sequences', 'hendhys_stock_movements', 'jihans_retail_stock_movements'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_history_searches_snapshots_receivers_items_and_notes_without_leaking_other_branches(): void
    {
        $a = $this->branch(); $b = $this->branch(); $c = $this->branch(); $p = $this->product(); $this->stock($a, $p);
        $actor = $this->user('kasir_hendhys', 'hendhys', $a);
        $data = $this->data($a, $b, $p); $data['physical_receiver_name'] = 'Siti Penerima'; $data['physical_sender_name'] = 'Budi Pengirim';
        $this->actingAs($actor)->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $a->update(['name' => 'Nama cabang berubah']); $p->update(['name' => 'Nama produk berubah']); $p->delete(); $actor->update(['name' => 'Nama akun berubah']);
        $this->get(route('branch-transfers.show', $id))->assertInertia(fn (Assert $p) => $p->where('stockChanges.0.product', 'Brownies Cokelat')->where('stockChanges.0.unit', 'PCS'));
        foreach (['Siti Penerima', 'Budi Pengirim', 'Brownies Cokelat', $p->code, $data['courier_name'], 'dibungkus dalam kotak', 'kekurangan stok', 'MUT-20261012-0001'] as $term) {
            $this->get(route('branch-transfers.history', ['search' => $term]))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 1)->where('transfers.data.0.id', $id));
        }
        $this->actingAs($this->user('kasir_hendhys', 'hendhys', $c))->get(route('branch-transfers.history', ['search' => 'Siti Penerima']))->assertInertia(fn (Assert $p) => $p->has('transfers.data', 0));
        $this->actingAs($this->user('owner'))->get(route('branch-transfers.history'))->assertForbidden();
    }

    public function test_ui_preview_with_isolated_transfer_records(): void
    {
        $path = getenv('HEJIRA_BRANCH_TRANSFER_PREVIEW');
        if (!$path) { $this->assertTrue(true); return; }
        $a = $this->branch(); $a->update(['name' => 'Hendhys Pasar 6 (data uji)']);
        $b = $this->branch(); $b->update(['name' => 'Hendhys Beringin (data uji)']);
        $j = $this->branch('jihans'); $j->update(['name' => 'Jihan’s Food (data uji)']);
        $p = $this->product(); $this->stock($a, $p); $this->stock($j, $p, 15);
        $sender = $this->user('kasir_hendhys', 'hendhys', $a); $sender->update(['name' => 'Pengirim Uji']);
        $this->actingAs($sender);
        file_put_contents($path.'/create.html', $this->get(route('branch-transfers.create'))->assertOk()->getContent());
        $this->legacySend($this->data($a, $b, $p, 10));
        $id = (int) DB::table('branch_stock_transfers')->value('id');
        $receiver = $this->user('admin_hendhys', 'hendhys', $b); $receiver->update(['name' => 'Admin Penerima Uji']);
        $this->actingAs($receiver);
        file_put_contents($path.'/receive.html', $this->get(route('branch-transfers.show', $id))->assertOk()->getContent());
        $this->post(route('branch-transfers.receive', $id), $this->receiveData($id, 4))->assertSessionHas('success');
        $owner = $this->user('owner'); $owner->update(['name' => 'Owner Uji']);
        $this->actingAs($owner);
        file_put_contents($path.'/owner.html', $this->get(route('stock-opname.index'))->assertOk()->getContent());
        $this->actingAs($receiver);
        file_put_contents($path.'/partial.html', $this->get(route('branch-transfers.show', $id))->assertOk()->getContent());
        file_put_contents($path.'/index.html', $this->get(route('branch-transfers.index'))->assertOk()->getContent());
        $this->actingAs($receiver)->post(route('branch-transfers.receive', $id), $this->receiveData($id, 6))->assertSessionHas('success');
        $this->actingAs($receiver);
        file_put_contents($path.'/complete.html', $this->get(route('branch-transfers.show', $id))->assertOk()->getContent());
        $this->actingAs($this->user('kasir_jihans', 'jihans', $j));
        file_put_contents($path.'/jihans.html', $this->get(route('branch-transfers.create'))->assertOk()->getContent());
        $this->travelBack();
        $superadmin = $this->user('super_admin', 'all'); $superadmin->update(['name' => 'Super Admin Uji']);
        $this->actingAs($superadmin);
        file_put_contents($path.'/superadmin-dashboard.html', $this->get(route('gudang.dashboard'))->assertOk()->getContent());
        file_put_contents($path.'/superadmin.html', $this->get(route('products.qr'))->assertOk()->getContent());
        file_put_contents($path.'/superadmin-transfers.html', $this->get(route('branch-transfers.index'))->assertOk()->getContent());
        file_put_contents($path.'/completed-create.html', $this->get(route('branch-transfers.create', ['from_branch_id' => $a->id]))->assertOk()->getContent());
        $data = $this->data($a, $b, $p, 3); $data['date'] = now('Asia/Jakarta')->toDateString(); $data['occurred_time'] = now('Asia/Jakarta')->format('H:i');
        $data['physical_sender_name'] = 'Budi · Hendhys Pasar 6'; $data['physical_receiver_name'] = 'Siti · Hendhys Beringin';
        $this->post(route('branch-transfers.store'), $data)->assertSessionHas('success');
        $completedId = (int) DB::table('branch_stock_transfers')->max('id');
        file_put_contents($path.'/completed-detail.html', $this->get(route('branch-transfers.show', $completedId))->assertOk()->getContent());
        file_put_contents($path.'/completed-history.html', $this->get(route('branch-transfers.history'))->assertOk()->getContent());
        file_put_contents($path.'/completed-search.html', $this->get(route('branch-transfers.history', ['search' => 'Siti']))->assertOk()->getContent());

        $jihansAdmin = $this->user('admin_jihans', 'jihans', $j); $jihansAdmin->update(['name' => 'Admin Jihan’s Uji']);
        $this->actingAs($jihansAdmin)->withSession(['active_branch_id' => $j->id]);
        file_put_contents($path.'/jihans-admin.html', $this->get(route('products.qr'))->assertOk()->getContent());
        $pusat = $this->branch('hendhys', 'pusat'); $pusat->update(['name' => 'Hendhys Produksi (data uji)']);
        $productionAdmin = $this->user('admin_hendhys', 'hendhys', $pusat); $productionAdmin->update(['name' => 'Admin Produksi Uji']);
        $this->actingAs($productionAdmin)->withSession(['active_branch_id' => $pusat->id]);
        file_put_contents($path.'/hendhys-pusat.html', $this->get(route('products.qr'))->assertOk()->getContent());
    }
}
