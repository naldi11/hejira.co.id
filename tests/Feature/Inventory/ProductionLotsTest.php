<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, HendhysProduction, HendhysStockBranch, HendhysStockPusat, JihansProductionSession, JihansGudangStock, Product, ProductCategory, Unit, User};
use App\Services\{ProductionLots, StockService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionLotsTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $unit = Unit::create(['name' => 'Pcs', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::create(['name' => 'Produksi', 'entity_scope' => 'all']);
        return Product::create(['code' => fake()->unique()->numerify('P-#####'), 'name' => 'Brownies', 'unit_id' => $unit->id,
            'category_id' => $category->id, 'product_type' => 'INV', 'source_type' => 'produced', 'entity_scope' => 'all', 'status' => 'active']);
    }

    private function produce(Product $p, string $number, int $qty, string $entity = 'hendhys', string $date = '2026-10-12')
    {
        $attrs = ['type' => 'aktual', 'date' => $date, 'created_by' => User::factory()->create()->id];
        $stock = app(StockService::class);
        if ($entity === 'hendhys') {
            $production = HendhysProduction::create($attrs + ['production_number' => $number]);
            $production->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => $qty]);
            $stock->creditHendhys($p->id, $p->unit_id, $qty, null, 'production', $production->id, $production->created_by);
        } else {
            $production = JihansProductionSession::create($attrs + ['session_number' => $number]);
            $production->details()->create(['product_id' => $p->id, 'quantity' => $qty]);
            $stock->creditJihansGudang($p->id, $p->unit_id, $qty, 'production', $production->id, $production->created_by);
        }
        return $production;
    }

    public function test_fifo_splits_a_shipment_across_production_numbers_without_mixing_remaining_stock(): void
    {
        $p = $this->product();
        $first = $this->produce($p, 'HND-A', 10); $second = $this->produce($p, 'HND-B', 20);
        app(StockService::class)->debitHendhys($p->id, 15, null, 'transfer_to_branch', 90, null, null, 99);
        $this->assertDatabaseHas('production_lots', ['production_id' => $first->id, 'remaining' => 0]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $second->id, 'remaining' => 15]);
        $this->assertEquals([10, 5], DB::table('production_lot_outflows')->orderBy('id')->pluck('quantity')->all());
        $summary = app(ProductionLots::class)->summary($second->load('details.product.unit'), 'hendhys');
        $this->assertSame(5, $summary[0]['distributed']); $this->assertSame(15, $summary[0]['remaining']);
        $this->assertSame(15, (int) HendhysStockPusat::where('product_id', $p->id)->value('quantity'));
    }

    public function test_operator_can_choose_a_specific_newer_production(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'A', 10); $b = $this->produce($p, 'B', 20);
        $lot = DB::table('production_lots')->where('production_id', $b->id)->value('id');
        app(StockService::class)->debitHendhys($p->id, 7, null, 'transfer_to_branch', 90, null, $lot, 99);
        $this->assertDatabaseHas('production_lots', ['production_id' => $a->id, 'remaining' => 10]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $b->id, 'remaining' => 13]);
        $this->assertDatabaseHas('production_lot_outflows', ['lot_id' => $lot, 'reference_id' => 90, 'detail_id' => 99, 'quantity' => 7]);
    }

    public function test_invalid_or_insufficient_selected_lots_leave_both_ledgers_unchanged(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'A', 10); $this->produce($p, 'B', 20);
        $other = $this->product(); $this->produce($other, 'OTHER', 30);
        $this->produce($p, 'JHS', 30, 'jihans');
        $ownLot = DB::table('production_lots')->where('entity', 'hendhys')->where('production_id', $a->id)->value('id');
        $otherLot = DB::table('production_lots')->where('product_id', $other->id)->value('id');
        $wrongEntityLot = DB::table('production_lots')->where('entity', 'jihans')->value('id');
        foreach ([$ownLot, $otherLot, $wrongEntityLot, 999999] as $lot) {
            try { app(StockService::class)->debitHendhys($p->id, 15, null, 'transfer_to_branch', 90, null, $lot, 99); $this->fail('Invalid selection accepted'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('items', $e->errors()); }
        }
        $this->assertSame(30, (int) HendhysStockPusat::where('product_id', $p->id)->value('quantity'));
        $this->assertDatabaseCount('production_lot_outflows', 0);
        $this->assertDatabaseHas('production_lots', ['id' => $ownLot, 'remaining' => 10]);
    }

    public function test_legacy_opening_stock_and_unidentified_returns_do_not_inflate_known_production(): void
    {
        $p = $this->product(); HendhysStockPusat::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 8]);
        $production = $this->produce($p, 'NEW', 20);
        app(StockService::class)->debitHendhys($p->id, 10, null, 'transfer_to_branch', 90, null, null, 99);
        app(StockService::class)->creditHendhys($p->id, $p->unit_id, 3, null, 'return_from_branch', 91, null);
        $this->assertDatabaseHas('production_lots', ['production_id' => $production->id, 'remaining' => 18]);
        $this->assertSame(3, (int) DB::table('production_lots')->whereNull('production_id')->sum('remaining'));
        $this->assertSame(21, (int) HendhysStockPusat::where('product_id', $p->id)->value('quantity'));
    }

    public function test_pos_void_restores_original_lots_and_other_outflows_are_separate_from_shipments(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'A', 10); $b = $this->produce($p, 'B', 20);
        $stock = app(StockService::class);
        $stock->debitHendhys($p->id, 15, null, 'pos_sale', 90, null);
        $stock->creditHendhys($p->id, $p->unit_id, 15, null, 'pos_void', 90, null);
        $stock->debitHendhys($p->id, 2, null, 'return_gudang', 91, null);
        $this->assertDatabaseHas('production_lots', ['production_id' => $a->id, 'remaining' => 8]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $b->id, 'remaining' => 20]);
        $summary = app(ProductionLots::class)->summary($a->load('details.product.unit'), 'hendhys');
        $this->assertSame(0, $summary[0]['distributed']); $this->assertSame(2, $summary[0]['other_out']);
    }

    public function test_branch_stock_does_not_consume_central_production_and_jihans_adjustments_do(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'A', 10); $this->produce($p, 'J', 20, 'jihans');
        $branch = Branch::create(['code' => 'OUTLET', 'name' => 'Outlet', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        HendhysStockBranch::create(['branch_id' => $branch->id, 'product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 5]);
        app(StockService::class)->debitHendhys($p->id, 3, $branch->id, 'pos_sale', 90, null);
        $this->assertDatabaseHas('production_lots', ['entity' => 'hendhys', 'production_id' => $a->id, 'remaining' => 10]);
        app(StockService::class)->adjustJihansGudang($p->id, $p->unit_id, 7);
        $this->assertDatabaseHas('production_lots', ['entity' => 'jihans', 'remaining' => 7]);
        $this->assertSame(7, (int) JihansGudangStock::where('product_id', $p->id)->value('quantity'));
    }

    public function test_deadline_is_per_production_date_in_wib_and_predictions_have_no_lot(): void
    {
        $p = $this->product(); $old = $this->produce($p, 'OLD', 10, 'hendhys', '2026-10-11');
        $today = $this->produce($p, 'TODAY', 20);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 08:00', 'Asia/Jakarta'));
        $this->assertTrue(app(ProductionLots::class)->summary($old->load('details.product.unit'), 'hendhys')[0]['overdue']);
        $this->assertFalse(app(ProductionLots::class)->summary($today->load('details.product.unit'), 'hendhys')[0]['overdue']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 17:00', 'Asia/Jakarta'));
        $this->assertTrue(app(ProductionLots::class)->summary($today, 'hendhys')[0]['overdue']);
        $this->travelBack();
        $prediction = HendhysProduction::create(['production_number' => 'PRED', 'type' => 'prediksi', 'date' => '2026-10-12', 'created_by' => User::factory()->create()->id]);
        try { app(StockService::class)->creditHendhys($p->id, $p->unit_id, 1, null, 'production', $prediction->id, null); $this->fail('Prediction credited'); }
        catch (ValidationException $e) { $this->assertDatabaseMissing('production_lots', ['entity' => 'hendhys', 'production_id' => $prediction->id]); }
    }
    public function test_known_shipment_shortfall_restores_its_lot_but_mixed_shortfall_is_not_guessed(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'A', 10); $stock = app(StockService::class);
        $stock->debitHendhys($p->id, 10, null, 'transfer_to_branch', 90, null, null, 99);
        $stock->creditHendhys($p->id, $p->unit_id, 2, null, 'transfer_shortfall', 90, null);
        $this->assertDatabaseHas('production_lots', ['production_id' => $a->id, 'remaining' => 2]);
        $this->assertDatabaseHas('production_lot_outflows', ['reference_id' => 90, 'restored_quantity' => 2]);
        $b = $this->produce($p, 'B', 20);
        $stock->debitHendhys($p->id, 5, null, 'transfer_to_branch', 91, null, null, 100);
        $stock->creditHendhys($p->id, $p->unit_id, 1, null, 'transfer_shortfall', 91, null);
        $this->assertDatabaseHas('production_lots', ['production_id' => $a->id, 'remaining' => 0]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $b->id, 'remaining' => 17]);
        $this->assertSame(1, (int) DB::table('production_lots')->whereNull('production_id')->sum('remaining'));
    }

    public function test_new_morning_production_does_not_inherit_completed_yesterday_alert(): void
    {
        $p = $this->product(); $this->produce($p, 'YESTERDAY', 10, 'hendhys', '2026-10-11');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 17:00', 'Asia/Jakarta'));
        $monitor = app(\App\Services\ProductionStockMonitor::class);
        $this->assertSame(1, $monitor->overview()['overdue_count']);
        app(StockService::class)->debitHendhys($p->id, 10, null, 'transfer_to_branch', 90, null);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 08:00', 'Asia/Jakarta'));
        $this->produce($p, 'TODAY', 20);
        $this->assertNull($monitor->overview()['alert']);
        $this->assertSame([], $monitor->overview()['overdue_lots']);
        $this->travelBack();
    }

    public function test_dispatch_forms_save_selected_lots_for_both_units_and_rollback_stale_selection(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create(['entity' => 'all']); $admin->assignRole('super_admin');
        $branch = Branch::create(['code' => 'OUTLET', 'name' => 'Outlet', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $p = $this->product(); $this->produce($p, 'A', 10); $b = $this->produce($p, 'B', 20);
        $lot = DB::table('production_lots')->where('entity', 'hendhys')->where('production_id', $b->id)->value('id');
        $data = ['branch_id' => $branch->id, 'date' => '2026-10-12', 'items' => [['product_id' => $p->id, 'quantity' => 7, 'unit_id' => $p->unit_id, 'production_lot_id' => $lot]]];
        $this->actingAs($admin)->get(route('hendhys.transfer-to-branch.create'))->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->has('productionLots', 2));
        $this->post(route('hendhys.transfer-to-branch.store'), $data)->assertRedirect()->assertSessionHas('success');
        $transfer = \App\Models\HendhysTransferToBranch::first(); $detail = $transfer->details->first();
        $this->assertDatabaseHas('production_lot_outflows', ['lot_id' => $lot, 'reference_id' => $transfer->id, 'detail_id' => $detail->id, 'quantity' => 7]);
        $data['items'][0]['quantity'] = 14;
        $this->post(route('hendhys.transfer-to-branch.store'), $data)->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('hendhys_transfer_to_branch', 1);
        $this->assertDatabaseHas('production_lots', ['id' => $lot, 'remaining' => 13]);
        $j = $this->produce($p, 'J', 20, 'jihans');
        $jLot = DB::table('production_lots')->where('entity', 'jihans')->where('production_id', $j->id)->value('id');
        $this->post(route('gudang.transfer-out.store'), ['to_entity' => 'hendhys', 'branch_id' => $branch->id, 'date' => '2026-10-12',
            'items' => [['product_id' => $p->id, 'quantity' => 4, 'unit_id' => $p->unit_id, 'hpp_price' => 1000, 'production_lot_id' => $jLot]]])
            ->assertRedirect()->assertSessionHas('success');
        $gudangTransfer = \App\Models\TransferOut::first();
        $this->assertDatabaseHas('production_lot_outflows', ['lot_id' => $jLot, 'source' => 'transfer_out', 'reference_id' => $gudangTransfer->id,
            'detail_id' => $gudangTransfer->details->first()->id, 'quantity' => 4]);
        $this->assertDatabaseHas('production_lots', ['id' => $jLot, 'remaining' => 16]);
    }

}
