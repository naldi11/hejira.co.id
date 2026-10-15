<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, Unit, User};
use App\Services\{BranchStockTransfers, ProductStockUnits};
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseTransferAndMasterUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite' || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Pengujian mutasi hanya boleh memakai SQLite :memory:.');
        }
        return $app;
    }

    private function branch(string $entity): Branch
    {
        if ($entity === 'gudang') return Branch::where('entity', 'gudang')->firstOrFail();
        return Branch::create(['code' => Str::random(10), 'name' => $entity.' uji', 'type' => 'cabang', 'entity' => $entity, 'is_active' => true]);
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Kilogram uji'], ['abbreviation' => 'KG', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan uji'], ['entity_scope' => 'all']);
        $product = Product::create(['code' => Str::random(10), 'name' => 'Bahan uji', 'category_id' => $category->id, 'unit_id' => $unit->id, 'entity_scope' => 'all', 'status' => 'active', 'product_type' => 'INV', 'source_type' => 'purchased']);
        DB::table('izzi_ingredients')->insert(['product_id' => $product->id, 'purchase_unit' => 'KG', 'purchase_factor' => 1000, 'created_at' => now(), 'updated_at' => now()]);
        return $product;
    }

    private function actor(?Branch $branch = null, string $role = 'super_admin'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $role === 'super_admin' ? 'all' : $branch->entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);
        $this->actingAs($user)->withSession(['active_branch_id' => $branch?->id]);
        return $user;
    }

    private function stock(Branch $branch, Product $product, int $quantity, ?int $unit = null): void
    {
        $service = app(BranchStockTransfers::class);
        DB::table($service->stockTable($branch->entity))->insert([
            'product_id' => $product->id, 'unit_id' => $unit ?? $product->unit_id,
            'quantity' => $quantity * $service->scale($branch->entity), 'last_updated' => now(),
            ...($service->hasBranchStock($branch->entity) ? ['branch_id' => $branch->id] : []),
        ]);
    }

    private function data(Branch $from, Branch $to, Product $product, int $quantity = 3): array
    {
        return ['request_key' => (string) Str::uuid(), 'from_branch_id' => $from->id, 'to_branch_id' => $to->id,
            'date' => now()->toDateString(), 'occurred_time' => now()->format('H:i'), 'physical_sender_name' => 'Pengirim uji', 'physical_receiver_name' => 'Penerima uji',
            'reason' => 'Mutasi bahan untuk operasional', 'confirmed' => true, 'items' => [['product_id' => $product->id, 'quantity' => $quantity]]];
    }

    public static function routes(): array
    {
        return [['hendhys', 'gudang'], ['jihans', 'gudang'], ['izzi', 'gudang'], ['gudang', 'hendhys'], ['gudang', 'jihans'], ['gudang', 'izzi']];
    }

    #[DataProvider('routes')]
    public function test_warehouse_transfers_use_the_existing_balances_and_ledgers(string $source, string $destination): void
    {
        $from = $this->branch($source); $to = $this->branch($destination); $product = $this->product();
        $this->stock($from, $product, 20); $this->stock($to, $product, 5); $this->actor();
        $data = $this->data($from, $to, $product);
        $this->get(route('branch-transfers.create', ['from_branch_id' => $from->id, 'to_branch_id' => $to->id]))->assertOk();
        $this->getJson(route('branch-transfers.source-products', $from))->assertOk()->assertJsonPath('products.0.unit', 'KG');
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $service = app(BranchStockTransfers::class);
        $this->assertSame(17 * $service->scale($source), (int) $service->stockQuery($from, $product->id)->value('quantity'));
        $this->assertSame(8 * $service->scale($destination), (int) $service->stockQuery($to, $product->id)->value('quantity'));
        $id = DB::table('branch_stock_transfers')->value('id');
        $this->get(route('branch-transfers.show', $id))->assertOk();
        $this->assertDatabaseHas('jihans_gudang_stock_movements', ['branch_transfer_id' => $id, 'branch_transfer_side' => $source === 'gudang' ? 'from' : 'to', 'quantity' => 3]);
        $this->assertSame(DB::table('branch_stock_transfers')->value('number'), \App\Models\JihansGudangStockMovement::firstOrFail()->document_number);
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('branch_stock_transfers', 1);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 1);
    }

    public function test_master_unit_is_used_without_rescaling_old_balances(): void
    {
        $from = $this->branch('hendhys'); $to = $this->branch('gudang'); $product = $this->product();
        $old = Unit::create(['name' => 'Tiang uji', 'abbreviation' => 'TIANG', 'entity_scope' => 'all']);
        $this->stock($from, $product, 8, $old->id); $this->stock($to, $product, 6, $old->id); $this->actor();
        $this->getJson(route('branch-transfers.source-products', $from))->assertOk()->assertJsonPath('products.0.unit', 'KG')->assertJsonPath('products.0.stock_unit', 'KG')->assertJsonPath('products.0.quantity', 8)->assertJsonPath('products.0.unit_matches', true);
        $this->post(route('branch-transfers.store'), $this->data($from, $to, $product))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hendhys_stock_branch', ['branch_id' => $from->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_id' => $product->unit_id]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 9, 'unit_id' => $product->unit_id]);
        $this->assertDatabaseHas('master_products', ['id' => $product->id, 'unit_id' => $product->unit_id]);
    }

    public function test_future_master_changes_sync_current_cards_and_keep_quantities(): void
    {
        $product = $this->product(); $branch = $this->branch('hendhys'); $izzi = $this->branch('izzi');
        foreach (ProductStockUnits::TABLES as $table) {
            DB::table($table)->insert(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 8, 'last_updated' => now(),
                ...($table === 'hendhys_stock_branch' ? ['branch_id' => $branch->id] : []), ...($table === 'izzi_stock' ? ['branch_id' => $izzi->id] : [])]);
        }
        $unit = Unit::create(['name' => 'Satuan master baru', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product->update(['unit_id' => $unit->id]);
        foreach (ProductStockUnits::TABLES as $table) $this->assertDatabaseHas($table, ['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 8]);
    }

    public function test_branch_users_can_send_to_warehouse_but_cannot_manage_warehouse_stock(): void
    {
        $from = $this->branch('hendhys'); $to = $this->branch('gudang'); $product = $this->product(); $this->stock($from, $product, 8);
        $this->actor($from, 'kasir_hendhys');
        $this->post(route('branch-transfers.store'), $this->data($from, $to, $product))->assertSessionHasNoErrors();
        $this->getJson(route('branch-transfers.source-products', $to))->assertForbidden();
        $this->post(route('branch-transfers.store'), $this->data($to, $from, $product))->assertForbidden();
    }

    public function test_insufficient_stock_rolls_back_all_balances_units_and_history(): void
    {
        $from = $this->branch('gudang'); $to = $this->branch('hendhys'); $product = $this->product(); $this->stock($from, $product, 2); $this->actor();
        $this->post(route('branch-transfers.store'), $this->data($from, $to, $product, 3))->assertSessionHasErrors('items');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseCount('branch_stock_transfers', 0); $this->assertDatabaseCount('hendhys_stock_branch', 0);
    }

    public function test_produced_warehouse_balances_keep_lot_totals_in_sync(): void
    {
        $from = $this->branch('gudang'); $to = $this->branch('hendhys'); $product = $this->product(); $product->update(['source_type' => 'produced']);
        $this->stock($from, $product, 8); $actor = $this->actor(); $data = $this->data($from, $to, $product);
        $id = app(BranchStockTransfers::class)->send($data, $actor);
        $this->assertSame(5, (int) DB::table('production_lots')->where('product_id', $product->id)->sum('remaining'));
        app(BranchStockTransfers::class)->cancel($id, 'Barang belum diterima tujuan', $actor);
        $this->assertSame(8, (int) DB::table('production_lots')->where('product_id', $product->id)->sum('remaining'));
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 8]);
    }

    public function test_goods_received_from_izzi_are_visible_in_the_warehouse_stock_page(): void
    {
        $from = $this->branch('izzi'); $to = $this->branch('gudang'); $product = $this->product();
        $product->update(['entity_scope' => 'izzi', 'visible_gudang' => false]);
        $this->stock($from, $product, 8); $this->actor();
        $this->post(route('branch-transfers.store'), $this->data($from, $to, $product))->assertSessionHasNoErrors();
        $this->get(route('gudang.stock.index', ['search' => $product->code]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('stocks.data', 1)->where('stocks.data.0.code', $product->code)->where('stocks.data.0.current_stock', 3)->where('stocks.data.0.unit', 'KG'));
        $this->assertDatabaseHas('master_products', ['id' => $product->id, 'entity_scope' => 'izzi', 'visible_gudang' => false]);
    }
}
