<?php

namespace Tests\Feature\Performance;

use App\Models\{Branch, CashierShift, JihansGudangStockMovement, Product, ProductCategory, TransferOut, Unit, User};
use App\Services\{ShiftReportSummaries, StockMovementDocuments};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);
        return $user;
    }

    private function products(int $number = 75): void
    {
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::create(['name' => 'Daging', 'entity_scope' => 'all']);
        $rows = [];
        for ($i = 1; $i <= $number; $i++) $rows[] = ['code' => sprintf('TEST-%03d', $i), 'barcode' => sprintf('BAR-%03d', $i), 'name' => sprintf('Produk %03d', $i),
            'unit_id' => $unit->id, 'category_id' => $category->id, 'status' => 'active', 'entity_scope' => 'all', 'visible_jihans' => true, 'visible_hendhys' => true,
            'product_type' => 'INV', 'source_type' => 'purchased', 'selling_price' => 1000];
        DB::table('master_products')->insert($rows);
    }

    public function test_user_pages_are_bounded_and_search_covers_later_pages(): void
    {
        $admin = $this->user('super_admin');
        User::factory()->count(109)->create();
        $last = User::factory()->create(['name' => 'ZZZ pengguna terakhir']);
        $this->actingAs($admin)->get(route('master.users.index'))->assertInertia(fn (Assert $p) => $p->has('users', 20)->where('pagination.total', 111));
        $this->get(route('master.users.index', ['search' => 'ZZZ pengguna']))->assertInertia(fn (Assert $p) => $p->has('users', 1)->where('users.0.id', $last->id));
        foreach ([50 => 50, 100 => 100, 'all' => 111] as $size => $count) $this->get(route('master.users.index', ['per_page' => $size]))->assertInertia(fn (Assert $p) => $p->has('users', $count));
    }

    public function test_dashboard_daily_sales_keep_the_current_wib_date_and_branch_scope(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-13 01:43:00', 'Asia/Jakarta'));
        foreach (['jihans', 'hendhys'] as $entity) {
            $branch = Branch::create(['code' => $entity.'-DAY', 'name' => $entity, 'entity' => $entity, 'type' => 'cabang', 'is_active' => true]);
            $actor = $this->user('kasir_'.$entity, $entity, $branch);
            foreach ([['today', today(), 'paid', 3000], ['yesterday', today()->subDay(), 'paid', 9000], ['pending', today(), 'pending', 4000]] as [$number, $date, $status, $total]) {
                $row = ['transaction_number' => $number, 'date' => $date->toDateString(), 'time' => '01:00:00', 'status' => $status, 'grand_total' => $total, 'created_by' => $actor->id];
                if ($entity === 'hendhys') $row['branch_id'] = $branch->id;
                DB::table($entity.'_transactions')->insert($row);
            }
            if ($entity === 'hendhys') DB::table('hendhys_transactions')->insert([
                'transaction_number' => 'another-location', 'date' => today()->toDateString(), 'time' => '01:00:00',
                'status' => 'paid', 'grand_total' => 100000, 'created_by' => $actor->id,
            ]);
            $this->actingAs($actor)->get(route($entity.'.dashboard'))->assertInertia(fn (Assert $p) => $p->where('stats.omset_hari_ini', 3000));
        }
    }

    public function test_incoming_shipments_are_paginated_for_both_units_without_loading_line_items(): void
    {
        $admin = $this->user('super_admin');
        foreach (['jihans', 'hendhys'] as $entity) {
            for ($i = 1; $i <= 45; $i++) TransferOut::create(['transfer_number' => "$entity-$i", 'to_entity' => $entity, 'date' => today(), 'status' => 'sent', 'created_by' => $admin->id]);
        }
        $this->actingAs($admin)->get(route('jihans.transfer-requests.index'))->assertInertia(fn (Assert $p) => $p->has('incomingTransfers', 20)->where('incomingPagination.total', 45));
        $this->get(route('jihans.transfer-requests.index', ['incoming_page' => 3]))->assertInertia(fn (Assert $p) => $p->has('incomingTransfers', 5));
        $this->get(route('hendhys.transfer-to-branch.index', ['tab' => 'gudang']))->assertInertia(fn (Assert $p) => $p->has('gudangTransfers', 20)->where('gudangPagination.total', 45)->missing('gudangTransfers.0.details'));
        $this->get(route('hendhys.transfer-to-branch.index', ['tab' => 'gudang', 'gudang_page' => 3]))->assertInertia(fn (Assert $p) => $p->has('gudangTransfers', 5));
    }

    public function test_catalogues_search_every_product_and_use_the_active_branch_stock(): void
    {
        $this->products();
        $branch = Branch::create(['code' => 'HND-TEST', 'name' => 'Hendhys Cabang', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $foreign = Branch::create(['code' => 'HND-OTHER', 'name' => 'Hendhys Lain', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        foreach (['jihans', 'hendhys'] as $entity) {
            $cashier = $this->user('kasir_'.$entity, $entity, $entity === 'hendhys' ? $branch : null);
            CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $cashier->branch_id, 'entity' => $entity, 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
            $this->actingAs($cashier)->get(route($entity.'.pos.index'))->assertInertia(fn (Assert $p) => $p->has('products', 20)->where('catalogue.has_more', true));
            $this->getJson(route($entity.'.pos.index', ['lookup' => 'products', 'q' => 'BAR-075']))->assertOk()->assertJsonCount(1, 'products')->assertJsonPath('products.0.code', 'TEST-075');
            $this->getJson(route($entity.'.pos.index', ['lookup' => 'products', 'catalog_page' => 4]))->assertJsonCount(15, 'products');
        }
        $last = Product::where('code', 'TEST-075')->first();
        DB::table('hendhys_stock_branch')->insert([
            ['branch_id' => $branch->id, 'product_id' => $last->id, 'unit_id' => $last->unit_id, 'quantity' => 8],
            ['branch_id' => $foreign->id, 'product_id' => $last->id, 'unit_id' => $last->unit_id, 'quantity' => 900],
        ]);
        $this->getJson(route('hendhys.pos.index', ['lookup' => 'products', 'q' => 'BAR-075']))->assertJsonPath('products.0.current_stock', 8);
        $this->actingAs($this->user('owner'))->getJson(route('hendhys.pos.index', ['lookup' => 'products']))->assertForbidden();
    }

    public function test_document_number_loading_is_batched_and_does_not_confuse_shortfall_with_return(): void
    {
        $this->products(1); $actor = $this->user('super_admin');
        $transfer = TransferOut::create(['transfer_number' => 'TRF-CHECK', 'to_entity' => 'jihans', 'date' => today(), 'status' => 'sent', 'created_by' => $actor->id]);
        $rows = collect();
        for ($i = 0; $i < 50; $i++) $rows->push(new JihansGudangStockMovement(['source' => 'transfer_out', 'reference_id' => $transfer->id]));
        DB::enableQueryLog(); DB::flushQueryLog();
        app(StockMovementDocuments::class)->load($rows);
        foreach ($rows as $row) $this->assertSame('TRF-CHECK', $row->document_number);
        $this->assertCount(1, DB::getQueryLog()); DB::disableQueryLog();
        $shortfalls = collect([
            new JihansGudangStockMovement(['source' => 'return_receiving', 'reference_id' => $transfer->id, 'notes' => 'Selisih penerimaan dari TRF-CHECK']),
            new JihansGudangStockMovement(['source' => 'return_receiving', 'reference_id' => $transfer->id, 'notes' => 'Selisih penerimaan dari TRF-WRONG']),
        ]);
        app(StockMovementDocuments::class)->load($shortfalls);
        $this->assertSame('TRF-CHECK', $shortfalls[0]->document_number); $this->assertNull($shortfalls[1]->document_number);
    }

    public function test_batch_shift_totals_match_single_calculations_and_query_count_stays_bounded(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(16, 0));
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$values) => min($values), -1);
        $user = $this->user('super_admin'); $shifts = collect();
        foreach (['jihans', 'hendhys'] as $entity) {
            $branch = Branch::create(['code' => $entity, 'name' => $entity, 'entity' => $entity, 'type' => 'pusat', 'is_active' => true]);
            // Prior closed shifts on another day and null-ended shifts must not start the period.
            CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => $entity, 'status' => 'closed', 'opened_at' => now()->subDay(), 'closed_at' => now()->subDay()->setTime(23, 0), 'starting_cash' => 0]);
            CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => $entity, 'status' => 'open', 'opened_at' => now()->setTime(7, 0), 'starting_cash' => 0]);
            for ($i = 0; $i < 15; $i++) $shifts->push(CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch->id, 'entity' => $entity,
                'status' => $i === 14 ? 'open' : 'closed', 'opened_at' => now()->setTime(8, $i), 'closed_at' => $i === 14 ? null : now()->setTime(8, $i + 1), 'starting_cash' => 123]));
            foreach ([['paid', 'cash', 500, 6], ['paid', 'cash', 1200, 9], ['pending', 'transfer', 600, 10], ['cancelled', 'cash', 10000, 12]] as [$status, $method, $amount, $hour]) {
                $id = DB::table($entity.'_transactions')->insertGetId(['transaction_number' => $entity.'-'.$hour, 'date' => today(), 'time' => "$hour:00:00", 'created_by' => $user->id, 'status' => $status, 'grand_total' => 1000, 'created_at' => now()->setTime($hour, 0)]);
                DB::table($entity.'_transaction_payments')->insert(['transaction_id' => $id, 'payment_method' => $method, 'amount' => $amount]);
            }
        }
        $expected = $shifts->mapWithKeys(fn ($s) => [$s->id => ['summary' => $s->calculatePaymentSummary(), 'cash' => $s->status === 'open' ? $s->calculateExpectedCashSoFar() : $s->expected_cash]]);
        DB::enableQueryLog(); DB::flushQueryLog();
        app(ShiftReportSummaries::class)->load($shifts);
        $queryCount = count(DB::getQueryLog()); DB::disableQueryLog();
        $this->assertSame(5, $queryCount); // Previous periods + two aggregates per business, independent of 30 shifts.
        foreach ($shifts as $s) { $this->assertEquals($expected[$s->id]['summary'], $s->payment_summary); $this->assertSame($expected[$s->id]['cash'], $s->expected_cash); }
    }
}
