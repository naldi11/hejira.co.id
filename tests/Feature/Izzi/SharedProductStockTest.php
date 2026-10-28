<?php

namespace Tests\Feature\Izzi;

use App\Models\{Branch, Product, ProductCategory, Unit, User};
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SharedProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite' || $app['config']->get('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Pengujian hanya boleh memakai SQLite :memory:.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 10, 15)->setTime(15, 0));
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Kilogram master bersama'], ['abbreviation' => 'KG', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan master bersama'], ['entity_scope' => 'all']);
        return Product::create(['code' => Str::random(10), 'name' => 'Daging master bersama', 'unit_id' => $unit->id,
            'category_id' => $category->id, 'entity_scope' => 'all', 'status' => 'active', 'product_type' => 'INV', 'source_type' => 'purchased']);
    }

    private function branch(): Branch
    {
        return Branch::create(['code' => Str::random(10), 'name' => 'Izzi bersama', 'entity' => 'izzi', 'type' => 'cabang', 'is_active' => true]);
    }

    private function actor(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);
        $this->actingAs($user)->withSession(['active_branch_id' => $branch?->id]);
        return $user;
    }

    private function warehouseStock(Product $product): Branch
    {
        $warehouse = Branch::where('entity', 'gudang')->firstOrFail();
        DB::table('jihans_gudang_stock')->insert(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 20, 'last_updated' => now()]);
        return $warehouse;
    }

    private function transfer(Branch $warehouse, Branch $branch, Product $product): array
    {
        return ['request_key' => (string) Str::uuid(), 'from_branch_id' => $warehouse->id, 'to_branch_id' => $branch->id,
            'date' => '2026-10-15', 'occurred_time' => '14:00', 'physical_sender_name' => 'Petugas Gudang', 'physical_receiver_name' => 'Petugas Izzi',
            'reason' => 'Bahan sudah diterima cabang Izzi', 'confirmed' => true, 'items' => [['product_id' => $product->id, 'quantity' => 3]]];
    }

    public function test_catalogue_reuses_master_products_and_packaging_updates_only_izzi_metadata(): void
    {
        $product = $this->product(); $this->warehouseStock($product); $branch = $this->branch();
        $this->actor('admin_izzi', 'izzi', $branch);
        $before = $product->fresh()->getAttributes(); $unitCount = Unit::count(); $productCount = Product::count();
        $this->getJson(route('izzi.ingredients', ['lookup' => 'products', 'q' => $product->code]))->assertOk()
            ->assertJsonPath('products.0.id', $product->id)->assertJsonPath('products.0.unit', 'KG')
            ->assertJsonPath('products.0.purchase_factor', '1')->assertJsonPath('products.0.configured', 0);
        foreach (['2.5', '7.125'] as $factor) {
            $this->put(route('izzi.ingredients.update', $product->id), ['product_id' => $product->id,
                'purchase_unit' => 'Kemasan pilihan pengguna', 'purchase_factor' => $factor])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseHas('izzi_ingredients', ['product_id' => $product->id, 'purchase_factor' => 7125]);
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame($unitCount, Unit::count()); $this->assertSame($productCount, Product::count());
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 20]);
    }

    public function test_izzi_setup_cannot_create_a_product_or_override_its_master_fields(): void
    {
        $product = $this->product(); $branch = $this->branch(); $this->actor('admin_izzi', 'izzi', $branch);
        $count = Product::count();
        $this->post(route('izzi.ingredients.store'), ['name' => 'Bahan baru', 'code' => 'BARU', 'unit' => 'gram',
            'purchase_unit' => 'Bungkus', 'purchase_factor' => 20])->assertSessionHasErrors(['product_id', 'name', 'code', 'unit']);
        $this->put(route('izzi.ingredients.update', $product->id), ['product_id' => $product->id, 'unit_id' => $product->unit_id,
            'name' => 'Nama diubah', 'purchase_unit' => 'Bungkus', 'purchase_factor' => 20])->assertSessionHasErrors(['unit_id', 'name']);
        $this->assertSame($count, Product::count()); $this->assertDatabaseCount('izzi_ingredients', 0);
        $this->assertDatabaseHas('master_products', ['id' => $product->id, 'name' => $product->name, 'unit_id' => $product->unit_id]);
    }

    public function test_warehouse_supply_shared_recipe_sale_cancellation_and_opname_need_no_separate_ingredient(): void
    {
        $product = $this->product(); $warehouse = $this->warehouseStock($product); $branch = $this->branch();
        $this->actor('super_admin', 'gudang'); $data = $this->transfer($warehouse, $branch, $product);
        $this->get(route('gudang.transfer-out.create'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('izziBranches', 1)->where('izziBranches.0.id', $branch->id)->missing('izziTransferUrl'));
        $this->getJson(route('branch-transfers.destination-products', ['branch' => $branch, 'q' => $product->code]))->assertOk()->assertJsonPath('products.0.id', $product->id);
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 17]);
        $this->assertDatabaseHas('izzi_stock', ['branch_id' => $branch->id, 'product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 3000]);
        $this->assertDatabaseHas('branch_stock_transfer_items', ['product_id' => $product->id, 'to_product_id' => $product->id, 'unit_id' => $product->unit_id, 'to_unit_id' => $product->unit_id]);
        $admin = $this->actor('admin_izzi', 'izzi', $branch);
        $this->get(route('izzi.stock'))->assertInertia(fn (Assert $page) => $page->has('stocks.data', 1)->where('stocks.data.0.quantity', '3')->where('stocks.data.0.unit', 'KG'));
        $this->post(route('izzi.menus.store'), ['code' => 'KEBAB-UJI', 'name' => 'Kebab master bersama', 'category' => 'Kebab', 'price' => 15000,
            'is_active' => true, 'recipe' => [['product_id' => $product->id, 'quantity' => '0.04']]])->assertSessionHasNoErrors();
        $menu = DB::table('izzi_menus')->value('id'); $cashier = $this->actor('kasir_izzi', 'izzi', $branch);
        $this->post(route('izzi.shifts.open'), ['starting_cash' => 100000])->assertSessionHasNoErrors();
        $this->post(route('izzi.checkout'), ['checkout_key' => (string) Str::uuid(), 'branch_id' => $branch->id, 'payment_type' => 'cash', 'amount_paid' => 50000,
            'items' => [['menu_id' => $menu, 'price' => 15000, 'quantity' => 2]]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $product->id, 'quantity' => 2920]);
        $this->assertDatabaseHas('izzi_sale_ingredients', ['product_id' => $product->id, 'unit_name' => 'KG', 'per_menu' => 40, 'quantity' => 80]);
        $sale = DB::table('izzi_sales')->value('id'); $line = DB::table('izzi_sale_lines')->value('id');
        $this->actingAs($admin)->post(route('izzi.sales.cancel', $sale), ['request_key' => (string) Str::uuid(), 'branch_id' => $branch->id,
            'reason' => 'Menu belum dibuat, pelanggan membatalkan', 'confirmed' => true, 'items' => [['line_id' => $line, 'unused' => 2, 'wasted' => 0]]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $product->id, 'quantity' => 3000]);
        $this->actor('owner');
        $this->post(route('owner.stock-opname.open'), ['request_key' => (string) Str::uuid(), 'location_key' => 'branch-'.$branch->id,
            'expires_at' => '2026-10-15T17:00', 'notes' => 'Hitung bahan master bersama', 'confirmed' => true])->assertSessionHasNoErrors();
        $session = DB::table('stock_opname_sessions')->value('id');
        $this->actingAs($cashier)->withSession(['active_branch_id' => $branch->id])->post(route('stock-opname.count', $session), [
            'product_id' => $product->id, 'expected_quantity' => '3', 'quantity' => '2.99', 'notes' => 'Hasil timbang pengguna', 'confirmed' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $product->id, 'quantity' => 2990]);
        $this->assertDatabaseCount('izzi_ingredients', 0); $this->assertDatabaseCount('master_products', 1);
        $this->assertDatabaseHas('master_products', ['id' => $product->id, 'unit_id' => $product->unit_id, 'entity_scope' => 'all']);
    }

    public function test_direct_receipt_uses_shared_units_and_user_defined_packaging(): void
    {
        $product = $this->product(); $branch = $this->branch(); $this->actor('admin_izzi', 'izzi', $branch);
        $receipt = ['branch_id' => $branch->id, 'date' => '2026-10-15', 'time' => '14:00', 'origin' => 'Pemasok langsung', 'notes' => 'Barang diterima sesuai jumlah fisik',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'mode' => 'base']]];
        $this->post(route('izzi.receipts.store'), $receipt + ['request_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $product->id, 'quantity' => 2000]);
        $this->put(route('izzi.ingredients.update', $product->id), ['purchase_unit' => 'Karung pengguna', 'purchase_factor' => '7.5'])->assertSessionHasNoErrors();
        $receipt['items'][0] = ['product_id' => $product->id, 'quantity' => 1, 'mode' => 'purchase'];
        $this->post(route('izzi.receipts.store'), $receipt + ['request_key' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('izzi_stock', ['product_id' => $product->id, 'quantity' => 9500]);
        $this->assertDatabaseCount('master_products', 1);
    }

    public function test_izzi_transfer_cannot_replace_shared_product_with_a_second_master(): void
    {
        $product = $this->product(); $other = $this->product(); $warehouse = $this->warehouseStock($product); $branch = $this->branch();
        $this->actor('super_admin'); $data = $this->transfer($warehouse, $branch, $product);
        $data['items'][0] += ['to_product_id' => $other->id, 'conversion_from' => 1, 'conversion_to' => 1];
        $this->post(route('branch-transfers.store'), $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('branch_stock_transfers', 0); $this->assertDatabaseCount('izzi_stock', 0);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 20]);
    }
}
