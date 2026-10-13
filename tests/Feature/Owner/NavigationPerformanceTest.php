<?php

namespace Tests\Feature\Owner;

use App\Models\{HendhysStockPusat, ProductCategory, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function seedStock(int $count): User
    {
        Role::findOrCreate('owner', 'web');
        $owner = User::factory()->create(['entity' => 'all']);
        $owner->assignRole('owner');
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::create(['name' => 'Produksi', 'entity_scope' => 'all']);
        $products = $stocks = $lots = [];
        $timestamp = now()->toDateTimeString();
        for ($i = 1; $i <= $count; $i++) {
            $products[] = ['id' => $i, 'code' => sprintf('PERF-%04d', $i), 'name' => sprintf('Produk %04d', $i),
                'unit_id' => $unit->id, 'category_id' => $category->id, 'status' => 'active', 'source_type' => 'produced',
                'entity_scope' => 'hendhys', 'visible_hendhys' => 1, 'product_type' => 'INV'];
            $stocks[] = ['product_id' => $i, 'unit_id' => $unit->id, 'quantity' => 5];
            foreach ([0, 5] as $remaining) {
                $productionId = $i + ($remaining === 0 ? $count : 0);
                $lots[] = ['entity' => 'hendhys', 'product_id' => $i, 'unit_id' => $unit->id, 'production_id' => $productionId,
                    'production_number' => 'HND-PERF-'.$i, 'production_date' => '2026-08-02', 'quantity' => 5,
                    'remaining' => $remaining, 'created_at' => $timestamp, 'updated_at' => $timestamp];
            }
        }
        DB::table('master_products')->insert($products);
        DB::table('hendhys_stock_pusat')->insert($stocks);
        DB::table('production_lots')->insert($lots);
        return $owner;
    }

    private function measuredGet(string $url): array
    {
        DB::flushQueryLog(); DB::enableQueryLog();
        $response = $this->get($url, ['X-Inertia' => 'true',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))]);
        $queries = collect(DB::getQueryLog()); DB::disableQueryLog();
        $response->assertOk();
        return [$response, $queries];
    }

    public function test_large_dashboard_avoids_product_queries_and_computes_monitor_once(): void
    {
        $this->actingAs($this->seedStock(80));
        [$response, $queries] = $this->measuredGet(route('owner.dashboard'));
        $response->assertJsonMissingPath('props.details')->assertJsonMissingPath('props.production_monitor.stocks')
            ->assertJsonMissingPath('props.production_monitor.overdue_lots')
            ->assertJsonPath('props.stats.stock.hendhys_pusat', 400)
            ->assertJsonPath('props.production_monitor.overdue_count', 80);
        $this->assertLessThan(50, $queries->count(), 'Queries must not grow once per product.');
        $this->assertCount(1, $queries->filter(fn ($q) => str_starts_with($q['query'], 'select') && str_contains($q['query'], 'from "production_lots"')));
        $this->assertFalse($queries->contains(fn ($q) => str_contains($q['query'], '"master_units"."id" = ?')));
        $this->assertLessThan(15000, strlen($response->getContent()), 'Dashboard should only send summaries.');
    }

    public function test_monitor_reuses_one_request_but_sees_stock_changes_on_next_request(): void
    {
        $this->actingAs($this->seedStock(25));
        $url = route('owner.dashboard.detail', ['mode' => 'stock', 'unit' => 'hendhys_pusat']);
        [$first] = $this->measuredGet($url);
        $first->assertJsonCount(20, 'props.list')->assertJsonCount(20, 'props.production_monitor.stocks')
            ->assertJsonPath('props.production_monitor.overdue_count', 25)->assertJsonPath('props.pagination.total', 25);
        [$unchanged, $queries] = $this->measuredGet($url);
        $this->assertFalse($queries->contains(fn ($q) => str_contains($q['query'], 'production_stock_alerts')
            && preg_match('/^(insert|update|delete)/i', $q['query'])), 'An unchanged GET must not rewrite alert rows.');
        $this->assertCount(1, $queries->filter(fn ($q) => str_starts_with($q['query'], 'select') && str_contains($q['query'], 'from "production_lots"')));
        HendhysStockPusat::where('product_id', 1)->update(['quantity' => 0]);
        DB::table('production_lots')->where('product_id', 1)->update(['remaining' => 0]);
        [$changed] = $this->measuredGet($url);
        $changed->assertJsonPath('props.production_monitor.overdue_count', 24);
        $this->assertDatabaseMissing('production_stock_alerts', ['product_id' => 1, 'resolved_at' => null]);
        [$movements] = $this->measuredGet(route('owner.dashboard.detail', ['mode' => 'stock', 'unit' => 'movements']));
        $movements->assertJsonMissingPath('props.production_monitor.stocks')->assertJsonMissingPath('props.production_monitor.overdue_lots');
    }

    public function test_index_migration_can_resume_without_changing_inventory(): void
    {
        $this->seedStock(1);
        $migration = require database_path('migrations/2026_10_13_050000_add_navigation_query_indexes.php');
        $migration->down();
        Schema::table('production_lots', fn ($table) => $table->index(['entity', 'remaining', 'production_date'], 'nav_lots_remaining_index'));
        $migration->up(); $migration->up();
        foreach (['production_lots' => 'nav_lots_remaining_index', 'hendhys_transactions' => 'nav_hendhys_sales_index',
            'gudang_transfer_requests' => 'nav_requests_status_index', 'gudang_transfer_out' => 'nav_transfers_pending_index'] as $table => $name) {
            $this->assertTrue(Schema::hasIndex($table, $name));
        }
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => 1, 'quantity' => 5]);
        $this->assertDatabaseCount('production_lots', 2);
        $migration->down(); $migration->down();
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => 1, 'quantity' => 5]);
        $this->assertDatabaseCount('production_lots', 2);
        $migration->up();
    }
}
