<?php

namespace Tests\Feature\Owner;

use App\Models\{Branch, CashierShift, HendhysTransaction, JihansTransaction, HendhysStockBranch, JihansGudangStock, JihansGudangStockMovement, Product, ProductCategory, PurchaseOrder, Supplier, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        Role::findOrCreate('owner', 'web');
        $user = User::factory()->create(['entity' => 'all']);
        $user->assignRole('owner');
        return $user;
    }

    private function product(string $code, string $name): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pack'], ['abbreviation' => 'PACK', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        return Product::create(['code' => $code, 'name' => $name, 'unit_id' => $unit->id, 'category_id' => $category->id,
            'status' => 'active', 'source_type' => 'purchased', 'entity_scope' => 'all', 'product_type' => 'INV', 'visible_hendhys' => true]);
    }

    private function movements(User $actor, Product $product, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            JihansGudangStockMovement::create(['product_id' => $product->id, 'type' => 'in', 'source' => 'adjustment',
                'quantity' => 1, 'quantity_before' => $i, 'quantity_after' => $i + 1, 'created_by' => $actor->id,
                'created_at' => now(), 'notes' => $i === 0 ? 'Koreksi khusus 100%_A' : 'Penerimaan biasa']);
        }
    }

    private function detail(array $query = [])
    {
        return $this->get(route('owner.dashboard.detail', $query + ['mode' => 'stock', 'unit' => 'movements']));
    }

    public function test_page_size_choices_and_all_are_applied_before_loading_rows(): void
    {
        $owner = $this->owner();
        $this->movements($owner, $this->product('P-1', 'Produk'), 125);
        $this->actingAs($owner);
        DB::enableQueryLog();
        $this->detail()->assertOk()->assertInertia(fn (Assert $page) => $page->has('list', 20)
            ->where('per_page', '20')->where('pagination.total', 125)->where('pagination.from', 1)->where('pagination.to', 20)
            ->where('list.0.id', 125)->where('list.19.id', 106));
        $this->assertTrue(collect(DB::getQueryLog())->contains(fn ($q) => str_contains($q['query'], 'jihans_gudang_stock_movements')
            && str_contains($q['query'], 'limit 20')));
        DB::disableQueryLog();
        foreach ([50, 100] as $size) {
            $this->detail(['per_page' => $size])->assertInertia(fn (Assert $page) => $page->has('list', $size)->where('pagination.total', 125));
        }
        $this->detail(['page' => 2])->assertInertia(fn (Assert $page) => $page->has('list', 20)
            ->where('pagination.from', 21)->where('list.0.id', 105)->where('list.19.id', 86));
        $this->detail(['per_page' => 'all', 'page' => 3])->assertInertia(fn (Assert $page) => $page->has('list', 125)
            ->where('pagination.from', 1)->where('pagination.to', 125)->where('pagination.links', []));
    }

    public function test_search_reaches_old_rows_and_literal_wildcards_and_keeps_filters_on_links(): void
    {
        $owner = $this->owner();
        $this->movements($owner, $this->product('P-1', 'Kue Brownies'), 65);
        $this->actingAs($owner);
        $this->detail(['search' => '100%_A'])->assertInertia(fn (Assert $page) => $page->has('list', 1)->where('list.0.id', 1));
        $this->detail(['search' => 'kue brownies', 'per_page' => 50])->assertInertia(function (Assert $page) {
            $page->has('list', 50)->where('pagination.total', 65)->where('pagination.next_page_url', function ($url) {
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                return $query['search'] === 'kue brownies' && $query['per_page'] === '50'
                    && $query['mode'] === 'stock' && $query['unit'] === 'movements' && $query['page'] === '2';
            });
        });
        $this->detail(['search' => 'Tidak ditemukan'])->assertInertia(fn (Assert $page) => $page->has('list', 0)
            ->where('pagination.total', 0)->where('pagination.from', null));
        $owner->update(['name' => 'Petugas Khusus']);
        $this->detail(['search' => 'Petugas Khusus'])->assertInertia(fn (Assert $page) => $page->where('pagination.total', 65));
    }

    public function test_stock_total_counts_all_pages_and_keeps_unit_and_archived_stock(): void
    {
        $owner = $this->owner();
        for ($i = 1; $i <= 25; $i++) {
            $product = $this->product(sprintf('P-%02d', $i), sprintf('Barang %02d', $i));
            JihansGudangStock::create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 2]);
            if ($i === 25) $product->delete();
        }
        $this->actingAs($owner);
        $this->detail(['unit' => 'gudang'])->assertInertia(fn (Assert $page) => $page->has('list', 20)
            ->where('pagination.total', 25)->where('subtitle', '50 Item')->where('list.0.unit', 'PACK'));
        $this->detail(['unit' => 'gudang', 'search' => 'Barang 25'])->assertInertia(fn (Assert $page) => $page->has('list', 1)
            ->where('list.0.name', 'Barang 25')->where('subtitle', '2 Item'));
    }

    public function test_branch_stock_is_scoped_and_return_only_products_are_visible(): void
    {
        $owner = $this->owner();
        $a = Branch::create(['code' => 'A', 'name' => 'Cabang A', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $b = Branch::create(['code' => 'B', 'name' => 'Cabang B', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $product = $this->product('P-1', 'Brownies');
        HendhysStockBranch::create(['product_id' => $product->id, 'branch_id' => $a->id, 'unit_id' => $product->unit_id, 'quantity' => 0, 'quantity_return' => 3]);
        HendhysStockBranch::create(['product_id' => $product->id, 'branch_id' => $b->id, 'unit_id' => $product->unit_id, 'quantity' => 77, 'quantity_return' => 0]);
        $this->actingAs($owner)->detail(['unit' => 'hendhys_cabang_'.$a->id])->assertInertia(fn (Assert $page) => $page->has('list', 1)
            ->where('list.0.quantity', 0)->where('list.0.quantity_return', 3)->where('list.0.unit', 'PACK')->where('subtitle', '0 Item'));
    }

    public function test_po_search_reaches_supplier_and_document_outside_the_first_page(): void
    {
        $owner = $this->owner();
        $supplier = Supplier::create(['code' => 'SUP-1', 'name' => 'Pemasok Khusus', 'is_active' => true]);
        for ($i = 1; $i <= 25; $i++) PurchaseOrder::create(['po_number' => sprintf('PO-%03d', $i), 'date' => today(),
            'supplier_id' => $supplier->id, 'created_by' => $owner->id, 'status' => 'received']);
        $this->actingAs($owner)->detail(['unit' => 'po', 'search' => 'PO-001'])->assertInertia(fn (Assert $page) => $page->has('list', 1)
            ->where('list.0.po_number', 'PO-001')->where('pagination.total', 1));
        $this->detail(['unit' => 'po', 'search' => 'Pemasok Khusus'])->assertInertia(fn (Assert $page) => $page->has('list', 20)->where('pagination.total', 25));
    }

    private function transaction(string $model, string $number, User $owner, string $status = 'paid')
    {
        return $model::create([
            'transaction_number' => $number, 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Pelanggan Khusus', 'customer_type' => 'Pelanggan Retail',
            'ppn_type' => 'none', 'ppn_rate' => 0, 'subtotal' => 1000, 'discount_amount' => 0,
            'tax_amount' => 0, 'other_costs' => 0, 'grand_total' => 1000, 'status' => $status, 'created_by' => $owner->id,
        ]);
    }

    public function test_combined_business_transactions_paginate_without_mixing_same_ids_and_keep_full_revenue(): void
    {
        $owner = $this->owner();
        foreach ([JihansTransaction::class => 'JHS', HendhysTransaction::class => 'HND'] as $model => $prefix) {
            for ($i = 1; $i <= 15; $i++) $this->transaction($model, sprintf('%s-%03d', $prefix, $i), $owner);
            $this->transaction($model, $prefix.'-UNPAID', $owner, 'pending');
        }
        $this->actingAs($owner);
        $query = ['mode' => 'omset', 'unit' => 'all_transactions', 'filter' => 'this_week'];
        $this->detail($query)->assertInertia(fn (Assert $page) => $page->has('list', 20)
            ->where('pagination.total', 30)->where('subtitle', 'Total Omset: Rp 30.000'));
        $first = $this->detail($query)->inertiaProps('list');
        $second = $this->detail($query + ['page' => 2])->assertInertia(fn (Assert $page) => $page->has('list', 10)
            ->where('pagination.from', 21)->where('subtitle', 'Total Omset: Rp 30.000'))->inertiaProps('list');
        $this->assertCount(30, collect($first)->concat($second)->pluck('transaction_number')->unique());
        $this->detail($query + ['search' => 'JHS-001'])->assertInertia(fn (Assert $page) => $page->has('list', 1)
            ->where('list.0.transaction_number', 'JHS-001')->where('subtitle', 'Total Omset: Rp 1.000'));
        $this->detail($query + ['per_page' => 'all'])->assertInertia(fn (Assert $page) => $page->has('list', 30));
    }

    public function test_single_business_shift_pages_and_search_are_independent_of_transaction_pages(): void
    {
        // Keep the existing MySQL payment calculation intact in the SQLite fixture.
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$values) => min($values), -1);
        $owner = $this->owner();
        for ($i = 1; $i <= 25; $i++) {
            $this->transaction(JihansTransaction::class, sprintf('JHS-%03d', $i), $owner);
            CashierShift::create(['user_id' => $owner->id, 'entity' => 'jihans', 'status' => 'closed',
                'opened_at' => today()->addHours(8), 'closed_at' => today()->addHours(9),
                'starting_cash' => 0, 'expected_cash' => 0, 'actual_cash' => 0, 'note' => $i === 1 ? 'Pengecekan lama' : 'Selesai']);
        }
        CashierShift::create(['user_id' => $owner->id, 'entity' => 'hendhys', 'status' => 'closed',
            'opened_at' => today()->addHours(8), 'closed_at' => today()->addHours(9), 'starting_cash' => 0]);
        $this->actingAs($owner);
        $query = ['mode' => 'omset', 'unit' => 'jihans_transactions', 'filter' => 'this_week'];
        $this->detail($query + ['shift_page' => 2])->assertInertia(fn (Assert $page) => $page->has('list', 20)->has('shifts', 5)
            ->where('pagination.from', 1)->where('shifts_pagination.total', 25)->where('shifts_pagination.from', 21));
        $this->detail($query + ['search' => 'Pengecekan lama'])->assertInertia(fn (Assert $page) => $page->has('list', 0)->has('shifts', 1)
            ->where('shifts.0.note', 'Pengecekan lama'));
        $this->detail($query + ['search' => 'Pelanggan Khusus', 'per_page' => 50])->assertInertia(fn (Assert $page) => $page->has('list', 25)
            ->where('pagination.total', 25)->where('subtitle', 'Total Omset: Rp 25.000'));
    }

    public function test_invalid_paging_is_rejected(): void
    {
        $this->actingAs($this->owner());
        foreach ([['per_page' => 9999], ['page' => -1], ['search' => str_repeat('a', 201)]] as $query) {
            $this->getJson(route('owner.dashboard.detail', $query + ['mode' => 'stock', 'unit' => 'movements']))->assertUnprocessable();
        }
    }
}
