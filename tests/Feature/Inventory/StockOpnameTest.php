<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, Unit, User, HendhysProduction};
use App\Services\{StockService, ActivityLogService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 16:00', 'Asia/Jakarta'));
    }

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $u = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $u->assignRole($role);
        return $u;
    }

    private function branch(string $entity = 'hendhys', string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => Str::random(6), 'name' => 'Cabang Uji '.Str::random(4), 'entity' => $entity, 'type' => $type, 'is_active' => true]);
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $cat = ProductCategory::firstOrCreate(['name' => 'Frozen'], ['entity_scope' => 'all']);
        return Product::create(['code' => Str::random(6), 'name' => 'Brownies Uji', 'category_id' => $cat->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'source_type' => 'purchased']);
    }

    private function stock(Product $p, string $table = 'jihans_gudang_stock', ?Branch $branch = null): void
    {
        $row = ['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 10, 'last_updated' => now()];
        if ($branch) $row['branch_id'] = $branch->id;
        DB::table($table)->insert($row);
    }

    private function openData(string $location = 'gudang'): array
    {
        return ['request_key' => (string) Str::uuid(), 'location_key' => $location, 'expires_at' => '2026-10-12T17:00', 'notes' => 'Hitung seluruh stok siap jual setelah toko tutup.', 'confirmed' => true];
    }

    private function open(string $location = 'gudang'): int
    {
        $this->actingAs($this->user('owner'))->post(route('owner.stock-opname.open'), $this->openData($location))->assertRedirect()->assertSessionHas('success');
        return (int) DB::table('stock_opname_sessions')->max('id');
    }

    private function countData(Product $p, int $quantity = 7): array
    {
        return ['product_id' => $p->id, 'expected_quantity' => 10, 'quantity' => $quantity, 'notes' => 'Hitungan fisik sudah diverifikasi petugas.', 'confirmed' => true];
    }

    public function test_only_owner_opens_closes_and_expiry_is_wib(): void
    {
        $admin = $this->user('super_admin');
        $this->actingAs($admin)->post(route('owner.stock-opname.open'), $this->openData())->assertForbidden();
        $this->assertDatabaseCount('stock_opname_sessions', 0);
        $owner = $this->user('owner');
        $data = $this->openData();
        $this->actingAs($owner)->post(route('owner.stock-opname.open'), $data)->assertRedirect();
        $id = DB::table('stock_opname_sessions')->value('id');
        $this->post(route('owner.stock-opname.open'), $data)->assertRedirect();
        $this->assertDatabaseCount('stock_opname_sessions', 1);
        $this->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $p) => $p->component('StockOpname/Show')->where('session.expires_at', '12/10/2026 17:00 WIB')->where('session.active', true)->where('canCount', false));
        $this->actingAs($admin)->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertForbidden();
        $this->actingAs($owner)->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertRedirect();
        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $id, 'status' => 'closed', 'closed_by' => $owner->id]);
    }

    public function test_gudang_has_no_adjustment_without_session_and_counts_within_session(): void
    {
        $p = $this->product(); $this->stock($p);
        $admin = $this->user('super_admin', 'gudang');
        $this->actingAs($admin)->get(route('stock-opname.index'))->assertInertia(fn (Assert $p) => $p->has('sessions.data', 0)->where('isOwner', false));
        $this->post(route('gudang.stock.adjust'), ['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 0, 'notes' => 'Bypass attempt'])->assertForbidden();
        $id = $this->open();
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($p))->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 7]);
        $this->assertDatabaseHas('stock_opname_counts', ['session_id' => $id, 'product_id' => $p->id, 'quantity_before' => 10, 'quantity_after' => 7, 'difference' => -3, 'counted_by' => $admin->id]);
        $this->assertDatabaseHas('jihans_gudang_stock_movements', ['source' => 'adjustment', 'reference_id' => 1, 'quantity' => 3]);
        $this->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $page) => $page->has('counts', 1)->where('counts.0.counted_by_name', $admin->name));
    }

    public function test_branch_count_is_scoped_and_cannot_count_other_branch_or_stock(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product();
        $this->stock($p, 'hendhys_stock_branch', $a); $this->stock($p, 'hendhys_stock_branch', $b); $this->stock($p, 'hendhys_stock_pusat');
        $id = $this->open('branch-'.$a->id);
        $other = $this->user('kasir_hendhys', 'hendhys', $b);
        $this->actingAs($other)->get(route('stock-opname.show', $id))->assertForbidden();
        $this->post(route('stock-opname.count', $id), $this->countData($p))->assertForbidden();
        $this->get(route('stock-opname.index'))->assertInertia(fn (Assert $p) => $p->has('sessions.data', 0));
        $user = $this->user('kasir_hendhys', 'hendhys', $a);
        $this->actingAs($user)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 7]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 10]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 10]);
        $this->assertDatabaseHas('hendhys_stock_movements', ['branch_id' => $a->id, 'source' => 'adjustment', 'quantity' => 3]);
    }

    public function test_active_branch_is_validated_and_selected_branch_gets_access(): void
    {
        $a = $this->branch(); $b = $this->branch(); $wrong = $this->branch('jihans');
        $id = $this->open('branch-'.$b->id);
        $u = $this->user('admin_hendhys', 'hendhys', $a);
        $this->actingAs($u)->withSession(['active_branch_id' => $wrong->id])->get(route('stock-opname.show', $id))->assertForbidden();
        $this->withSession(['active_branch_id' => $b->id])->get(route('stock-opname.show', $id))->assertOk();
        $this->assertSame($a->id, $u->fresh()->branch_id);
    }

    public function test_closed_and_expired_session_do_not_change_stock(): void
    {
        $p = $this->product(); $this->stock($p); $id = $this->open();
        $admin = $this->user('super_admin', 'gudang');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:00', 'Asia/Jakarta'));
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHasErrors('session_id');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 16:10', 'Asia/Jakarta'));
        $this->actingAs($this->user('owner'))->post(route('owner.stock-opname.close', $id), ['confirmed' => true]);
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHasErrors('session_id');
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 10]);
        $this->assertDatabaseCount('stock_opname_counts', 0);
    }

    public function test_shared_jihans_is_targeted_to_one_branch_and_one_active_session(): void
    {
        $a = $this->branch('jihans'); $b = $this->branch('jihans'); $p = $this->product(); $this->stock($p, 'jihans_retail_stock'); $this->stock($p);
        $id = $this->open('branch-'.$a->id);
        $this->post(route('owner.stock-opname.open'), $this->openData('branch-'.$b->id))->assertSessionHasErrors('location_key');
        $this->actingAs($this->user('kasir_jihans', 'jihans', $b))->post(route('stock-opname.count', $id), $this->countData($p))->assertForbidden();
        $this->actingAs($this->user('kasir_jihans', 'jihans', $a))->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $p) => $p->where('session.stock_key', 'jihans-retail')->where('canCount', true));
        $this->post(route('stock-opname.count', $id), $this->countData($p, 15))->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_retail_stock', ['quantity' => 15]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 10]);
        $this->assertDatabaseHas('jihans_retail_stock_movements', ['quantity' => 5, 'type' => 'in']);
    }

    public function test_stale_duplicate_or_missing_stock_cannot_overwrite_balance(): void
    {
        $p = $this->product(); $this->stock($p); $missing = $this->product(); $id = $this->open();
        $this->actingAs($u = $this->user('super_admin', 'gudang'));
        $data = $this->countData($p); $data['expected_quantity'] = 9;
        $this->post(route('stock-opname.count', $id), $data)->assertSessionHasErrors('expected_quantity');
        $this->post(route('stock-opname.count', $id), $this->countData($missing))->assertSessionHasErrors('product_id');
        $data = $this->countData($p);
        $this->post(route('stock-opname.count', $id), $data)->assertSessionHas('success');
        $this->post(route('stock-opname.count', $id), $data)->assertSessionHas('success');
        $data['quantity'] = 0;
        $this->post(route('stock-opname.count', $id), $data)->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('stock_opname_counts', 1); $this->assertDatabaseCount('jihans_gudang_stock_movements', 1);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 7]);
    }

    public function test_central_production_lots_stay_consistent_and_surplus_is_unknown(): void
    {
        $p = $this->product(); $p->update(['source_type' => 'produced']); $owner = $this->user('owner');
        $prod = HendhysProduction::create(['type' => 'aktual', 'date' => '2026-10-12', 'production_number' => 'HND-OPNAME', 'created_by' => $owner->id]);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 10, null, 'production', $prod->id, $owner->id);
        $branch = $this->branch('hendhys', 'pusat'); $id = $this->open('hendhys-pusat');
        $this->actingAs($this->user('admin_hendhys', 'hendhys', $branch))->post(route('stock-opname.count', $id), $this->countData($p, 7))->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['production_id' => $prod->id, 'remaining' => 7]);
        $this->assertDatabaseHas('production_lot_outflows', ['source' => 'stock_opname', 'reference_id' => 1, 'quantity' => 3]);
        $this->actingAs($owner)->post(route('owner.stock-opname.close', $id), ['confirmed' => true]);
        $new = $this->open('hendhys-pusat');
        $data = $this->countData($p, 12); $data['expected_quantity'] = 7;
        $this->actingAs($this->user('super_admin'))->post(route('stock-opname.count', $new), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['production_id' => $prod->id, 'remaining' => 7]);
        $this->assertDatabaseHas('production_lots', ['production_id' => null, 'remaining' => 5]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 12]);
    }

    public function test_invalid_input_owner_count_and_entity_scope_are_rejected(): void
    {
        $p = $this->product(); $this->stock($p); $id = $this->open();
        $this->post(route('stock-opname.count', $id), $this->countData($p))->assertForbidden();
        $this->actingAs($this->user('super_admin', 'hendhys'))->get(route('stock-opname.show', $id))->assertForbidden();
        $this->actingAs($this->user('super_admin', 'gudang'));
        foreach ([['quantity' => -1], ['quantity' => 1.5], ['notes' => '     '], ['confirmed' => false]] as $extra) {
            $this->post(route('stock-opname.count', $id), $extra + $this->countData($p))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('stock_opname_counts', 0);
        $this->actingAs($this->user('owner'));
        $data = $this->openData(); $data['expires_at'] = '2026-10-12T15:00';
        $this->post(route('owner.stock-opname.open'), $data)->assertSessionHasErrors('expires_at');
        $inactive = $this->branch(); $inactive->update(['is_active' => false]);
        $this->post(route('owner.stock-opname.open'), $this->openData('branch-'.$inactive->id))->assertSessionHasErrors('location_key');
    }

    public function test_logging_failure_rolls_back_count_stock_lots_and_movement(): void
    {
        $p = $this->product(); $this->stock($p); $id = $this->open();
        $this->mock(ActivityLogService::class)->shouldReceive('log')->andThrow(new \RuntimeException('Simulated logging failure'));
        $this->actingAs($this->user('super_admin', 'gudang'))->post(route('stock-opname.count', $id), $this->countData($p))->assertServerError();
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 10]);
        $this->assertDatabaseCount('stock_opname_counts', 0); $this->assertDatabaseCount('jihans_gudang_stock_movements', 0);
    }

    public function test_independent_branches_can_open_together_and_owner_keeps_inactive_history(): void
    {
        $a = $this->branch(); $b = $this->branch();
        $first = $this->open('branch-'.$a->id);
        $second = $this->open('branch-'.$b->id);
        $this->assertNotSame($first, $second);
        $this->post(route('owner.stock-opname.open'), $this->openData('branch-'.$a->id))->assertSessionHasErrors('location_key');
        $a->update(['is_active' => false]);
        $this->get(route('stock-opname.index'))->assertInertia(fn (Assert $p) => $p->has('sessions.data', 2));
        $this->get(route('stock-opname.show', $first))->assertOk();
        $this->actingAs($this->user('admin_hendhys', 'hendhys', $a))->get(route('stock-opname.show', $first))->assertForbidden();
    }

    public function test_no_difference_still_records_count_and_expired_session_can_be_replaced(): void
    {
        $p = $this->product(); $this->stock($p); $first = $this->open();
        $this->actingAs($this->user('super_admin', 'gudang'))->post(route('stock-opname.count', $first), $this->countData($p, 10))->assertSessionHas('success');
        $this->assertDatabaseHas('stock_opname_counts', ['difference' => 0, 'quantity_after' => 10]);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:01', 'Asia/Jakarta'));
        $data = $this->openData(); $data['expires_at'] = '2026-10-12T18:00';
        $this->actingAs($this->user('owner'))->post(route('owner.stock-opname.open'), $data)->assertSessionHas('success');
        $this->assertDatabaseCount('stock_opname_sessions', 2);
        $this->get(route('stock-opname.index'))->assertInertia(fn (Assert $p) => $p->where('sessions.data.0.active', true)->where('sessions.data.1.active', false));
    }

    public function test_gudang_stock_access_requires_owner_session_for_exact_stock_location(): void
    {
        $product = $this->product(); $this->stock($product);
        $admin = $this->user('super_admin');
        $this->actingAs($admin)->get(route('gudang.stock.index'))->assertInertia(fn (Assert $p) => $p
            ->where('opnameAccess.can_count', false)->where('opnameAccess.session', null));
        $this->open('hendhys-pusat');
        $this->actingAs($admin)->get(route('gudang.stock.index'))->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', false));
        $id = $this->open();
        $this->actingAs($admin)->get(route('gudang.stock.index'))->assertInertia(fn (Assert $p) => $p
            ->where('opnameAccess.can_count', true)->where('opnameAccess.session.id', $id)
            ->where('opnameAccess.session.expires_at_iso', '2026-10-12T17:00:00+07:00'));
        $this->post(route('gudang.stock.adjust'), $this->countData($product))->assertForbidden();
        $this->actingAs($this->user('owner'))->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertRedirect();
        $this->actingAs($admin)->get(route('gudang.stock.index'))->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', false));
        $this->post(route('stock-opname.count', $id), $this->countData($product))->assertSessionHasErrors('session_id');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 10]);
        $this->assertDatabaseCount('stock_opname_counts', 0);
    }

    public function test_legacy_adjustment_always_rejects_even_empty_or_invalid_payload(): void
    {
        $product = $this->product(); $this->stock($product);
        $this->actingAs($this->user('super_admin', 'gudang'));
        foreach ([[], ['product_id' => $product->id, 'quantity' => -100]] as $data) {
            $this->post(route('gudang.stock.adjust'), $data)->assertForbidden();
        }
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 10]);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 0);
    }

    public function test_stock_page_access_expires_and_does_not_follow_other_branch_session(): void
    {
        $a = $this->branch(); $b = $this->branch();
        $u = $this->user('admin_hendhys', 'hendhys', $a);
        $id = $this->open('branch-'.$b->id);
        $this->actingAs($u)->get(route('hendhys.stock.index'))->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', false));
        $id = $this->open('branch-'.$a->id);
        $this->actingAs($u)->get(route('hendhys.stock.index'))->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', true)->where('opnameAccess.session.id', $id));
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:00', 'Asia/Jakarta'));
        $this->get(route('hendhys.stock.index'))->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', false));
        $this->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $p) => $p->where('canCount', false));
    }

    public function test_shared_jihans_balance_does_not_grant_another_branch_opname_access(): void
    {
        $a = $this->branch('jihans'); $b = $this->branch('jihans');
        $id = $this->open('branch-'.$a->id);
        $this->actingAs($this->user('admin_jihans', 'jihans', $b))->get(route('jihans.stock.index'))
            ->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', false));
        $this->actingAs($this->user('kasir_jihans', 'jihans', $a))->get(route('jihans.stock.index'))
            ->assertInertia(fn (Assert $p) => $p->where('opnameAccess.can_count', true)->where('opnameAccess.session.id', $id));
    }

    public function test_result_keeps_before_after_code_role_and_actor_snapshots_when_current_stock_changes(): void
    {
        $p = $this->product(); $this->stock($p); $code = $p->code;
        $admin = $this->user('super_admin', 'gudang'); $oldName = $admin->name; $id = $this->open();
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $count = DB::table('stock_opname_counts')->first();
        $this->assertNotNull($count->stock_movement_id);
        $this->assertSame('jihans_gudang_stock_movements', $count->stock_movement_table);
        $p->update(['name' => 'Nama master baru', 'code' => 'CODE-NEW']); $p->delete();
        $admin->update(['name' => 'Petugas diganti']);
        DB::table('jihans_gudang_stock')->where('product_id', $p->id)->update(['quantity' => 5]);
        $this->get(route('stock-opname.result', $count->id))->assertInertia(fn (Assert $page) => $page
            ->component('StockOpname/Result')->where('count.number', 'SO-000001')->where('count.product_name', 'Brownies Uji')
            ->where('count.product_code', $code)->where('count.role_label', 'Super Admin')->where('count.counted_by_name', $oldName)
            ->where('count.quantity_before', 10)->where('count.quantity_after', 7)->where('count.difference', -3)
            ->where('count.created_at', '12/10/2026 16:00:00 WIB')->where('currentStock.quantity', 5)
            ->where('movement.id', $count->stock_movement_id)->where('movement.quantity', 3));
        $this->get(route('stock-opname.history', ['search' => $code]))->assertInertia(fn (Assert $page) => $page->has('counts.data', 1));
    }

    public function test_history_filters_products_actor_session_dates_and_difference(): void
    {
        $a = $this->product(); $b = $this->product(); $c = $this->product();
        foreach ([$a, $b, $c] as $p) $this->stock($p);
        $id = $this->open(); $admin = $this->user('super_admin', 'gudang');
        $this->actingAs($admin);
        foreach ([[$a, 7], [$b, 12], [$c, 10]] as [$p, $quantity]) $this->post(route('stock-opname.count', $id), $this->countData($p, $quantity))->assertSessionHas('success');
        $this->get(route('stock-opname.history'))->assertInertia(fn (Assert $p) => $p->has('counts.data', 3)
            ->where('summary.total', 3)->where('summary.decreased', 1)->where('summary.increased', 1)->where('summary.unchanged', 1));
        foreach (['increase', 'decrease', 'same'] as $diff) $this->get(route('stock-opname.history', ['difference' => $diff]))->assertInertia(fn (Assert $p) => $p->has('counts.data', 1));
        foreach ([['product_id' => $b->id], ['search' => 'SO-000002']] as $filter) $this->get(route('stock-opname.history', $filter))->assertInertia(fn (Assert $p) => $p->has('counts.data', 1)->where('counts.data.0.product_id', $b->id));
        $this->get(route('stock-opname.history', ['counted_by' => $admin->id, 'session_id' => $id, 'location_key' => 'gudang', 'date_from' => '2026-10-12', 'date_to' => '2026-10-12']))->assertInertia(fn (Assert $p) => $p->has('counts.data', 3));
        $this->get(route('stock-opname.history', ['date_from' => '2026-10-13']))->assertInertia(fn (Assert $p) => $p->has('counts.data', 0));
        $this->get(route('stock-opname.history', ['date_from' => '2026-10-13', 'date_to' => '2026-10-12']))->assertSessionHasErrors('date_to');
    }

    public function test_history_permissions_remain_scoped_and_closed_sessions_keep_readable_results(): void
    {
        $a = $this->branch(); $b = $this->branch(); $p = $this->product(); $this->stock($p, 'hendhys_stock_branch', $a);
        $id = $this->open('branch-'.$a->id); $actor = $this->user('kasir_hendhys', 'hendhys', $a);
        $this->actingAs($actor)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $count = DB::table('stock_opname_counts')->value('id');
        $owner = $this->user('owner'); $this->actingAs($owner)->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertRedirect();
        $this->get(route('stock-opname.history'))->assertInertia(fn (Assert $p) => $p->has('counts.data', 1));
        $this->actingAs($actor)->get(route('stock-opname.result', $count))->assertOk();
        $this->post(route('stock-opname.count', $id), $this->countData($p, 8))->assertSessionHasErrors('session_id');
        foreach ([$this->user('kasir_hendhys', 'hendhys', $b), $this->user('super_admin', 'gudang')] as $other) {
            $this->actingAs($other)->get(route('stock-opname.result', $count))->assertForbidden();
            $this->get(route('stock-opname.history', ['session_id' => $id]))->assertInertia(fn (Assert $p) => $p->has('counts.data', 0)->has('actors', 0)->has('locations', 0));
        }
        $this->actingAs($this->user('super_admin', 'all'))->get(route('stock-opname.result', $count))->assertOk();
        $this->get(route('stock-opname.result', 999999))->assertNotFound();
    }

    public function test_legacy_result_has_no_fabricated_snapshots_and_ambiguous_movement_is_not_linked(): void
    {
        $p = $this->product(); $this->stock($p); $id = $this->open();
        $this->actingAs($this->user('super_admin', 'gudang'))->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $count = (int) DB::table('stock_opname_counts')->value('id');
        DB::table('stock_opname_counts')->where('id', $count)->update(['product_code' => null, 'counted_by_role' => null, 'stock_movement_table' => null, 'stock_movement_id' => null]);
        $p->update(['code' => 'CURRENT-CODE']);
        $this->get(route('stock-opname.result', $count))->assertInertia(fn (Assert $p) => $p->where('count.product_code', null)->where('count.current_product_code', 'CURRENT-CODE')
            ->where('count.role_label', null)->where('movement.verified_by', 'Jejak dokumen SO lama yang cocok'));
        $movement = (array) DB::table('jihans_gudang_stock_movements')->first(); unset($movement['id']);
        DB::table('jihans_gudang_stock_movements')->insert($movement);
        $this->get(route('stock-opname.result', $count))->assertInertia(fn (Assert $p) => $p->where('movement', null)->where('count.quantity_before', 10)->where('count.quantity_after', 7));
    }

    public function test_result_shows_production_lots_consumed_by_opname(): void
    {
        $p = $this->product(); $p->update(['source_type' => 'produced']); $owner = $this->user('owner');
        $production = HendhysProduction::create(['type' => 'aktual', 'date' => '2026-10-12', 'production_number' => 'HND-SO-TRACE', 'created_by' => $owner->id]);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 10, null, 'production', $production->id, $owner->id);
        $session = $this->open('hendhys-pusat');
        $this->actingAs($this->user('super_admin', 'all'))->post(route('stock-opname.count', $session), $this->countData($p))->assertSessionHas('success');
        $this->get(route('stock-opname.result', 1))->assertInertia(fn (Assert $p) => $p->has('lotEffects', 1)->where('lotEffects.0.production_number', 'HND-SO-TRACE')->where('lotEffects.0.quantity', 3));
    }

    public function test_ui_preview_from_isolated_opname_records(): void
    {
        $path = getenv('HEJIRA_OPNAME_PREVIEW');
        if (!$path) { $this->assertTrue(true); return; }
        $a = $this->branch(); $a->update(['name' => 'Hendhys Pasar 6 (data uji)']);
        $this->branch('jihans')->update(['name' => 'Jihan’s Food (data uji)']);
        $p = $this->product(); $this->stock($p, 'hendhys_stock_branch', $a);
        $owner = $this->user('owner'); $owner->update(['name' => 'Owner Uji']);
        $this->actingAs($owner);
        $index = $this->get(route('stock-opname.index'))->assertOk();
        file_put_contents($path.'/owner.html', $index->getContent());
        $this->post(route('owner.stock-opname.open'), $this->openData('branch-'.$a->id))->assertSessionHas('success');
        $id = (int) DB::table('stock_opname_sessions')->value('id');
        $u = $this->user('kasir_hendhys', 'hendhys', $a); $u->update(['name' => 'Petugas Uji']);
        $before = $this->actingAs($u)->get(route('stock-opname.show', $id))->assertOk();
        file_put_contents($path.'/count.html', $before->getContent());
        $this->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $after = $this->actingAs($owner)->get(route('stock-opname.show', $id))->assertOk();
        file_put_contents($path.'/result.html', $after->getContent());
        $this->post(route('owner.stock-opname.close', $id), ['confirmed' => true]);
        $closed = $this->actingAs($u)->get(route('stock-opname.show', $id))->assertOk();
        file_put_contents($path.'/closed.html', $closed->getContent());
        $this->travelBack();
        $this->stock($p);
        $admin = $this->user('super_admin', 'all'); $admin->update(['name' => 'Super Admin Uji']);
        $locked = $this->actingAs($admin)->get(route('gudang.stock.index'))->assertOk();
        file_put_contents($path.'/gudang-locked.html', $locked->getContent());
        $data = $this->openData(); $data['expires_at'] = now('Asia/Jakarta')->addHours(2)->format('Y-m-d\TH:i');
        $this->actingAs($owner)->post(route('owner.stock-opname.open'), $data)->assertSessionHas('success');
        $gudangId = (int) DB::table('stock_opname_sessions')->max('id');
        file_put_contents($path.'/gudang-active.html', $this->actingAs($admin)->get(route('gudang.stock.index'))->assertOk()->getContent());
        file_put_contents($path.'/gudang-count.html', $this->get(route('stock-opname.show', $gudangId))->assertOk()->getContent());
        $this->post(route('stock-opname.count', $gudangId), $this->countData($p))->assertSessionHas('success');
        $countId = (int) DB::table('stock_opname_counts')->max('id');
        file_put_contents($path.'/gudang-counted.html', $this->get(route('stock-opname.show', $gudangId))->assertOk()->getContent());
        file_put_contents($path.'/opname-history.html', $this->get(route('stock-opname.history', ['counted_by' => $admin->id]))->assertOk()->getContent());
        file_put_contents($path.'/opname-result.html', $this->get(route('stock-opname.result', $countId))->assertOk()->getContent());

        $this->actingAs($owner)->post(route('owner.stock-opname.close', $gudangId), ['confirmed' => true])->assertRedirect();
        file_put_contents($path.'/gudang-closed.html', $this->actingAs($admin)->get(route('gudang.stock.index'))->assertOk()->getContent());

    }
    public function test_owner_reopens_expired_session_and_continues_same_counts_with_audit(): void
    {
        $p = $this->product(); $this->stock($p); $other = $this->product(); $this->stock($other);
        $id = $this->open(); $admin = $this->user('super_admin', 'gudang');
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($p))->assertSessionHas('success');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:01', 'Asia/Jakarta'));
        $this->post(route('stock-opname.count', $id), $this->countData($other))->assertSessionHasErrors('session_id');
        $data = ['request_key' => (string) Str::uuid(), 'expires_at' => '2026-10-12T19:00', 'notes' => 'Hitungan belum selesai, lanjutkan produk lainnya.', 'confirmed' => true];
        $this->post(route('owner.stock-opname.reopen', $id), $data)->assertForbidden();
        $owner = $this->user('owner');
        $this->actingAs($owner)->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHas('success');
        $this->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHas('success');
        $this->assertDatabaseCount('stock_opname_sessions', 1);
        $this->assertDatabaseCount('stock_opname_session_events', 2);
        $this->assertDatabaseCount('stock_opname_counts', 1);
        $this->assertDatabaseHas('stock_opname_session_events', ['session_id' => $id, 'action' => 'reopen', 'previous_expires_at' => '2026-10-12 17:00:00', 'owner_id' => $owner->id]);
        $this->actingAs($admin)->post(route('stock-opname.count', $id), $this->countData($other))->assertSessionHas('success');
        $this->post(route('stock-opname.count', $id), $this->countData($p, 2))->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('stock_opname_counts', 2);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 7]);
        $this->get(route('stock-opname.show', $id))->assertInertia(fn (Assert $p) => $p->where('canCount', true)->has('counts', 2)->has('events', 2));
    }

    public function test_closed_session_reopens_without_erasing_owner_close_history(): void
    {
        $id = $this->open(); $owner = $this->user('owner');
        $this->actingAs($owner)->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertSessionHas('success');
        $this->post(route('owner.stock-opname.close', $id), ['confirmed' => true])->assertSessionHas('success');
        $this->assertDatabaseCount('stock_opname_session_events', 2);
        $data = ['request_key' => (string) Str::uuid(), 'expires_at' => '2026-10-12T20:00', 'notes' => 'Petugas melanjutkan penghitungan yang tersisa.', 'confirmed' => true];
        $this->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('stock_opname_session_events', ['session_id' => $id, 'action' => 'close', 'owner_name' => $owner->name]);
        $this->assertDatabaseHas('stock_opname_session_events', ['session_id' => $id, 'action' => 'reopen', 'previous_closed_by_name' => $owner->name]);
        $this->assertDatabaseHas('stock_opname_sessions', ['id' => $id, 'status' => 'open', 'closed_at' => null]);
    }

    public function test_reopen_requires_new_future_expiry_confirmation_and_no_conflicting_shared_session(): void
    {
        $id = $this->open(); $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:01', 'Asia/Jakarta'));
        $owner = $this->user('owner');
        $data = ['request_key' => (string) Str::uuid(), 'expires_at' => '2026-10-12T16:00', 'notes' => 'Lanjut menghitung semua produk.', 'confirmed' => true];
        $this->actingAs($owner)->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHasErrors('expires_at');
        $data['expires_at'] = '2026-10-12T20:00'; $data['confirmed'] = false;
        $this->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHasErrors('confirmed');
        $new = $this->openData(); $new['expires_at'] = '2026-10-12T21:00';
        $this->post(route('owner.stock-opname.open'), $new)->assertSessionHas('success');
        $data['confirmed'] = true;
        $this->post(route('owner.stock-opname.reopen', $id), $data)->assertSessionHasErrors('expires_at');
        $this->assertDatabaseCount('stock_opname_session_events', 2);
    }

}
