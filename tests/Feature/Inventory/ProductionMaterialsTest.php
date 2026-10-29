<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, HendhysProduction, Product, ProductCategory, Unit, User};
use App\Services\{ActivityLogService, ProductionMaterials, StockService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionMaterialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 01:30', 'Asia/Jakarta'));
    }

    private function branch(string $type = 'cabang', string $entity = 'hendhys'): Branch
    {
        return Branch::create(['code' => Str::random(8), 'name' => 'Cabang '.Str::random(6), 'entity' => $entity, 'type' => $type, 'is_active' => true]);
    }

    private function user(Branch $branch, string $role = 'kasir_hendhys', string $entity = 'hendhys'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch->id]);
        $user->assignRole($role);
        return $user;
    }

    private function product(string $source = 'purchased'): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        return Product::create(['name' => 'Bahan Uji '.Str::random(4), 'code' => Str::random(8), 'category_id' => $category->id,
            'unit_id' => $unit->id, 'source_type' => $source, 'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active']);
    }

    private function stock(Branch $branch, Product $product, int $quantity = 20): void
    {
        $row = ['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => $quantity, 'quantity_return' => 3, 'last_updated' => now()];
        if ($branch->type !== 'pusat') $row['branch_id'] = $branch->id;
        DB::table($branch->type === 'pusat' ? 'hendhys_stock_pusat' : 'hendhys_stock_branch')->insert($row);
    }

    private function data(Branch $branch, Product $product, int $quantity = 7): array
    {
        return ['request_key' => (string) Str::uuid(), 'branch_id' => $branch->id, 'date' => '2026-10-14', 'time' => '01:15',
            'receiver_name' => 'Siti Tim Produksi', 'reason' => 'Bahan untuk brownies pagi', 'notes' => 'Barang dalam kondisi baik',
            'evidence_reference' => 'BUKTI-PAGI-12', 'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'notes' => 'Kemasan tertutup']]];
    }

    public function test_only_hendhys_cashier_can_use_every_endpoint_and_direct_service(): void
    {
        $branch = $this->branch(); $p = $this->product(); $this->stock($branch, $p);
        foreach (['owner', 'admin_hendhys', 'admin_jihans', 'kasir_jihans'] as $role) {
            $actor = $this->user($branch, $role); $this->actingAs($actor);
            foreach (['production-materials.index', 'production-materials.create'] as $route) $this->get(route($route))->assertForbidden();
            $this->get(route('production-materials.show', 1))->assertForbidden();
            $this->getJson(route('production-materials.create', ['lookup' => 'products']))->assertForbidden();
            $this->post(route('production-materials.store'), $this->data($branch, $p))->assertForbidden();
            $this->post(route('production-materials.cancel', 1), ['reason' => 'Salah input', 'confirmed' => true])->assertForbidden();
            try { app(ProductionMaterials::class)->record($this->data($branch, $p), $actor); $this->fail('Other roles cannot call the service.'); }
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        }
        Role::findOrCreate('super_admin', 'web');
        $mixed = $this->user($branch); $mixed->assignRole('super_admin');
        $this->actingAs($mixed)->get(route('production-materials.create'))->assertForbidden();
        $this->assertDatabaseCount('production_material_transactions', 0);
    }

    public function test_one_save_deducts_only_active_branch_uses_master_unit_and_records_complete_audit(): void
    {
        $branch = $this->branch(); $other = $this->branch(); $centre = $this->branch('pusat');
        $actor = $this->user($branch); $p = $this->product();
        $pack = Unit::create(['name' => 'Kemasan', 'abbreviation' => 'PACK', 'entity_scope' => 'all']); $p->update(['unit_id' => $pack->id]);
        $this->stock($branch, $p); $this->stock($other, $p, 40); $this->stock($centre, $p, 50);
        foreach (['jihans_gudang_stock', 'jihans_retail_stock'] as $table) DB::table($table)->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 60]);
        $this->actingAs($actor)->post(route('production-materials.store'), $this->data($branch, $p))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $branch->id, 'quantity' => 13, 'quantity_return' => 3]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $other->id, 'quantity' => 40]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 50]);
        foreach (['jihans_gudang_stock', 'jihans_retail_stock'] as $table) $this->assertDatabaseHas($table, ['quantity' => 60]);
        $this->assertDatabaseHas('production_material_transactions', ['branch_id' => $branch->id, 'branch_name' => $branch->name, 'stock_location' => 'hendhys_stock_branch',
            'kind' => 'branch_usage', 'number' => 'BPR-20261014-0001', 'status' => 'completed', 'production_id' => null, 'created_by_name' => $actor->name, 'receiver_name' => 'Siti Tim Produksi']);
        $this->assertDatabaseHas('production_material_items', ['unit_name' => 'PACK', 'quantity' => 7, 'source_before' => 20, 'source_after' => 13, 'destination_before' => null, 'destination_after' => null]);
        $this->assertDatabaseHas('hendhys_stock_movements', ['branch_id' => $branch->id, 'production_material_id' => 1, 'type' => 'out', 'quantity_before' => 20, 'quantity_after' => 13]);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 0); $this->assertDatabaseCount('production_lots', 0);
        $this->get(route('production-materials.show', 1))->assertInertia(fn (Assert $page) => $page->where('transaction.occurred_label', '14/10/2026 01:15:00 WIB')
            ->where('transaction.branch_name', $branch->name)->has('events.0.stock_changes', 1)->where('events.0.stock_changes.0.branch_id', $branch->id));
    }

    public function test_session_branch_is_used_instead_of_account_branch_and_stale_form_is_rejected(): void
    {
        $a = $this->branch(); $b = $this->branch(); $actor = $this->user($a); $p = $this->product();
        $this->stock($a, $p); $this->stock($b, $p, 30);
        $this->actingAs($actor)->withSession(['active_branch_id' => $b->id])->get(route('production-materials.create'))
            ->assertInertia(fn (Assert $page) => $page->where('location.branch_id', $b->id)->where('products.0.quantity', 30)->missing('productions')->missing('kinds')->missing('lots'));
        $this->post(route('production-materials.store'), $this->data($a, $p))->assertSessionHasErrors('branch_id');
        $this->assertDatabaseCount('production_material_transactions', 0);
        $this->post(route('production-materials.store'), $this->data($b, $p))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $a->id, 'quantity' => 20]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $b->id, 'quantity' => 23]);
        $this->assertDatabaseHas('master_users', ['id' => $actor->id, 'branch_id' => $a->id]);
    }

    public function test_catalogue_and_history_are_scoped_paginated_and_search_all_products(): void
    {
        $branch = $this->branch(); $other = $this->branch(); $actor = $this->user($branch);
        for ($i = 1; $i <= 45; $i++) {
            $p = $this->product(); $p->update(['name' => sprintf('Bahan %03d', $i)]); $this->stock($branch, $p);
            app(ProductionMaterials::class)->record($this->data($branch, $p), $actor);
        }
        $foreign = $this->product(); $foreign->update(['name' => 'Rahasia cabang lain']); $this->stock($other, $foreign);
        app(ProductionMaterials::class)->record($this->data($other, $foreign), $this->user($other));
        $this->actingAs($actor)->get(route('production-materials.create'))->assertInertia(fn (Assert $p) => $p->has('products', 20)->where('catalogue.has_more', true)->where('today', '2026-10-14')->where('currentTime', '01:30'));
        $this->getJson(route('production-materials.create', ['lookup' => 'products', 'q' => 'Bahan 045']))->assertJsonCount(1, 'products')->assertJsonPath('products.0.name', 'Bahan 045');
        $this->getJson(route('production-materials.create', ['lookup' => 'products', 'q' => 'Rahasia']))->assertJsonCount(0, 'products');
        $this->get(route('production-materials.index'))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 20)->where('transactions.total', 45));
        $this->get(route('production-materials.index', ['per_page' => 'all']))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 45));
        $this->get(route('production-materials.index', ['search' => 'Rahasia']))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 0));
        $this->get(route('production-materials.index', ['search' => 'Bahan 045']))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 1));
        $this->get(route('production-materials.show', 46))->assertForbidden();
        $this->post(route('production-materials.cancel', 46), ['reason' => 'Salah input barang', 'confirmed' => true])->assertForbidden();
    }

    public function test_old_warehouse_records_are_preserved_and_cannot_be_used_as_branch_usage(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $p = $this->product(); $this->stock($branch, $p);
        $legacy = DB::table('production_material_transactions')->insertGetId(['request_key' => Str::uuid(), 'payload_hash' => str_repeat('a', 64),
            'number' => 'BPR-LAMA', 'kind' => 'warehouse_transfer', 'status' => 'completed', 'date' => '2026-10-13', 'occurred_at' => now()->subDay(),
            'sender_name' => 'Super Admin', 'receiver_name' => 'Petugas pusat', 'reason' => 'Pengiriman lama', 'created_by' => $actor->id, 'created_by_name' => 'Super Admin', 'created_at' => now()->subDay()]);
        $this->actingAs($actor)->get(route('production-materials.index'))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 0));
        $this->get(route('production-materials.show', $legacy))->assertForbidden();
        $this->post(route('production-materials.cancel', $legacy), ['reason' => 'Percobaan salah input', 'confirmed' => true])->assertForbidden();
        $this->assertDatabaseHas('production_material_transactions', ['id' => $legacy, 'number' => 'BPR-LAMA', 'status' => 'completed', 'branch_id' => null]);
        $old = $this->data($branch, $p); $old['kind'] = 'warehouse_transfer'; $old['production_id'] = 1;
        $this->post(route('production-materials.store'), $old)->assertSessionHasErrors(['kind', 'production_id']);
        $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 20]);
    }

    public function test_insufficient_or_missing_stock_rolls_back_every_item_event_and_sequence(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $a = $this->product(); $b = $this->product();
        $this->stock($branch, $a); $this->stock($branch, $b, 2);
        $data = $this->data($branch, $a); $data['items'][] = ['product_id' => $b->id, 'quantity' => 7];
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHasErrors('items');
        $missing = $this->product(); $this->post(route('production-materials.store'), $this->data($branch, $missing))->assertSessionHasErrors('items');
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $a->id, 'quantity' => 20]);
        foreach (['production_material_transactions', 'production_material_items', 'production_material_sequences', 'production_material_events', 'hendhys_stock_movements'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_unit_mismatch_and_invalid_input_cannot_change_stock(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $p = $this->product(); $this->stock($branch, $p);
        $box = Unit::create(['name' => 'Kotak', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        DB::table('hendhys_stock_branch')->update(['unit_id' => $box->id]);
        $this->actingAs($actor)->get(route('production-materials.create'))->assertInertia(fn (Assert $p) => $p->where('products.0.unit_matches', false));
        $this->post(route('production-materials.store'), $this->data($branch, $p))->assertSessionHasErrors('items');
        DB::table('hendhys_stock_branch')->update(['unit_id' => $p->unit_id]);
        foreach ([['quantity' => 1.5, 'key' => 'items.0.quantity'], ['quantity' => 0, 'key' => 'items.0.quantity']] as $invalid) {
            $data = $this->data($branch, $p); $data['items'][0]['quantity'] = $invalid['quantity'];
            $this->post(route('production-materials.store'), $data)->assertSessionHasErrors($invalid['key']);
        }
        $data = $this->data($branch, $p); $data['items'][] = $data['items'][0];
        $this->post(route('production-materials.store'), $data)->assertSessionHasErrors('items.0.product_id');
        $data = $this->data($branch, $p); $data['items'][0]['unit_id'] = $box->id; $data['items'][0]['lot_id'] = 1;
        $this->post(route('production-materials.store'), $data)->assertSessionHasErrors(['items.0.unit_id', 'items.0.lot_id']);
        $data = $this->data($branch, $p); $data['reason'] = '     ';
        $this->post(route('production-materials.store'), $data)->assertSessionHasErrors('reason');
        $data = $this->data($branch, $p); $data['time'] = '01:31';
        $this->post(route('production-materials.store'), $data)->assertSessionHasErrors('time');
        $this->assertDatabaseCount('production_material_transactions', 0); $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 20]);
    }

    public function test_duplicate_submissions_are_idempotent_and_numbers_follow_usage_date(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $p = $this->product(); $this->stock($branch, $p, 50);
        $data = $this->data($branch, $p);
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 43]); $this->assertDatabaseCount('production_material_transactions', 1);
        $changed = $data; $changed['items'][0]['quantity'] = 8;
        $this->post(route('production-materials.store'), $changed)->assertSessionHasErrors('items');
        $this->post(route('production-materials.store'), $this->data($branch, $p))->assertSessionHas('success');
        $old = $this->data($branch, $p); $old['date'] = '2026-10-13';
        $this->post(route('production-materials.store'), $old)->assertSessionHas('success');
        foreach (['BPR-20261014-0001', 'BPR-20261014-0002', 'BPR-20261013-0001'] as $number) $this->assertDatabaseHas('production_material_transactions', ['number' => $number]);
    }

    public function test_cancellation_restores_same_branch_once_with_snapshots_and_searchable_history(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $p = $this->product(); $this->stock($branch, $p);
        $data = $this->data($branch, $p); $name = $p->name; $code = $p->code; $branchName = $branch->name; $actorName = $actor->name;
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $cancel = ['reason' => 'Salah jumlah, barang belum dipakai', 'confirmed' => true];
        $this->post(route('production-materials.cancel', 1), $cancel)->assertSessionHas('success');
        $this->post(route('production-materials.cancel', 1), $cancel)->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $branch->id, 'quantity' => 20, 'quantity_return' => 3]);
        $this->assertDatabaseCount('production_material_events', 2); $this->assertDatabaseCount('hendhys_stock_movements', 2);
        $this->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 20]);
        $p->update(['name' => 'Nama berubah', 'code' => 'BARU']); $branch->update(['name' => 'Cabang berubah']); $actor->update(['name' => 'Petugas berubah']);
        foreach ([$name, $code, $branchName, $actorName, $cancel['reason'], 'BUKTI-PAGI-12'] as $search) $this->get(route('production-materials.index', ['search' => $search]))->assertInertia(fn (Assert $p) => $p->has('transactions.data', 1));
        $this->get(route('production-materials.show', 1))->assertInertia(fn (Assert $p) => $p->where('transaction.status', 'cancelled')->where('transaction.branch_name', $branchName)
            ->where('items.0.product_name', $name)->where('events.1.stock_changes.0.before', 13)->where('events.1.stock_changes.0.after', 20));
    }

    public function test_centre_cashier_uses_centre_stock_and_preserves_original_production_lots_on_reversal(): void
    {
        $centre = $this->branch('pusat'); $other = $this->branch(); $actor = $this->user($centre); $p = $this->product('produced');
        $production = HendhysProduction::create(['production_number' => 'HND-ASAL-1', 'date' => '2026-10-13', 'type' => 'aktual', 'created_by' => $actor->id]);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 20, $centre->id, 'production', $production->id, $actor->id);
        $this->stock($other, $p, 70);
        $this->actingAs($actor)->post(route('production-materials.store'), $this->data($centre, $p))->assertSessionHas('success');
        $lot = DB::table('production_lots')->first();
        $this->assertSame(13, (int) $lot->remaining);
        $this->assertDatabaseHas('hendhys_stock_movements', ['production_material_id' => 1, 'branch_id' => null, 'quantity_after' => 13]);
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Barang belum digunakan', 'confirmed' => true])->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 20]); $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 70]);
        $this->assertDatabaseHas('production_lots', ['id' => $lot->id, 'production_number' => 'HND-ASAL-1', 'remaining' => 20]);
        $this->assertDatabaseHas('production_lot_outflows', ['detail_id' => 1, 'source' => 'production_material', 'restored_quantity' => 7]);
    }

    public function test_invalid_branch_entity_and_inactive_branch_are_rejected(): void
    {
        $foreign = $this->branch('cabang', 'jihans'); $actor = $this->user($foreign);
        $this->actingAs($actor)->get(route('production-materials.create'))->assertForbidden();
        $branch = $this->branch(); $branch->update(['is_active' => false]);
        $this->actingAs($this->user($branch))->withSession(['active_branch_id' => $branch->id])->get(route('production-materials.create'))->assertForbidden();
    }

    public function test_logging_failure_rolls_back_stock_and_all_records(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $p = $this->product(); $this->stock($branch, $p);
        $this->mock(ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit failed'));
        try { app(ProductionMaterials::class)->record($this->data($branch, $p), $actor); $this->fail('Must fail atomically.'); }
        catch (\RuntimeException $error) { $this->assertSame('Audit failed', $error->getMessage()); }
        $this->assertDatabaseHas('hendhys_stock_branch', ['quantity' => 20]);
        foreach (['production_material_transactions', 'production_material_items', 'production_material_events', 'production_material_sequences', 'hendhys_stock_movements'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_cancellation_of_multiple_items_rolls_back_when_one_stock_unit_changed(): void
    {
        $branch = $this->branch(); $actor = $this->user($branch); $a = $this->product(); $b = $this->product();
        $this->stock($branch, $a); $this->stock($branch, $b);
        $data = $this->data($branch, $a); $data['items'][] = ['product_id' => $b->id, 'quantity' => 4];
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $unit = Unit::create(['name' => 'Kotak', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        DB::table('hendhys_stock_branch')->where('product_id', $b->id)->update(['unit_id' => $unit->id]);
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Salah input dua barang', 'confirmed' => true])->assertSessionHasErrors('items');
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $a->id, 'quantity' => 13]);
        $this->assertDatabaseHas('production_material_transactions', ['status' => 'completed']);
        $this->assertDatabaseCount('production_material_events', 1);
    }

    public function test_super_admin_can_record_view_filter_and_cancel_across_active_hendhys_branches(): void
    {
        $pusat = $this->branch('pusat');
        $cabang = $this->branch('cabang');
        $inactive = $this->branch('cabang'); $inactive->update(['is_active' => false]);
        $jihans = $this->branch('cabang', 'jihans');

        Role::findOrCreate('super_admin', 'web');
        $superAdmin = User::factory()->create(['entity' => 'all', 'branch_id' => null]);
        $superAdmin->assignRole('super_admin');

        $p = $this->product();
        $this->stock($pusat, $p, 35);
        $this->stock($cabang, $p, 25);

        $this->actingAs($superAdmin)->get(route('production-materials.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('location.branch_id', $pusat->id)
                ->has('branches', 4)
                ->where('products.0.quantity', 35));

        $this->get(route('production-materials.create', ['branch_id' => $cabang->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('location.branch_id', $cabang->id)
                ->where('products.0.quantity', 25));

        $this->getJson(route('production-materials.create', ['lookup' => 'products', 'branch_id' => $cabang->id]))
            ->assertJsonPath('branch_id', $cabang->id)
            ->assertJsonPath('products.0.quantity', 25);

        $this->post(route('production-materials.store'), $this->data($inactive, $p))->assertSessionHasErrors('branch_id');
        $this->post(route('production-materials.store'), $this->data($jihans, $p))->assertSessionHasErrors('items');

        $this->post(route('production-materials.store'), $this->data($pusat, $p, 5))->assertSessionHas('success');
        $this->post(route('production-materials.store'), $this->data($cabang, $p, 8))->assertSessionHas('success');

        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 30]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $cabang->id, 'product_id' => $p->id, 'quantity' => 17]);

        $this->get(route('production-materials.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('location', null)
                ->has('branches', 4)
                ->where('transactions.total', 2));

        $this->get(route('production-materials.index', ['branch_id' => $cabang->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('location.branch_id', $cabang->id)
                ->where('transactions.total', 1));

        $this->get(route('production-materials.show', 2))
            ->assertInertia(fn (Assert $page) => $page->where('transaction.branch_id', $cabang->id));

        $this->post(route('production-materials.cancel', 2), ['reason' => 'Dibatalkan oleh super admin', 'confirmed' => true])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $cabang->id, 'product_id' => $p->id, 'quantity' => 25]);
    }

    public function test_super_admin_uses_and_restores_the_selected_units_stock_with_its_own_audit(): void
    {
        $admin = $this->user($this->branch('pusat'), 'super_admin', 'all');
        $tables = ['gudang' => 'jihans_gudang_stock', 'jihans' => 'jihans_retail_stock', 'hendhys' => 'hendhys_stock_branch'];
        $movements = ['gudang' => 'jihans_gudang_stock_movements', 'jihans' => 'jihans_retail_stock_movements', 'hendhys' => 'hendhys_stock_movements'];
        $other = $this->branch('cabang', 'izzi');
        $this->actingAs($admin);
        foreach ($tables as $entity => $table) {
            $branch = $this->branch($entity === 'gudang' ? 'pusat' : 'cabang', $entity);
            $product = $this->product();
            $product->update(['entity_scope' => $entity, 'visible_gudang' => false, 'visible_jihans' => false, 'visible_hendhys' => false]);
            // Seed every stock pool to prove only the selected source changes.
            foreach ($tables as $pool => $stockTable) {
                $row = ['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 40];
                if ($pool === 'hendhys') $row['branch_id'] = $branch->id;
                DB::table($stockTable)->insert($row);
            }
            DB::table('izzi_stock')->insert(['branch_id' => $other->id, 'product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 90000, 'last_updated' => now()]);
            $this->get(route('production-materials.create', ['branch_id' => $branch->id]))
                ->assertInertia(fn (Assert $page) => $page->where('location.stock_location', $table)->where('products.0.id', $product->id));
            $this->getJson(route('production-materials.create', ['branch_id' => $branch->id, 'lookup' => 'products', 'q' => $product->code]))
                ->assertJsonCount(1, 'products')->assertJsonPath('products.0.quantity', 40);
            $data = $this->data($branch, $product);
            $this->post(route('production-materials.store'), $data)->assertSessionHas('success');
            $this->post(route('production-materials.store'), $data)->assertSessionHas('success');
            $id = DB::table('production_material_transactions')->where('request_key', $data['request_key'])->value('id');
            foreach ($tables as $pool => $stockTable) {
                $this->assertDatabaseHas($stockTable, ['product_id' => $product->id, 'quantity' => $pool === $entity ? 33 : 40]);
            }
            $this->assertDatabaseHas('izzi_stock', ['branch_id' => $other->id, 'product_id' => $product->id, 'quantity' => 90000]);
            $this->assertDatabaseHas($movements[$entity], ['production_material_id' => $id, 'quantity' => 7, 'type' => 'out']);
            $this->get(route('production-materials.show', $id))->assertInertia(fn (Assert $page) => $page
                ->where('items.0.quantity', 7)->where('items.0.source_after', 33));
            $this->get(route('production-materials.index', ['branch_id' => $branch->id]))->assertInertia(fn (Assert $page) => $page
                ->where('transactions.total', 1)->where('transactions.data.0.items.0.quantity', 7));
            $cancel = ['reason' => 'Barang belum digunakan untuk produksi', 'confirmed' => true];
            $this->post(route('production-materials.cancel', $id), $cancel)->assertSessionHas('success');
            $this->post(route('production-materials.cancel', $id), $cancel)->assertSessionHas('success');
            $this->assertDatabaseHas($table, ['product_id' => $product->id, 'quantity' => 40]);
            $this->assertSame(2, DB::table($movements[$entity])->where('production_material_id', $id)->count());
        }
    }

    public function test_warehouse_production_lots_are_deducted_and_restored(): void
    {
        $warehouse = $this->branch('pusat', 'gudang');
        $admin = $this->user($warehouse, 'super_admin', 'all');
        $p = $this->product('produced');
        DB::table('jihans_gudang_stock')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20]);
        $this->actingAs($admin)->post(route('production-materials.store'), $this->data($warehouse, $p))->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['entity' => 'jihans', 'product_id' => $p->id, 'remaining' => 13]);
        $this->get(route('production-materials.show', 1))->assertInertia(fn (Assert $page) => $page->where('items.0.lots.0.quantity', 7));
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Bahan produksi belum digunakan', 'confirmed' => true])->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['entity' => 'jihans', 'product_id' => $p->id, 'remaining' => 20]);
        $this->assertDatabaseHas('production_lot_outflows', ['reference_id' => 1, 'source' => 'production_material', 'restored_quantity' => 7]);
    }

    public function test_izzi_is_excluded_from_branch_choices_and_rejected_for_direct_requests(): void
    {
        $hendhys = $this->branch();
        $warehouse = Branch::where('entity', 'gudang')->firstOrFail();
        $jihans = $this->branch('cabang', 'jihans');
        $izzi = $this->branch('cabang', 'izzi');
        $admin = $this->user($hendhys, 'super_admin', 'all');
        $product = $this->product();
        DB::table('izzi_stock')->insert(['branch_id' => $izzi->id, 'product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 40000, 'last_updated' => now()]);
        $allowedIds = collect([$hendhys->id, $warehouse->id, $jihans->id])->sort()->values()->all();
        $this->actingAs($admin);
        foreach (['production-materials.create', 'production-materials.index'] as $route) {
            $this->get(route($route))->assertInertia(fn (Assert $page) => $page
                ->has('branches', 3)
                ->where('branches', fn ($branches) => $branches->pluck('id')->sort()->values()->all() === $allowedIds));
            $this->getJson(route($route, ['branch_id' => $izzi->id]))->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        }
        $this->getJson(route('production-materials.create', ['branch_id' => $izzi->id, 'lookup' => 'products']))
            ->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->post(route('production-materials.store'), $this->data($izzi, $product))->assertSessionHasErrors('branch_id');
        $this->assertDatabaseHas('izzi_stock', ['branch_id' => $izzi->id, 'product_id' => $product->id, 'quantity' => 40000]);
        foreach (['production_material_transactions', 'production_material_items', 'production_material_events', 'izzi_stock_movements'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_other_units_remain_inaccessible_to_hendhys_cashier_and_scoped_super_admin(): void
    {
        $hendhys = $this->branch(); $warehouse = $this->branch('pusat', 'gudang');
        $product = $this->product(); $this->stock($hendhys, $product);
        DB::table('jihans_gudang_stock')->insert(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 50]);
        $allAdmin = $this->user($hendhys, 'super_admin', 'all');
        $this->actingAs($allAdmin)->post(route('production-materials.store'), $this->data($warehouse, $product))->assertSessionHas('success');
        foreach (['kasir_hendhys', 'super_admin'] as $role) {
            $actor = $this->user($hendhys, $role);
            $this->actingAs($actor)->get(route('production-materials.create'))->assertInertia(fn (Assert $page) => $page->has('branches', 1)->where('branches.0.entity', 'hendhys'));
            $this->post(route('production-materials.store'), $this->data($warehouse, $product))->assertSessionHasErrors('branch_id');
            $this->get(route('production-materials.show', 1))->assertForbidden();
            $this->post(route('production-materials.cancel', 1), ['reason' => 'Tidak boleh membatalkan unit lain', 'confirmed' => true])->assertForbidden();
        }
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 43]);
    }
}
