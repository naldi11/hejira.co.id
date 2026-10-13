<?php

namespace Tests\Feature\Owner;

use App\Models\{Branch, HendhysProduction, HendhysStockPusat, HendhysStockBranch, JihansProductionSession, JihansGudangStock, JihansRetailStock, Product, ProductCategory, PurchaseOrder, Supplier, Unit, User};
use App\Services\ProductionStockMonitor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'owner', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $role === 'owner' ? 'all' : (str_contains($role, 'jihans') ? 'jihans' : 'hendhys'), 'branch_id' => $branch?->id]);
        $user->assignRole($role);
        return $user;
    }

    private function product(string $entity = 'hendhys', string $source = 'produced'): Product
    {
        $category = ProductCategory::create(['name' => 'Produksi', 'entity_scope' => 'all']);
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        return Product::create(['code' => 'P-'.fake()->unique()->numberBetween(1, 99999), 'name' => 'Brownies '.$entity,
            'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'INV',
            'entity_scope' => $entity, 'source_type' => $source, 'status' => 'active', 'stock_min' => 0, 'hpp' => 1000]);
    }

    private function production(Product $product, string $type = 'aktual', int $quantity = 20)
    {
        $user = $this->user();
        $attributes = ['type' => $type, 'date' => '2026-08-03', 'created_by' => $user->id, 'notes' => 'Catatan produksi'];
        if ($product->entity_scope === 'jihans') {
            $production = JihansProductionSession::create($attributes + ['session_number' => 'JHS-'.fake()->unique()->numberBetween(1, 99999)]);
            $production->details()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        } else {
            $production = HendhysProduction::create($attributes + ['production_number' => 'HND-'.fake()->unique()->numberBetween(1, 99999)]);
            $production->details()->create(['product_id' => $product->id, 'quantity_produced' => $quantity, 'unit_id' => $product->unit_id]);
        }
        return $production;
    }

    public function test_owner_can_open_po_with_items_and_receipt_history(): void
    {
        $owner = $this->user(); $product = $this->product();
        $supplier = Supplier::create(['code' => 'S-1', 'name' => 'Supplier Demo', 'is_active' => true]);
        $po = PurchaseOrder::create(['po_number' => 'PO-1', 'supplier_id' => $supplier->id, 'date' => today(), 'status' => 'partial', 'total_amount' => 50000, 'created_by' => $owner->id]);
        $po->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_ordered' => 10, 'quantity_received' => 4, 'price' => 5000, 'total' => 50000]);
        $po->receivings()->create(['grn_number' => 'GRN-1', 'supplier_id' => $supplier->id, 'date' => today(), 'created_by' => $owner->id]);
        $this->actingAs($owner)->get(route('owner.dashboard.detail', ['mode' => 'stock', 'unit' => 'po']))
            ->assertInertia(fn (Assert $page) => $page->where('list.0.id', $po->id));
        $this->get(route('owner.po.show', $po))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Owner/PurchaseOrder')->where('po.details.0.quantity_ordered', 10)
            ->where('po.details.0.quantity_received', 4)->where('po.details.0.product', $product->name)
            ->where('po.receivings.0.grn_number', 'GRN-1'));
    }

    public function test_owner_can_view_both_units_and_filter_predictions(): void
    {
        $h = $this->production($this->product(), 'prediksi');
        $j = $this->production($this->product('jihans'));
        $this->actingAs($this->user())->get(route('owner.production.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Owner/Production/Index')->has('records.data', 2));
        $this->get(route('owner.production.index', ['entity' => 'hendhys', 'type' => 'prediksi']))
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 1)->where('records.data.0.id', $h->id));
        foreach ([['hendhys', $h], ['jihans', $j]] as [$entity, $record]) {
            $this->get(route('owner.production.show', ['entity' => $entity, 'id' => $record->id]))
                ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Owner/Production/Show')->where('production.items.0.quantity', 20));
        }
        $this->assertSame([], app(ProductionStockMonitor::class)->overview()['stocks']);
    }

    public function test_deadline_uses_wib_and_deduplicates_alerts_excluding_returns_branches_and_purchased_goods(): void
    {
        $product = $this->product();
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 7, 'quantity_return' => 100, 'unit_id' => $product->unit_id]);
        $purchased = $this->product('hendhys', 'purchased');
        HendhysStockPusat::create(['product_id' => $purchased->id, 'quantity' => 500, 'unit_id' => $purchased->unit_id]);
        $branchProduct = $this->product();
        $branch = Branch::create(['code' => 'OUTLET', 'name' => 'Outlet', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        HendhysStockBranch::create(['product_id' => $branchProduct->id, 'branch_id' => $branch->id, 'quantity' => 30, 'unit_id' => $branchProduct->unit_id]);
        Carbon::setTestNow(Carbon::parse('2026-08-03 09:59:59', 'UTC')); // 16:59:59 WIB
        $monitor = app(ProductionStockMonitor::class);
        $this->assertNull($monitor->overview()['alert']);
        Carbon::setTestNow(Carbon::parse('2026-08-03 10:00:00', 'UTC')); // 17:00 WIB
        $state = $monitor->overview();
        $this->assertSame(1, $state['overdue_count']);
        $this->assertSame(7, $state['stocks'][0]['quantity']);
        $this->assertTrue($state['stocks'][0]['overdue']);
        $monitor->overview();
        $this->assertDatabaseCount('production_stock_alerts', 1);
        $this->artisan('production:check-stock')->assertSuccessful();
    }

    public function test_hidden_or_deleted_produced_products_still_alert_if_physically_at_hendhys_pusat(): void
    {
        $product = $this->product('jihans');
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 5, 'unit_id' => $product->unit_id]);
        $product->delete();
        Carbon::setTestNow('2026-08-03 17:00:00');
        $state = app(ProductionStockMonitor::class)->overview();
        $this->assertSame(1, $state['overdue_count']);
        $this->assertSame($product->name, $state['stocks'][0]['product']);
    }

    public function test_overdue_remains_next_morning_until_stock_is_distributed(): void
    {
        $product = $this->product();
        $stock = HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 6, 'unit_id' => $product->unit_id]);
        Carbon::setTestNow('2026-08-03 17:00:00');
        $monitor = app(ProductionStockMonitor::class); $monitor->overview();
        Carbon::setTestNow('2026-08-04 08:00:00');
        $this->assertSame(1, $monitor->overview()['overdue_count']);
        $stock->update(['quantity' => 0, 'quantity_return' => 9]);
        $this->assertNull($monitor->overview()['alert']);
        $this->assertDatabaseMissing('production_stock_alerts', ['resolved_at' => null]);
    }

    public function test_owner_receives_alert_and_operational_routes_remain_forbidden(): void
    {
        $product = $this->product();
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 3, 'unit_id' => $product->unit_id]);
        Carbon::setTestNow('2026-08-03 17:00:00');
        $this->actingAs($this->user())->get(route('owner.dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('production_monitor.overdue_count', 1)
                ->where('notifications.items.0.id', 'production_stock_2026-08-03')
                ->where('notifications.items.0.path', route('owner.production.index')));
        $this->get(route('gudang.po.index'))->assertForbidden();
    }

    public function test_jihans_warehouse_and_outlet_stocks_are_separate_from_hendhys(): void
    {
        $j = $this->product('jihans'); $h = $this->product();
        JihansGudangStock::create(['product_id' => $j->id, 'quantity' => 12, 'unit_id' => $j->unit_id]);
        JihansRetailStock::create(['product_id' => $j->id, 'quantity' => 4, 'unit_id' => $j->unit_id]);
        JihansGudangStock::create(['product_id' => $h->id, 'quantity' => 30, 'unit_id' => $h->unit_id]);
        HendhysStockPusat::create(['product_id' => $h->id, 'quantity' => 8, 'unit_id' => $h->unit_id]);
        $rows = app(ProductionStockMonitor::class)->stocks('jihans');
        $this->assertCount(1, $rows);
        $this->assertSame(12, $rows[0]['quantity']); $this->assertSame(4, $rows[0]['retail_quantity']);
    }

    public function test_hendhys_actualization_preserves_prediction_for_owner(): void
    {
        $product = $this->product(); $prediction = $this->production($product, 'prediksi', 30);
        $prediction->update(['date' => '2026-08-04']);
        $branch = Branch::create(['code' => 'PUSAT', 'name' => 'Hendhys Produksi', 'entity' => 'hendhys', 'type' => 'pusat', 'is_active' => true]);
        $this->actingAs($this->user('admin_hendhys', $branch))->post(route('hendhys.productions.store'), [
            'date' => '2026-08-03', 'prediction_id' => $prediction->id,
            'items' => [['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_produced' => 22]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $prediction->refresh();
        $this->assertSame(30, $prediction->prediction_snapshot['items'][0]['quantity']);
        $this->assertSame('aktual', $prediction->type);
        $this->assertSame('2026-08-03', $prediction->date->toDateString());
        $this->assertSame('2026-08-04', $prediction->prediction_snapshot['date']);
        $this->assertSame(auth()->id(), $prediction->created_by);
        $this->assertSame(22, HendhysStockPusat::first()->quantity);
        $this->actingAs($this->user())->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $prediction->id]))
            ->assertInertia(fn (Assert $page) => $page->where('production.prediction.items.0.quantity', 30)->where('production.items.0.quantity', 22));
    }

    public function test_jihans_actualization_preserves_prediction_and_credits_warehouse(): void
    {
        $product = $this->product('jihans'); $prediction = $this->production($product, 'prediksi', 40);
        $this->actingAs($this->user('admin_jihans'))->post(route('jihans.production.store'), [
            'date' => '2026-08-03', 'prediction_id' => $prediction->id,
            'details' => [['product_id' => $product->id, 'quantity' => 25]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $prediction->refresh();
        $this->assertSame(40, $prediction->prediction_snapshot['items'][0]['quantity']);
        $this->assertSame(25, JihansGudangStock::first()->quantity);
        $this->assertSame(0, JihansRetailStock::count());
    }

    public function test_partial_refresh_crosses_deadline_and_distribution_clears_alert(): void
    {
        $product = $this->product();
        HendhysStockPusat::create(['product_id' => $product->id, 'quantity' => 10, 'unit_id' => $product->unit_id]);
        $owner = $this->user();
        Carbon::setTestNow('2026-08-03 16:59:00');
        $this->actingAs($owner)->get(route('owner.production.index'))->assertInertia(fn (Assert $page) => $page->where('production_monitor.alert', null));
        Carbon::setTestNow('2026-08-03 17:00:00');
        $headers = ['X-Inertia' => 'true', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Inertia-Partial-Component' => 'Owner/Production/Index', 'X-Inertia-Partial-Data' => 'notifications,production_monitor'];
        $this->withHeaders($headers)->get(route('owner.production.index'))->assertOk()
            ->assertJsonPath('props.production_monitor.overdue_count', 1)
            ->assertJsonPath('props.notifications.items.0.type', 'danger');
        app(\App\Services\StockService::class)->debitHendhys($product->id, 10, null, 'transfer_to_branch', null, $owner->id);
        $this->withHeaders($headers)->get(route('owner.production.index'))->assertOk()
            ->assertJsonPath('props.production_monitor.alert', null)
            ->assertJsonPath('props.production_monitor.stocks.0.quantity', 0);
    }

    public function test_non_owner_cannot_access_new_pages_and_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->user('admin_hendhys'))->get(route('owner.production.index'))->assertForbidden();
        $this->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => 1]))->assertForbidden();
        $supplier = Supplier::create(['code' => 'S-ACCESS', 'name' => 'Supplier', 'is_active' => true]);
        $po = PurchaseOrder::create(['po_number' => 'PO-ACCESS', 'supplier_id' => $supplier->id, 'date' => today(), 'status' => 'draft', 'created_by' => auth()->id()]);
        $this->get(route('owner.po.show', $po))->assertForbidden();
        $this->actingAs($this->user())->get(route('owner.production.index', ['entity' => 'izzi']))->assertSessionHasErrors('entity');
        $this->get('/owner/production/other/1')->assertNotFound();
    }
}
