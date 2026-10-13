<?php

namespace Tests\Performance;

use App\Models\{Branch, CashierShift, ProductCategory, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Repeatable isolated navigation benchmark; never connects to operational data. */
class OperationalBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_benchmark(): void
    {
        $category = ProductCategory::create(['name' => 'Benchmark', 'entity_scope' => 'all']);
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        Role::findOrCreate('owner', 'web'); Role::findOrCreate('super_admin', 'web');
        $owner = User::factory()->create(['entity' => 'all']); $owner->assignRole('owner');
        $admin = User::factory()->create(['entity' => 'all']); $admin->assignRole('super_admin');
        $products = $stocks = $lots = $movements = [];
        $timestamp = now()->toDateTimeString();
        for ($i = 1; $i <= 500; $i++) {
            $products[] = ['id' => $i, 'code' => sprintf('BENCH-%04d', $i), 'name' => sprintf('Produk %04d', $i),
                'unit_id' => $unit->id, 'category_id' => $category->id, 'status' => 'active', 'source_type' => 'produced',
                'entity_scope' => 'all', 'visible_jihans' => 1, 'visible_hendhys' => 1, 'product_type' => 'INV', 'stock_min' => 1];
            $stocks[] = ['product_id' => $i, 'unit_id' => $unit->id, 'quantity' => 5];
        }
        DB::table('master_products')->insert($products);
        foreach (['jihans_gudang_stock', 'jihans_retail_stock', 'hendhys_stock_pusat'] as $table) DB::table($table)->insert($stocks);
        for ($i = 1; $i <= 5500; $i++) $lots[] = [
            'entity' => 'hendhys', 'product_id' => (($i - 1) % 500) + 1, 'unit_id' => $unit->id,
            'production_id' => $i, 'production_number' => 'HND-BENCH-'.$i, 'production_date' => '2026-08-02',
            'quantity' => 5, 'remaining' => $i > 5000 ? 5 : 0, 'created_at' => $timestamp, 'updated_at' => $timestamp,
        ];
        foreach (array_chunk($lots, 500) as $chunk) DB::table('production_lots')->insert($chunk);
        for ($i = 1; $i <= 9000; $i++) $movements[] = [
            'product_id' => (($i - 1) % 500) + 1, 'type' => 'in', 'source' => 'adjustment',
            'quantity' => 1, 'quantity_before' => 0, 'quantity_after' => 1, 'notes' => 'Catatan benchmark',
            'created_by' => $admin->id, 'created_at' => $timestamp,
        ];
        foreach (array_chunk($movements, 500) as $chunk) DB::table('jihans_gudang_stock_movements')->insert($chunk);
        $branch = Branch::create(['code' => 'BENCH-HND', 'name' => 'Hendhys benchmark', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        Role::findOrCreate('kasir_jihans', 'web'); Role::findOrCreate('kasir_hendhys', 'web');
        $jihan = User::factory()->create(['entity' => 'jihans']); $jihan->assignRole('kasir_jihans');
        $hendhys = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]); $hendhys->assignRole('kasir_hendhys');
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$values) => min($values), -1);
        DB::table('hendhys_stock_branch')->insert(array_map(fn ($s) => $s + ['branch_id' => $branch->id], $stocks));
        $accounts = [];
        for ($i = 1; $i <= 500; $i++) $accounts[] = ['name' => 'Akun '.sprintf('%04d', $i), 'email' => 'bench'.$i.'@example.test', 'password' => 'not-a-real-account', 'entity' => 'jihans', 'is_active' => true];
        DB::table('master_users')->insert($accounts);
        foreach (['jihans' => $jihan, 'hendhys' => $hendhys] as $entity => $cashier) {
            CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $cashier->branch_id, 'entity' => $entity, 'status' => 'open', 'opened_at' => now()->startOfDay()->setTime(8, 0), 'starting_cash' => 100]);
            $transactions = $payments = $transfers = [];
            for ($i = 1; $i <= 1000; $i++) {
                $transactions[] = ['id' => $i, 'transaction_number' => 'BENCH-'.$entity.'-'.$i, 'date' => today()->toDateString(), 'time' => '10:00:00',
                    'created_by' => $cashier->id, 'customer_name' => 'Pelanggan benchmark', 'grand_total' => 1000, 'status' => 'paid', 'created_at' => $timestamp];
                $payments[] = ['transaction_id' => $i, 'payment_method' => 'cash', 'amount' => 1000];
            }
            foreach (array_chunk($transactions, 500) as $chunk) DB::table($entity.'_transactions')->insert($chunk);
            foreach (array_chunk($payments, 500) as $chunk) DB::table($entity.'_transaction_payments')->insert($chunk);
            for ($i = 1; $i <= 40; $i++) CashierShift::create(['user_id' => $cashier->id, 'branch_id' => $cashier->branch_id, 'entity' => $entity, 'status' => 'closed', 'opened_at' => now()->subDays($i)->startOfDay(), 'closed_at' => now()->subDays($i)->setTime(17, 0), 'starting_cash' => 100]);
            for ($i = 1; $i <= 200; $i++) $transfers[] = ['transfer_number' => 'SEND-'.$entity.'-'.$i, 'to_entity' => $entity, 'branch_id' => $entity === 'hendhys' ? $branch->id : null, 'date' => today()->toDateString(), 'status' => 'sent', 'created_by' => $admin->id, 'created_at' => $timestamp];
            DB::table('gudang_transfer_out')->insert($transfers);
        }
        $report = ['environment' => 'PHP '.PHP_VERSION.' / isolated SQLite :memory:',
            'fixture' => ['products' => 500, 'movements' => 9000, 'production_lots' => 5500, 'active_lots' => 500, 'users' => 504, 'transactions_per_unit' => 1000, 'shifts_per_unit' => 41, 'incoming_shipments_per_unit' => 200], 'pages' => []];
        foreach ([
            'superadmin_dashboard' => [$admin, route('gudang.dashboard')],
            'superadmin_users' => [$admin, route('master.users.index')],
            'superadmin_products' => [$admin, route('master.products.index')],
            'superadmin_stock' => [$admin, route('gudang.stock.index')],
            'superadmin_movements' => [$admin, route('gudang.stock.movements')],
            'superadmin_shipments' => [$admin, route('gudang.transfer-out.index')],
            'jihans_dashboard' => [$jihan, route('jihans.dashboard')],
            'jihans_stock' => [$jihan, route('jihans.stock.index')],
            'jihans_incoming' => [$admin, route('jihans.transfer-requests.index')],
            'jihans_pos' => [$jihan, route('jihans.pos.index')],
            'jihans_shift_report' => [$admin, route('jihans.reports.laci')],
            'hendhys_dashboard' => [$hendhys, route('hendhys.dashboard')],
            'hendhys_stock' => [$hendhys, route('hendhys.stock.index')],
            'hendhys_incoming' => [$hendhys, route('hendhys.transfer-to-branch.index', ['tab' => 'gudang'])],
            'hendhys_pos' => [$hendhys, route('hendhys.pos.index')],
            'hendhys_shift_report' => [$admin, route('hendhys.reports.laci')],
        ] as $key => [$actor, $url]) {
            $this->actingAs($actor);
            $runs = [];
            for ($iteration = 0; $iteration < 1; $iteration++) {
                DB::flushQueryLog(); DB::enableQueryLog();
                $start = hrtime(true);
                $response = $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))]);
                $elapsed = (hrtime(true) - $start) / 1e6;
                $queries = collect(DB::getQueryLog()); DB::disableQueryLog();
                $response->assertOk();
                $runs[] = ['request_ms' => round($elapsed, 2), 'query_count' => $queries->count(),
                    'sql_ms' => round($queries->sum('time'), 2), 'json_bytes' => strlen($response->getContent()),
                    'slowest_query_ms' => round($queries->max('time'), 2)];
                fwrite(STDERR, $key.' run '.($iteration + 1).': '.round($elapsed, 2).' ms / '.$queries->count()." queries\n");
                if ($iteration === 0) $report['pages'][$key]['query_groups'] = $queries->countBy(fn ($q) => $q['query'])->sortDesc()->take(5)->all();
                if ($queries->max('time') > 50) {
                    $slowest = $queries->sortByDesc('time')->first();
                    $report['pages'][$key]['slowest_query_plan'] = DB::select('EXPLAIN QUERY PLAN '.$slowest['query'], $slowest['bindings']);
                }
            }
            $report['pages'][$key]['runs'] = $runs;
        }
        if ($output = getenv('HEJIRA_PROFILE_OUTPUT')) file_put_contents($output, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
