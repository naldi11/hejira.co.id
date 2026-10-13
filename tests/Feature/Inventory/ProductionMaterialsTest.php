<?php

namespace Tests\Feature\Inventory;

use App\Models\{HendhysProduction, Product, ProductCategory, Unit, User};
use App\Services\{ProductionMaterials, StockService};
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
        parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-10-13 01:30', 'Asia/Jakarta'));
    }

    private function user(string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web'); $u = User::factory()->create(['entity' => 'gudang']); $u->assignRole($role); return $u;
    }

    private function product(string $source = 'purchased'): Product
    {
        $u = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $c = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        return Product::create(['name' => 'Bahan Uji '.Str::random(4), 'code' => Str::random(8), 'category_id' => $c->id,
            'unit_id' => $u->id, 'source_type' => $source, 'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active']);
    }

    private function stock(string $location, Product $p, int $quantity): void
    {
        DB::table($location === 'gudang' ? 'jihans_gudang_stock' : 'hendhys_stock_pusat')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => $quantity, 'last_updated' => now(), ...($location === 'gudang' ? [] : ['quantity_return' => 3])]);
    }

    private function production(User $actor, string $type = 'prediksi'): HendhysProduction
    {
        return HendhysProduction::create(['production_number' => 'HND-PRD-'.Str::random(8), 'date' => '2026-10-13', 'type' => $type, 'created_by' => $actor->id]);
    }

    private function data(Product $p, string $kind = 'warehouse_transfer', ?HendhysProduction $production = null): array
    {
        return ['request_key' => (string) Str::uuid(), 'kind' => $kind, 'date' => '2026-10-13', 'time' => '01:15', 'production_id' => $production?->id,
            'sender_name' => 'Budi Penyerah', 'receiver_name' => 'Siti Tim Produksi', 'reason' => 'Bahan untuk brownies pagi', 'notes' => 'Barang dalam kondisi baik',
            'evidence_reference' => 'BUKTI-PAGI-12', 'items' => [['product_id' => $p->id, 'quantity' => 7, 'notes' => 'Kemasan tertutup']], 'confirmed' => true];
    }

    public function test_all_endpoints_and_service_are_restricted_to_superadmin(): void
    {
        $p = $this->product(); $this->stock('gudang', $p, 20);
        foreach (['owner', 'admin_hendhys', 'kasir_hendhys', 'admin_jihans', 'kasir_jihans'] as $role) {
            $actor = $this->user($role); $this->actingAs($actor);
            foreach (['production-materials.index', 'production-materials.create'] as $route) $this->get(route($route))->assertForbidden();
            $this->get(route('production-materials.show', 1))->assertForbidden();
            $this->post(route('production-materials.store'), $this->data($p))->assertForbidden();
            $this->post(route('production-materials.cancel', 1), ['reason' => 'Salah input', 'confirmed' => true])->assertForbidden();
            try { app(ProductionMaterials::class)->record($this->data($p), $actor); $this->fail('Non-superadmin cannot call service directly.'); }
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        }
        $this->assertDatabaseCount('production_material_transactions', 0);
    }

    public function test_warehouse_transfer_is_atomic_preserves_other_balances_and_uses_master_unit(): void
    {
        $actor = $this->user(); $p = $this->product(); $pack = Unit::create(['name' => 'Kemasan', 'abbreviation' => 'PACK', 'entity_scope' => 'all']); $p->update(['unit_id' => $pack->id]);
        $this->stock('gudang', $p, 20); $this->stock('pusat', $p, 5);
        DB::table('jihans_retail_stock')->insert(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 40, 'last_updated' => now()]);
        $data = $this->data($p); $data['items'][0]['unit_id'] = 999; $data['from_branch_id'] = 99;
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('production_material_transactions', ['number' => 'BPR-20261013-0001', 'status' => 'completed', 'sender_name' => 'Budi Penyerah', 'receiver_name' => 'Siti Tim Produksi', 'created_by_name' => $actor->name]);
        $this->assertDatabaseHas('production_material_items', ['quantity' => 7, 'unit_name' => 'PACK', 'source_before' => 20, 'source_after' => 13, 'destination_before' => 5, 'destination_after' => 12]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 13]); $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 12, 'quantity_return' => 3]);
        $this->assertDatabaseHas('jihans_retail_stock', ['quantity' => 40]); $this->assertDatabaseCount('hendhys_stock_branch', 0);
        $this->assertDatabaseCount('production_material_events', 1); $this->assertDatabaseCount('production_lots', 0);
        foreach (['jihans_gudang_stock_movements', 'hendhys_stock_movements'] as $table) $this->assertDatabaseHas($table, ['production_material_id' => 1, 'reference_id' => null]);
        $this->get(route('production-materials.show', 1))->assertInertia(fn (Assert $page) => $page->where('transaction.occurred_label', '13/10/2026 01:15:00 WIB')->has('events.0.stock_changes', 2));
    }

    public function test_usage_requires_production_and_changes_only_pusat_stock(): void
    {
        $actor = $this->user(); $p = $this->product(); $this->stock('gudang', $p, 50); $this->stock('pusat', $p, 20); $production = $this->production($actor);
        $this->actingAs($actor)->post(route('production-materials.store'), $this->data($p, 'production_usage'))->assertSessionHasErrors('production_id');
        $this->post(route('production-materials.store'), $this->data($p, 'production_usage', $production))->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 13, 'quantity_return' => 3]); $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 50]);
        $this->assertDatabaseHas('production_material_transactions', ['production_id' => $production->id, 'production_number' => $production->production_number, 'production_type' => 'prediksi']);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 0); $this->assertDatabaseCount('hendhys_productions', 1); $this->assertDatabaseCount('hendhys_production_details', 0);
        if ($preview = getenv('HEJIRA_MATERIAL_PREVIEW')) {
            \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8099');
            foreach (['create' => route('production-materials.create'), 'history' => route('production-materials.index'), 'show' => route('production-materials.show', 1)] as $name => $url) {
                file_put_contents($preview.'/'.$name.'.html', $this->get($url)->assertOk()->getContent());
            }
        }
    }

    public function test_insufficient_stock_and_wrong_units_rollback_all_items_and_sequences(): void
    {
        $a = $this->product(); $b = $this->product(); $this->stock('gudang', $a, 20); $this->stock('gudang', $b, 2);
        $actor = $this->user(); $this->actingAs($actor); $data = $this->data($a); $data['items'][] = ['product_id' => $b->id, 'quantity' => 3];
        $this->post(route('production-materials.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $a->id, 'quantity' => 20]); $this->assertDatabaseCount('hendhys_stock_pusat', 0);
        $this->assertDatabaseCount('production_material_transactions', 0); $this->assertDatabaseCount('production_material_sequences', 0); $this->assertDatabaseCount('production_material_events', 0);
        $wrong = Unit::create(['name' => 'Kotak', 'abbreviation' => 'BOX', 'entity_scope' => 'all']);
        $this->stock('pusat', $a, 4); DB::table('hendhys_stock_pusat')->update(['unit_id' => $wrong->id]);
        $this->post(route('production-materials.store'), $this->data($a))->assertSessionHasErrors('items');
        DB::table('jihans_gudang_stock')->where('product_id', $a->id)->update(['unit_id' => $wrong->id]);
        $this->get(route('production-materials.create'))->assertInertia(fn (Assert $page) => $page->where('stocks.warehouse_transfer', fn ($rows) => collect($rows)->contains(fn ($row) => (int) $row['id'] === $a->id && !$row['unit_matches'] && $row['stock_unit'] === 'BOX' && $row['unit'] === 'PCS')));
        $this->post(route('production-materials.store'), $this->data($a))->assertSessionHasErrors('items');
        foreach (['jihans_gudang_stock_movements', 'hendhys_stock_movements', 'production_material_items', 'production_lots'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_daily_numbers_wib_override_and_duplicate_requests_do_not_move_stock_twice(): void
    {
        $p = $this->product(); $this->stock('gudang', $p, 50); $this->actingAs($this->user()); $data = $this->data($p);
        $this->get(route('production-materials.create'))->assertInertia(fn (Assert $page) => $page->where('today', '2026-10-13')->where('currentTime', '01:30'));
        $this->post(route('production-materials.store'), $data)->assertSessionHas('success'); $this->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 43]); $this->assertDatabaseCount('production_material_transactions', 1);
        $bad = $data; $bad['items'][0]['quantity'] = 8; $this->post(route('production-materials.store'), $bad)->assertSessionHasErrors('items');
        $this->post(route('production-materials.store'), $this->data($p))->assertSessionHas('success');
        $yesterday = $this->data($p); $yesterday['date'] = '2026-10-12'; $this->post(route('production-materials.store'), $yesterday)->assertSessionHas('success');
        foreach (['BPR-20261013-0001', 'BPR-20261013-0002', 'BPR-20261012-0001'] as $number) $this->assertDatabaseHas('production_material_transactions', ['number' => $number]);
        $future = $this->data($p); $future['time'] = '01:31'; $this->post(route('production-materials.store'), $future)->assertSessionHasErrors('time');
        $fraction = $this->data($p); $fraction['items'][0]['quantity'] = 1.5; $this->post(route('production-materials.store'), $fraction)->assertSessionHasErrors('items.0.quantity');
        $duplicate = $this->data($p); $duplicate['items'][] = $duplicate['items'][0]; $this->post(route('production-materials.store'), $duplicate)->assertSessionHasErrors('items.0.product_id');
    }

    public function test_usage_cancellation_restores_the_exact_original_production_lots_once(): void
    {
        $actor = $this->user(); $p = $this->product('produced'); $source = $this->production($actor, 'aktual'); $target = $this->production($actor);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 20, null, 'production', $source->id, $actor->id);
        $lot = (int) DB::table('production_lots')->value('id'); $data = $this->data($p, 'production_usage', $target); $data['items'][0]['lot_id'] = $lot;
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['id' => $lot, 'production_id' => $source->id, 'remaining' => 13]);
        $cancel = ['reason' => 'Salah jumlah, barang belum digunakan', 'confirmed' => true];
        $this->post(route('production-materials.cancel', 1), $cancel)->assertSessionHas('success'); $this->post(route('production-materials.cancel', 1), $cancel)->assertSessionHas('success');
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 20]); $this->assertDatabaseHas('production_lots', ['id' => $lot, 'remaining' => 20]);
        $this->assertDatabaseCount('production_lots', 1); $this->assertDatabaseCount('production_material_events', 2);
        $this->assertDatabaseHas('production_lot_outflows', ['lot_id' => $lot, 'source' => 'production_material', 'quantity' => 7, 'restored_quantity' => 7]);
        // Repeating the original save after cancellation must not reinstate the mutation.
        $this->post(route('production-materials.store'), $data)->assertSessionHas('success'); $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 20]);
        $this->get(route('production-materials.show', 1))->assertInertia(fn (Assert $page) => $page->where('transaction.status', 'cancelled')->where('items.0.lots.0.number', $source->production_number)->where('events.1.stock_changes.0.before', 13)->where('events.1.stock_changes.0.after', 20));
    }

    public function test_transfer_cancellation_restores_gudang_and_never_debits_newer_production(): void
    {
        $actor = $this->user(); $p = $this->product('produced'); $this->stock('gudang', $p, 20); $this->actingAs($actor);
        $this->post(route('production-materials.store'), $this->data($p))->assertSessionHas('success');
        $sourceLot = (int) DB::table('production_lots')->where('entity', 'jihans')->value('id');
        $destLot = (int) DB::table('production_material_items')->value('destination_lot_id');
        $production = $this->production($actor, 'aktual'); app(StockService::class)->creditHendhys($p->id, $p->unit_id, 10, null, 'production', $production->id, $actor->id);
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Barang dikembalikan utuh ke gudang', 'confirmed' => true])->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 20]); $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 10]);
        $this->assertDatabaseHas('production_lots', ['id' => $sourceLot, 'remaining' => 20]); $this->assertDatabaseHas('production_lots', ['id' => $destLot, 'remaining' => 0]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $production->id, 'entity' => 'hendhys', 'remaining' => 10]);
    }

    public function test_transfer_cancellation_is_blocked_if_original_goods_are_used_even_when_new_stock_exists(): void
    {
        $actor = $this->user(); $p = $this->product('produced'); $this->stock('gudang', $p, 20); $this->actingAs($actor);
        $this->post(route('production-materials.store'), $this->data($p))->assertSessionHas('success');
        $target = $this->production($actor); $use = $this->data($p, 'production_usage', $target); $use['items'][0]['quantity'] = 5;
        $this->post(route('production-materials.store'), $use)->assertSessionHas('success');
        $new = $this->production($actor, 'aktual'); app(StockService::class)->creditHendhys($p->id, $p->unit_id, 20, null, 'production', $new->id, $actor->id);
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Salah mencatat pengiriman', 'confirmed' => true])->assertSessionHasErrors('items');
        $this->assertDatabaseHas('jihans_gudang_stock', ['quantity' => 13]); $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 22]);
        $this->assertDatabaseHas('production_material_transactions', ['id' => 1, 'status' => 'completed']); $this->assertDatabaseCount('production_material_events', 2);
    }

    public function test_history_searches_immutable_product_and_actor_snapshots_and_cancellation_reasons(): void
    {
        $actor = $this->user(); $p = $this->product(); $this->stock('gudang', $p, 20); $production = $this->production($actor);
        $data = $this->data($p, 'warehouse_transfer', $production); $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        $oldName = $p->name; $oldCode = $p->code; $oldActor = $actor->name; $oldProduction = $production->production_number;
        $p->update(['name' => 'Nama baru', 'code' => 'BARU']); $p->delete(); $actor->update(['name' => 'Akun baru']); $production->update(['production_number' => 'PRODUKSI-BARU']);
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Kesalahan jumlah sore', 'confirmed' => true])->assertSessionHas('success');
        foreach ([$oldName, $oldCode, $oldActor, $oldProduction, 'Budi Penyerah', 'Siti Tim Produksi', 'BUKTI-PAGI-12', 'Kemasan tertutup', 'Kesalahan jumlah sore'] as $search) {
            $this->get(route('production-materials.index', ['search' => $search]))->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1)->where('transactions.data.0.items.0.product_name', $oldName));
        }
        $this->get(route('production-materials.index', ['status' => 'completed']))->assertInertia(fn (Assert $page) => $page->has('transactions.data', 0));
        $this->get(route('production-materials.index', ['date_from' => '2026-10-13', 'date_to' => '2026-10-13', 'kind' => 'warehouse_transfer']))->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1));
    }

    public function test_cancellation_of_multiple_items_rolls_back_if_one_destination_stock_is_insufficient(): void
    {
        $a = $this->product(); $b = $this->product(); $this->stock('gudang', $a, 20); $this->stock('gudang', $b, 20); $actor = $this->user();
        $data = $this->data($a); $data['items'][] = ['product_id' => $b->id, 'quantity' => 7];
        $this->actingAs($actor)->post(route('production-materials.store'), $data)->assertSessionHas('success');
        app(StockService::class)->debitHendhys($b->id, 6, null, 'adjustment', null, $actor->id);
        $beforeMovements = DB::table('hendhys_stock_movements')->count();
        $this->post(route('production-materials.cancel', 1), ['reason' => 'Salah pengiriman beberapa barang', 'confirmed' => true])->assertSessionHasErrors('items');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $a->id, 'quantity' => 13]); $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $b->id, 'quantity' => 13]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $a->id, 'quantity' => 7]); $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $b->id, 'quantity' => 1]);
        $this->assertDatabaseCount('production_material_events', 1); $this->assertDatabaseCount('hendhys_stock_movements', $beforeMovements);
        $this->assertDatabaseHas('production_material_transactions', ['id' => 1, 'status' => 'completed']);
    }

    public function test_fifo_does_not_consume_lots_with_units_different_from_the_stock_card(): void
    {
        $actor = $this->user(); $p = $this->product('produced'); $source = $this->production($actor, 'aktual'); $target = $this->production($actor);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 20, null, 'production', $source->id, $actor->id);
        $wrong = Unit::create(['name' => 'Kotak', 'abbreviation' => 'BOX', 'entity_scope' => 'all']); DB::table('production_lots')->update(['unit_id' => $wrong->id]);
        $this->actingAs($actor)->post(route('production-materials.store'), $this->data($p, 'production_usage', $target))->assertSessionHasErrors('items');
        $this->assertDatabaseHas('hendhys_stock_pusat', ['quantity' => 20]); $this->assertDatabaseHas('production_lots', ['remaining' => 20]);
        $this->assertDatabaseCount('production_material_transactions', 0); $this->assertDatabaseCount('production_lot_outflows', 0);
    }
}
