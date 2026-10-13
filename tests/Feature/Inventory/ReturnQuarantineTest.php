<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, Unit, User, GudangReturn, GudangReturnDetail, HendhysReturnFromBranch};
use App\Services\ReturnQuarantine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReturnQuarantineTest extends TestCase
{
    use RefreshDatabase;
    private function login(User $user): static
    {
        $user->refresh();
        return $this->actingAs($user)->withSession(['active_branch_id' => $user->branch_id]);
    }
    private function user(string $role, string $entity, ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $u = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]); $u->assignRole($role); return $u;
    }
    private function branch(string $entity = 'hendhys', string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => Str::random(8), 'name' => 'Cabang Asal '.Str::random(4), 'entity' => $entity, 'type' => $type, 'is_active' => true]);
    }
    private function product(): Product
    {
        $u = Unit::firstOrCreate(['abbreviation' => 'PCS'], ['name' => 'Pieces', 'entity_scope' => 'all']);
        $c = ProductCategory::firstOrCreate(['name' => 'Brownies'], ['entity_scope' => 'all']);
        return Product::create(['code' => Str::random(8), 'name' => 'Brownies Uji', 'unit_id' => $u->id, 'category_id' => $c->id, 'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'source_type' => 'purchased']);
    }
    private function stock(Product $p, string $table, int $qty, ?Branch $b = null): void
    {
        DB::table($table)->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => $qty, 'last_updated' => now(), ...($b ? ['branch_id' => $b->id] : [])]);
    }
    private function data(Product $p, string $condition = 'rusak'): array
    {
        return ['date' => '2026-10-13', 'notes' => 'Kemasan sobek saat diperiksa di cabang.', 'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 5, 'condition' => $condition]]];
    }
    private function sendGudang(string $entity = 'hendhys', string $condition = 'rusak'): array
    {
        $b = $this->branch($entity); $p = $this->product(); $u = $this->user('admin_'.$entity, $entity, $b);
        $table = $entity === 'hendhys' ? 'hendhys_stock_branch' : 'jihans_retail_stock';
        $this->stock($p, $table, 10, $entity === 'hendhys' ? $b : null);
        $this->login($u)->post(route($entity.'.returns-to-gudang.store'), $this->data($p, $condition))->assertSessionHas('success');
        return [$p, $b, $u, GudangReturn::latest('id')->first()];
    }
    private function receive(GudangReturn $r, string $condition, int $qty = 5): void
    {
        $id = $r->details()->value('id');
        $this->login($this->user('super_admin', 'gudang'))->post(route('gudang.returns.receive', $r), ['items' => [$id => ['received_quantity' => $qty, 'condition' => $condition, 'receiving_notes' => 'Barang diperiksa dan dipisahkan.']]])->assertSessionHas('success');
    }
    public function test_damaged_warehouse_returns_are_separate_auditable_and_not_sellable(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang(); $this->stock($p, 'jihans_gudang_stock', 0);
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->receive($r, 'Rusak (Defect)', 3);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 5]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 0]);
        $this->assertDatabaseHas('return_quarantine_entries', ['return_number' => $r->return_number, 'quantity' => 3, 'condition' => 'damaged', 'sent_condition' => 'rusak', 'origin_name' => $b->name, 'sent_by_name' => $u->name, 'reason' => $r->notes]);
        $this->post(route('gudang.returns.receive', $r), ['items' => [$r->details()->value('id') => ['received_quantity' => 3, 'condition' => 'Rusak (Defect)']]])->assertSessionHas('error');
        $this->assertDatabaseCount('return_quarantine_entries', 1);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 1);
    }
    public function test_receipt_cannot_convert_damaged_goods_into_good_stock_or_use_another_return_detail(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang(); [$p2, $b2, $u2, $r2] = $this->sendGudang();
        $sa = $this->user('super_admin', 'gudang'); $id = $r->details()->value('id');
        $this->login($sa)->post(route('gudang.returns.receive', $r), ['items' => [$id => ['received_quantity' => 5, 'condition' => 'Bagus (Siap Jual)']]])->assertSessionHasErrors('items.'.$id.'.condition');
        $this->post(route('gudang.returns.receive', $r), ['items' => [$r2->details()->value('id') => ['received_quantity' => 5, 'condition' => 'Rusak (Defect)']]])->assertSessionHasErrors('items');
        $this->post(route('gudang.returns.receive', $r), ['items' => [$id => ['received_quantity' => 5, 'condition' => 'Tidak diketahui']]])->assertSessionHasErrors('items.'.$id.'.condition');
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->assertDatabaseHas('gudang_returns', ['id' => $r->id, 'status' => 'sent']);
    }
    public function test_jihans_expired_return_has_real_branch_and_does_not_credit_available_balance(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang('jihans', 'Kadaluwarsa'); $this->stock($p, 'jihans_gudang_stock', 7);
        $this->receive($r, 'Kadaluwarsa');
        $this->assertDatabaseHas('gudang_returns', ['id' => $r->id, 'branch_id' => $b->id]);
        $this->assertDatabaseHas('jihans_retail_stock', ['quantity' => 5]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 7]);
        $this->assertDatabaseHas('return_quarantine_entries', ['origin_branch_id' => $b->id, 'origin_entity' => 'jihans', 'condition' => 'expired']);
    }
    public function test_good_return_still_credits_available_stock_and_never_enters_damaged_register(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang('hendhys', 'baik'); $this->stock($p, 'jihans_gudang_stock', 7);
        $this->receive($r, 'Bagus (Siap Jual)');
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 12]); $this->assertDatabaseCount('return_quarantine_entries', 0);
    }
    public function test_return_to_hendhys_keeps_condition_and_reason_and_never_inflates_production_stock(): void
    {
        foreach (['hendhys', 'jihans'] as $entity) {
            $b = $this->branch($entity); $p = $this->product(); $sender = $this->user('admin_'.$entity, $entity, $b);
            $this->stock($p, $entity === 'hendhys' ? 'hendhys_stock_branch' : 'jihans_retail_stock', 10, $entity === 'hendhys' ? $b : null);
            $this->stock($p, 'hendhys_stock_pusat', 2);
            $this->login($sender)->post(route($entity === 'hendhys' ? 'hendhys.returns.store' : 'jihans.returns-to-hendhys.store'), $this->data($p, 'basi'))->assertSessionHas('success');
            $r = HendhysReturnFromBranch::latest('id')->first();
            $receiver = $this->user('admin_hendhys', 'hendhys', $this->branch('hendhys', 'pusat'));
            $this->login($receiver)->post(route('hendhys.returns.receive', $r))->assertSessionHas('success');
            $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 2, 'quantity_return' => 5]);
            $this->assertDatabaseHas('return_quarantine_entries', ['source_type' => 'hendhys', 'return_id' => $r->id, 'condition' => 'expired', 'origin_entity' => $entity, 'reason' => $r->notes]);
            $this->post(route('hendhys.returns.receive', $r))->assertSessionHas('error');
            $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity_return' => 5]);
        }
        $this->assertDatabaseCount('return_quarantine_entries', 2);
    }
    public function test_quarantine_history_filters_and_snapshot_survive_master_changes_and_are_scoped(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang(); $this->receive($r, 'Rusak (Defect)'); $id = DB::table('return_quarantine_entries')->value('id');
        $old = $p->code; $name = $b->name; $senderName = $u->name; $p->update(['name' => 'Nama Baru', 'code' => 'NEWCODE']); $b->update(['name' => 'Cabang Baru']); $u->update(['name' => 'Petugas Baru']);
        $owner = $this->user('owner', 'all');
        $this->login($owner)->get(route('return-stock.index', ['search' => $old, 'condition' => 'damaged', 'branch_id' => $b->id, 'date_from' => '2026-10-13', 'date_to' => '2026-10-13']))->assertInertia(fn (Assert $p) => $p->has('entries.data', 1)->where('balances.data.0.quantity', 5)->where('entries.data.0.origin_name', $name));
        $this->get(route('return-stock.show', $id))->assertInertia(fn (Assert $p) => $p->where('entry.product_code', $old)->where('entry.product_name', 'Brownies Uji'));
        $this->login($this->user('admin_hendhys', 'hendhys', $this->branch()))->get(route('return-stock.show', $id))->assertForbidden();
        $this->get(route('return-stock.index'))->assertInertia(fn (Assert $p) => $p->has('entries.data', 0));
        $this->login($u)->get(route('return-stock.show', $id))->assertOk();
        $this->get(route('hendhys.returns-to-gudang.show', $r))->assertInertia(fn (Assert $p) => $p->where('return.details.0.product_code', $old)->where('return.creator', $senderName));
        $this->login($this->user('super_admin', 'gudang'))->get(route('return-stock.index'))->assertInertia(fn (Assert $p) => $p->has('entries.data', 1));
    }
    public function test_new_returns_require_reason_and_valid_branch_and_migration_reads_legacy_without_recredit(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang();
        $data = $this->data($p); $data['notes'] = '';
        $this->login($u)->post(route('hendhys.returns-to-gudang.store'), $data)->assertSessionHasErrors('notes');
        $this->receive($r, 'Rusak (Defect)');
        $balance = DB::table('hendhys_stock_branch')->value('quantity');
        $migration = require database_path('migrations/2026_10_13_040000_add_return_quarantine_tracking.php');
        $migration->down(); $migration->up();
        $this->assertDatabaseHas('return_quarantine_entries', ['return_number' => $r->return_number, 'legacy' => true, 'quantity' => 5]);
        $this->assertEquals($balance, DB::table('hendhys_stock_branch')->value('quantity'));
        app(ReturnQuarantine::class)->record('gudang', $r->fresh(), true); $this->assertDatabaseCount('return_quarantine_entries', 1);
    }
    public function test_quarantined_quantity_cannot_be_dispatched_and_opname_only_counts_saleable_stock(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang(); $this->stock($p, 'jihans_gudang_stock', 0);
        $this->receive($r, 'Rusak (Defect)');
        $this->post(route('gudang.transfer-out.store'), ['to_entity' => 'hendhys', 'branch_id' => $b->id, 'date' => '2026-10-13', 'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 1, 'hpp_price' => 0]]])->assertSessionHasErrors();
        $owner = $this->user('owner', 'all');
        $this->login($owner)->post(route('owner.stock-opname.open'), ['request_key' => (string) Str::uuid(), 'location_key' => 'gudang', 'expires_at' => now()->addHours(2)->format('Y-m-d\TH:i'), 'notes' => 'Hitung stok siap jual saja.', 'confirmed' => true])->assertSessionHas('success');
        $id = DB::table('stock_opname_sessions')->value('id');
        $this->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $p) => $p->where('products.data.0.quantity', 0));
        $this->assertDatabaseHas('return_quarantine_entries', ['quantity' => 5]);
    }

    public function test_partial_or_excess_receipt_cannot_leave_another_item_unchecked(): void
    {
        [$p, $b, $u, $r] = $this->sendGudang();
        $p2 = $this->product();
        $d2 = GudangReturnDetail::create(['return_id' => $r->id, 'product_id' => $p2->id, 'unit_id' => $p2->unit_id, 'quantity' => 2, 'condition' => 'rusak', 'sent_condition' => 'rusak']);
        $sa = $this->user('super_admin', 'gudang'); $id = $r->details()->orderBy('id')->value('id');
        $this->login($sa)->post(route('gudang.returns.receive', $r), ['items' => [$id => ['received_quantity' => 5, 'condition' => 'Rusak (Defect)']]])->assertSessionHasErrors('items');
        $this->post(route('gudang.returns.receive', $r), ['items' => [$id => ['received_quantity' => 6, 'condition' => 'Rusak (Defect)'], $d2->id => ['received_quantity' => 2, 'condition' => 'Rusak (Defect)']]])->assertSessionHas('error');
        $this->assertDatabaseCount('return_quarantine_entries', 0);
        $this->assertDatabaseHas('gudang_return_details', ['id' => $id, 'received_quantity' => null]);
    }

    public function test_ui_preview_of_session_controls_and_quarantine(): void
    {
        $path = getenv('HEJIRA_RETURN_PREVIEW');
        if (!$path) { $this->assertTrue(true); return; }
        if (!is_dir($path)) mkdir($path, 0777, true);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-13 08:00:00', 'Asia/Jakarta'));
        [$p, $b, $u, $r] = $this->sendGudang(); $this->stock($p, 'jihans_gudang_stock', 10);
        $sa = $this->user('super_admin', 'gudang');
        file_put_contents($path.'/receipt.html', $this->login($sa)->get(route('gudang.returns.show', $r))->assertOk()->getContent());
        $this->receive($r, 'Rusak (Defect)');
        $owner = $this->user('owner', 'all');
        $this->login($owner)->post(route('owner.stock-opname.open'), ['request_key' => (string) Str::uuid(), 'location_key' => 'gudang', 'expires_at' => now()->addHours(2)->format('Y-m-d\TH:i'), 'notes' => 'Hitung seluruh stok siap jual, pisahkan barang rusak.', 'confirmed' => true])->assertSessionHas('success');
        $session = DB::table('stock_opname_sessions')->value('id');
        $this->login($sa)->post(route('stock-opname.count', $session), ['product_id' => $p->id, 'expected_quantity' => 10, 'quantity' => 8, 'notes' => 'Hasil hitung stok siap jual diverifikasi.', 'confirmed' => true])->assertSessionHas('success');
        $this->login($owner)->post(route('owner.stock-opname.close', $session), ['confirmed' => true])->assertSessionHas('success');
        file_put_contents($path.'/owner-closed.html', $this->get(route('stock-opname.show', $session))->assertOk()->getContent());
        file_put_contents($path.'/owner-index.html', $this->get(route('stock-opname.index'))->assertOk()->getContent());
        $this->post(route('owner.stock-opname.reopen', $session), ['request_key' => (string) Str::uuid(), 'expires_at' => now()->addDays(2)->format('Y-m-d\TH:i'), 'notes' => 'Penghitungan dilanjutkan tanpa mengulang produk yang sudah selesai.', 'confirmed' => true])->assertSessionHas('success');
        file_put_contents($path.'/owner-resumed.html', $this->get(route('stock-opname.show', $session))->assertOk()->getContent());
        file_put_contents($path.'/quarantine.html', $this->get(route('return-stock.index'))->assertOk()->getContent());
        file_put_contents($path.'/quarantine-detail.html', $this->get(route('return-stock.show', DB::table('return_quarantine_entries')->value('id')))->assertOk()->getContent());
        $this->assertDatabaseCount('stock_opname_sessions', 1); $this->assertDatabaseCount('stock_opname_counts', 1);
    }

}
