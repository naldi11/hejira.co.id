<?php

namespace Tests\Performance;

use App\Models\{ProductCategory, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Repeatable isolated navigation benchmark; never connects to operational data. */
class NavigationBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_benchmark(): void
    {
        $category = ProductCategory::create(['name' => 'Benchmark', 'entity_scope' => 'all']);
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        Role::findOrCreate('owner', 'web'); Role::findOrCreate('super_admin', 'web');
        $owner = User::factory()->create(['entity' => 'all']); $owner->assignRole('owner');
        $admin = User::factory()->create(['entity' => 'gudang']); $admin->assignRole('super_admin');
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
        $report = ['environment' => 'PHP '.PHP_VERSION.' / isolated SQLite :memory:',
            'fixture' => ['products' => 500, 'movements' => 9000, 'production_lots' => 5500, 'active_lots' => 500], 'pages' => []];
        foreach ([
            'owner_dashboard' => [$owner, route('owner.dashboard')],
            'owner_movements' => [$owner, route('owner.dashboard.detail', ['mode' => 'stock', 'unit' => 'movements'])],
            'warehouse_stock' => [$admin, route('gudang.stock.index')],
            'master_products' => [$admin, route('master.products.index')],
        ] as $key => [$actor, $url]) {
            $this->actingAs($actor);
            $runs = [];
            for ($iteration = 0; $iteration < 3; $iteration++) {
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
                if ($iteration === 2) $report['pages'][$key]['query_groups'] = $queries->countBy(fn ($q) => $q['query'])->sortDesc()->take(5)->all();
            }
            $report['pages'][$key]['runs'] = $runs;
        }
        if ($output = getenv('HEJIRA_PROFILE_OUTPUT')) file_put_contents($output, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
