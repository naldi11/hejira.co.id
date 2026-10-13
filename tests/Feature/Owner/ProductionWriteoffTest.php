<?php

namespace Tests\Feature\Owner;

use App\Models\{HendhysProduction, HendhysStockBranch, HendhysStockPusat, JihansGudangStock, JihansProductionSession, Product, ProductCategory, Unit, User, Branch};
use App\Services\{ActivityLogService, ProductionLots, ProductionStockMonitor, StockService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionWriteoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 19:00', 'Asia/Jakarta'));
    }

    private function owner(string $role = 'owner'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['name' => 'Owner Uji', 'entity' => 'all']);
        $user->assignRole($role);
        return $user;
    }

    private function product(): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pcs'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Produksi'], ['entity_scope' => 'all']);
        return Product::create(['code' => fake()->unique()->numerify('W-#####'), 'name' => 'Brownies Cokelat',
            'unit_id' => $unit->id, 'category_id' => $category->id, 'product_type' => 'INV',
            'source_type' => 'produced', 'entity_scope' => 'all', 'status' => 'active']);
    }

    private function produce(Product $p, string $entity, string $number, int $qty = 20, string $date = '2026-10-11')
    {
        $attrs = ['type' => 'aktual', 'date' => $date, 'created_by' => $this->owner()->id];
        $stock = app(StockService::class);
        if ($entity === 'hendhys') {
            $prod = HendhysProduction::create($attrs + ['production_number' => $number]);
            $prod->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_produced' => $qty]);
            $stock->creditHendhys($p->id, $p->unit_id, $qty, null, 'production', $prod->id, $prod->created_by);
        } else {
            $prod = JihansProductionSession::create($attrs + ['session_number' => $number]);
            $prod->details()->create(['product_id' => $p->id, 'quantity' => $qty]);
            $stock->creditJihansGudang($p->id, $p->unit_id, $qty, 'production', $prod->id, $prod->created_by);
        }
        return $prod;
    }

    private function data(Product $p, string $entity, ?int $lotId, array $extra = []): array
    {
        return $extra + ['request_key' => (string) Str::uuid(), 'entity' => $entity, 'product_id' => $p->id,
            'lot_id' => $lotId, 'expected_remaining' => 20, 'quantity' => 20, 'reason' => 'tidak_habis',
            'notes' => 'Sisa produksi kemarin sudah dipisahkan dan dibuang.', 'confirmed' => true];
    }

    private function lot(string $entity, int $prod): int
    {
        return (int) DB::table('production_lots')->where('entity', $entity)->where('production_id', $prod)->value('id');
    }

    public static function entities(): array { return [['hendhys'], ['jihans']]; }

    #[DataProvider('entities')]
    public function test_owner_removes_selected_lot_and_keeps_other_production_and_branch_stock(string $entity): void
    {
        $p = $this->product(); $prod = $this->produce($p, $entity, 'PRD-LAMA');
        $new = $this->produce($p, $entity, 'PRD-BARU', 30, '2026-10-12');
        $branch = Branch::create(['code' => 'WB', 'name' => 'Cabang', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        HendhysStockBranch::create(['branch_id' => $branch->id, 'product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 9]);
        $this->actingAs($owner = $this->owner());
        $before = $this->get(route('owner.production-writeoffs.index'))->assertOk()->assertInertia(fn (Assert $page) =>
            $page->component('Owner/ProductionWriteoffs/Index')->has('stocks.data', 2)->where('stocks.data.0.production_number', 'PRD-LAMA'));
        $data = $this->data($p, $entity, $this->lot($entity, $prod->id));
        $this->post(route('owner.production-writeoffs.store'), $data)->assertRedirect()->assertSessionHas('success');
        $table = $entity === 'hendhys' ? 'hendhys_stock_pusat' : 'jihans_gudang_stock';
        $this->assertDatabaseHas($table, ['product_id' => $p->id, 'quantity' => 30]);
        $this->assertDatabaseHas('production_lots', ['id' => $data['lot_id'], 'remaining' => 0]);
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot($entity, $new->id), 'remaining' => 30]);
        $this->assertDatabaseHas('hendhys_stock_branch', ['product_id' => $p->id, 'quantity' => 9]);
        $this->assertDatabaseHas('production_writeoffs', ['lot_id' => $data['lot_id'], 'quantity' => 20, 'stock_before' => 50,
            'stock_after' => 30, 'remaining_before' => 20, 'remaining_after' => 0, 'created_by' => $owner->id]);
        $this->assertDatabaseHas('production_lot_outflows', ['lot_id' => $data['lot_id'], 'source' => 'production_writeoff', 'quantity' => 20]);
        $this->assertDatabaseHas('master_activity_logs', ['module' => 'owner.production_writeoffs', 'user_id' => $owner->id]);
        $after = $this->get(route('owner.production-writeoffs.index'))->assertInertia(fn (Assert $page) => $page
            ->has('history.data', 1)->where('history.data.0.owner_name', 'Owner Uji')->where('history.data.0.remaining_after', 0));
        $detail = $this->get(route('owner.production.show', ['entity' => $entity, 'id' => $prod->id]))->assertOk()->assertInertia(fn (Assert $page) =>
            $page->where('batch_stock.0.written_off', 20)->where('batch_stock.0.distributed', 0)->where('batch_stock.0.other_out', 0)
                ->where('batch_stock.0.remaining', 0)->has('writeoffs.data', 1));
        if ($entity === 'jihans') {
            $id = DB::table('production_writeoffs')->value('movement_id');
            $this->get(route('owner.movements.show', $id))->assertInertia(fn (Assert $page) => $page
                ->where('document.title', 'Penghapusan sisa produksi')->where('movement.delta', -20));
        }
        if ($entity === 'hendhys' && ($dir = getenv('HEJIRA_WRITEOFF_PREVIEW'))) {
            file_put_contents($dir.'/before.html', $before->getContent());
            file_put_contents($dir.'/after.html', $after->getContent());
            file_put_contents($dir.'/detail.html', $detail->getContent());
        }
    }

    public function test_duplicate_partial_submission_is_idempotent_and_key_cannot_be_reused(): void
    {
        $p = $this->product(); $prod = $this->produce($p, 'jihans', 'J-1'); $this->actingAs($this->owner());
        $data = $this->data($p, 'jihans', $this->lot('jihans', $prod->id), ['quantity' => 7]);
        foreach ([1, 2] as $attempt) $this->post(route('owner.production-writeoffs.store'), $data)->assertSessionHas('success');
        $this->assertDatabaseCount('production_writeoffs', 1);
        $this->assertDatabaseHas('production_lots', ['id' => $data['lot_id'], 'remaining' => 13]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 13]);
        $data['quantity'] = 8;
        $this->post(route('owner.production-writeoffs.store'), $data)->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('production_writeoffs', 1);
    }

    public function test_stale_stock_overdraw_and_wrong_source_are_rejected_without_writes(): void
    {
        $p = $this->product(); $prod = $this->produce($p, 'hendhys', 'H-1');
        $j = $this->produce($p, 'jihans', 'J-1'); $other = $this->product(); $o = $this->produce($other, 'hendhys', 'H-OTHER');
        $this->actingAs($this->owner());
        $base = $this->data($p, 'hendhys', $this->lot('hendhys', $prod->id));
        foreach ([['quantity' => 21], ['expected_remaining' => 19], ['lot_id' => 99999], ['lot_id' => $this->lot('jihans', $j->id)],
            ['lot_id' => $this->lot('hendhys', $o->id)], ['lot_id' => null]] as $extra) {
            $this->post(route('owner.production-writeoffs.store'), $extra + $base)->assertSessionHasErrors('quantity');
        }
        $this->assertDatabaseCount('production_writeoffs', 0);
        $this->assertDatabaseCount('production_lot_outflows', 0);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 20]);
    }

    #[DataProvider('entities')]
    public function test_legacy_stock_is_separate_and_soft_deleted_product_remains_identifiable(string $entity): void
    {
        $p = $this->product(); $model = $entity === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
        $model::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20]); $p->delete();
        $this->actingAs($this->owner());
        $this->get(route('owner.production-writeoffs.index', ['entity' => $entity]))->assertInertia(fn (Assert $page) =>
            $page->has('stocks.data', 1)->where('stocks.data.0.production_number', null)->where('stocks.data.0.lot_id', null)->where('stocks.data.0.product_name', $p->name));
        $this->assertDatabaseCount('production_lots', 0);
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, $entity, null, ['quantity' => 5]))->assertSessionHas('success');
        $this->assertDatabaseHas('production_writeoffs', ['entity' => $entity, 'production_id' => null, 'quantity' => 5]);
        $this->assertDatabaseHas('production_lots', ['entity' => $entity, 'production_id' => null, 'remaining' => 15]);
    }

    public function test_unassigned_lot_removal_does_not_consume_known_production_or_purchased_inventory(): void
    {
        $p = $this->product(); HendhysStockPusat::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20]);
        $prod = $this->produce($p, 'hendhys', 'NEW'); $this->actingAs($this->owner());
        $unknown = (int) DB::table('production_lots')->whereNull('production_id')->value('id');
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $unknown))->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot('hendhys', $prod->id), 'remaining' => 20]);
        $purchased = $this->product(); $purchased->update(['source_type' => 'purchased']);
        JihansGudangStock::create(['product_id' => $purchased->id, 'unit_id' => $purchased->unit_id, 'quantity' => 20]);
        $this->post(route('owner.production-writeoffs.store'), $this->data($purchased, 'jihans', null))->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('production_writeoffs', 1);
    }

    public function test_alert_is_resolved_when_old_stock_is_removed_but_new_morning_stock_is_kept(): void
    {
        $p = $this->product(); $old = $this->produce($p, 'hendhys', 'OLD');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 08:00', 'Asia/Jakarta'));
        $this->produce($p, 'hendhys', 'TODAY', 30, '2026-10-12');
        $this->assertSame(1, app(ProductionStockMonitor::class)->overview()['overdue_count']);
        $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $this->lot('hendhys', $old->id)))->assertSessionHas('success');
        $this->assertNull(app(ProductionStockMonitor::class)->overview()['alert']);
        $this->assertNotNull(DB::table('production_stock_alerts')->value('resolved_at'));
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 30]);
    }

    public function test_ledger_mismatch_rolls_back_all_changes(): void
    {
        $p = $this->product(); $prod = $this->produce($p, 'hendhys', 'BAD');
        HendhysStockPusat::where('product_id', $p->id)->update(['quantity' => 21]);
        $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $this->lot('hendhys', $prod->id)))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('production_writeoffs', 0);
        $this->assertDatabaseHas('production_lots', ['production_id' => $prod->id, 'remaining' => 20]);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 21]);
    }

    public function test_only_owner_can_access_and_confirmation_and_reason_are_required(): void
    {
        $this->get(route('owner.production-writeoffs.index'))->assertRedirect(route('login'));
        foreach (['super_admin', 'admin_hendhys', 'admin_jihans', 'kasir_jihans'] as $role) {
            $this->actingAs($this->owner($role));
            $this->get(route('owner.production-writeoffs.index'))->assertForbidden();
            $this->post(route('owner.production-writeoffs.store'), [])->assertForbidden();
        }
        $p = $this->product(); $prod = $this->produce($p, 'jihans', 'J-1'); $this->actingAs($this->owner());
        $base = $this->data($p, 'jihans', $this->lot('jihans', $prod->id));
        foreach ([['confirmed' => false], ['reason' => 'invalid'], ['quantity' => 0], ['quantity' => 1.5], ['notes' => ''], ['notes' => '      '], ['notes' => ['invalid']], ['request_key' => 'invalid']] as $extra)
            $this->post(route('owner.production-writeoffs.store'), $extra + $base)->assertSessionHasErrors(array_key_first($extra));
        $this->assertDatabaseCount('production_writeoffs', 0);
    }

    public function test_future_production_cannot_be_removed_and_filters_work(): void
    {
        $p = $this->product(); $future = $this->produce($p, 'jihans', 'FUTURE', 20, '2026-10-13');
        $this->produce($p, 'hendhys', 'OLD'); $this->actingAs($this->owner());
        $this->get(route('owner.production-writeoffs.index', ['entity' => 'jihans']))->assertInertia(fn (Assert $page) => $page->has('stocks.data', 0));
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'jihans', $this->lot('jihans', $future->id)))->assertSessionHasErrors('quantity');
        $this->get(route('owner.production-writeoffs.index', ['entity' => 'hendhys', 'search' => 'OLD']))->assertInertia(fn (Assert $page) => $page->has('stocks.data', 1));
        $this->assertDatabaseCount('production_writeoffs', 0);
    }

    public function test_failure_after_movement_creation_rolls_back_physical_stock_and_audit_records(): void
    {
        $p = $this->product(); $prod = $this->produce($p, 'hendhys', 'ATOMIC');
        $owner = $this->owner(); $this->actingAs($owner);
        $this->mock(ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated audit failure'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $this->lot('hendhys', $prod->id)));
            $this->fail('Expected simulated failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated audit failure', $e->getMessage());
        }
        $this->assertDatabaseCount('production_writeoffs', 0);
        $this->assertDatabaseCount('production_lot_outflows', 0);
        $this->assertDatabaseCount('hendhys_stock_movements', 1);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 20]);
        $this->assertDatabaseHas('production_lots', ['production_id' => $prod->id, 'remaining' => 20]);
    }

    #[DataProvider('entities')]
    public function test_reversal_restores_exact_lot_after_other_stock_movements_and_preserves_history(string $entity): void
    {
        $p = $this->product(); $a = $this->produce($p, $entity, 'ORIGINAL');
        $b = $this->produce($p, $entity, 'OTHER', 30); $this->actingAs($owner = $this->owner());
        $lotId = $this->lot($entity, $a->id);
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, $entity, $lotId, ['quantity' => 7]))->assertSessionHas('success');
        $writeoff = DB::table('production_writeoffs')->first();
        if ($entity === 'hendhys') app(StockService::class)->debitHendhys($p->id, 5, null, 'transfer_to_branch', 90, $owner->id, $lotId);
        else app(StockService::class)->debitJihansGudang($p->id, 5, 'transfer_out', 90, $owner->id, null, $lotId);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12 20:00', 'Asia/Jakarta'));
        $payload = ['request_key' => (string) Str::uuid(), 'notes' => 'Salah input jumlah; barang masih ada di produksi.', 'confirmed' => true];
        $this->post(route('owner.production-writeoffs.reverse', $writeoff->id), $payload)->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['id' => $lotId, 'quantity' => 20, 'remaining' => 15]);
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot($entity, $b->id), 'remaining' => 30]);
        $table = $entity === 'hendhys' ? 'hendhys_stock_pusat' : 'jihans_gudang_stock';
        $this->assertDatabaseHas($table, ['product_id' => $p->id, 'quantity' => 45]);
        $this->assertDatabaseHas('production_writeoff_reversals', ['writeoff_id' => $writeoff->id, 'quantity' => 7,
            'remaining_before' => 8, 'remaining_after' => 15, 'stock_before' => 38, 'stock_after' => 45, 'created_by' => $owner->id]);
        $this->assertDatabaseHas('production_writeoffs', ['id' => $writeoff->id, 'remaining_before' => 20, 'remaining_after' => 13]);
        $this->assertDatabaseHas('production_lot_outflows', ['source' => 'production_writeoff', 'reference_id' => $writeoff->id, 'restored_quantity' => 7]);
        $summary = app(ProductionLots::class)->summary($a->load('details.product.unit'), $entity)[0];
        $this->assertSame(0, $summary['written_off']); $this->assertSame(7, $summary['writeoff_cancelled']);
        $this->assertSame(5, $summary['distributed']); $this->assertSame(0, $summary['other_out']);
        $this->get(route('owner.production-writeoffs.index'))->assertInertia(fn (Assert $page) => $page
            ->where('history.data.0.reversal_notes', $payload['notes'])->where('history.data.0.reversal_remaining_after', 15)
            ->where('history.data.0.reversed_at_label', '12/10/2026 20:00:00 WIB'));
        if ($entity === 'jihans') {
            $movement = DB::table('production_writeoff_reversals')->value('movement_id');
            $this->get(route('owner.movements.show', $movement))->assertInertia(fn (Assert $page) => $page
                ->where('movement.delta', 7)->where('document.status', 'writeoff_reversed')->where('document.fields.0.value', 'ORIGINAL'));
            $this->get(route('owner.movements.show', $writeoff->movement_id))->assertInertia(fn (Assert $page) => $page->where('document.status', 'writeoff_cancelled'));
        }
    }

    public function test_repeated_reversal_cannot_double_credit_and_new_writeoff_can_be_created_afterwards(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'jihans', 'J-UNDO'); $this->actingAs($this->owner());
        $data = $this->data($p, 'jihans', $this->lot('jihans', $a->id));
        $this->post(route('owner.production-writeoffs.store'), $data)->assertSessionHas('success');
        $id = DB::table('production_writeoffs')->value('id');
        $payload = ['request_key' => (string) Str::uuid(), 'notes' => 'Salah memilih produksi, barang belum dibuang.', 'confirmed' => true];
        foreach ([1, 2] as $attempt) $this->post(route('owner.production-writeoffs.reverse', $id), $payload)->assertSessionHas('success');
        $this->post(route('owner.production-writeoffs.reverse', $id), ['request_key' => (string) Str::uuid()] + $payload)->assertSessionHasErrors('notes');
        $this->post(route('owner.production-writeoffs.store'), $data)->assertSessionHas('success'); // Retry of original request never re-deletes.
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 20]);
        $this->assertDatabaseCount('production_writeoff_reversals', 1);
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'jihans', $data['lot_id'], ['quantity' => 2]))->assertSessionHas('success');
        $this->assertDatabaseCount('production_writeoffs', 2);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 18]);
    }

    #[DataProvider('entities')]
    public function test_legacy_reversal_restores_same_unknown_lot_and_deleted_product_not_new_production(string $entity): void
    {
        $p = $this->product(); $model = $entity === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
        $model::create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20]); $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, $entity, null))->assertSessionHas('success');
        $writeoff = DB::table('production_writeoffs')->first();
        $new = $this->produce($p, $entity, 'NEW', 30); $p->delete();
        $this->post(route('owner.production-writeoffs.reverse', $writeoff->id), ['request_key' => (string) Str::uuid(),
            'notes' => 'Salah input, stok lama masih ada.', 'confirmed' => true])->assertSessionHas('success');
        $this->assertDatabaseHas('production_lots', ['id' => $writeoff->lot_id, 'production_id' => null, 'remaining' => 20]);
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot($entity, $new->id), 'remaining' => 30]);
        $this->assertDatabaseCount('production_lots', 2);
        $this->assertSame(50, (int) $model::where('product_id', $p->id)->value('quantity'));
    }

    public function test_reversal_reactivates_overdue_stock_notification(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'hendhys', 'LATE'); $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $this->lot('hendhys', $a->id)))->assertSessionHas('success');
        $this->assertNull(app(ProductionStockMonitor::class)->overview()['alert']);
        $this->post(route('owner.production-writeoffs.reverse', DB::table('production_writeoffs')->value('id')), [
            'request_key' => (string) Str::uuid(), 'notes' => 'Salah input, stok masih tersedia.', 'confirmed' => true])->assertSessionHas('success');
        $this->assertSame(1, app(ProductionStockMonitor::class)->overview()['overdue_count']);
        $this->assertDatabaseHas('production_stock_alerts', ['product_id' => $p->id, 'resolved_at' => null, 'quantity' => 20]);
    }

    public function test_bulk_tracks_each_lot_and_unit_and_retry_does_not_delete_twice(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'hendhys', 'H-A'); $b = $this->produce($p, 'hendhys', 'H-B');
        $j = $this->produce($p, 'jihans', 'J-A'); $this->actingAs($this->owner());
        $items = array_map(fn ($pair) => ['entity' => $pair[0], 'product_id' => $p->id, 'lot_id' => $this->lot($pair[0], $pair[1]),
            'expected_remaining' => 20, 'quantity' => 5], [['hendhys', $a->id], ['hendhys', $b->id], ['jihans', $j->id]]);
        $payload = ['request_key' => (string) Str::uuid(), 'items' => $items, 'reason' => 'tidak_habis', 'notes' => 'Sisa dipisahkan dan dibuang sesuai pemeriksaan.', 'confirmed' => true];
        foreach ([1, 2] as $attempt) $this->post(route('owner.production-writeoffs.bulk'), $payload)->assertSessionHas('success');
        $this->assertDatabaseCount('production_writeoff_batches', 1); $this->assertDatabaseCount('production_writeoffs', 3);
        $this->assertSame(1, DB::table('production_writeoffs')->distinct()->count('batch_id'));
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 30]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 15]);
        foreach ($items as $item) $this->assertDatabaseHas('production_lots', ['id' => $item['lot_id'], 'remaining' => 15]);
        $payload['items'][0]['quantity'] = 6;
        $this->post(route('owner.production-writeoffs.bulk'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('production_writeoffs', 3);
    }

    public function test_bulk_failure_rolls_back_all_selected_stocks_documents_movements_and_logs(): void
    {
        $p = $this->product(); $h = $this->produce($p, 'hendhys', 'FIRST'); $j = $this->produce($p, 'jihans', 'STALE');
        $this->actingAs($this->owner());
        $items = [['entity' => 'hendhys', 'product_id' => $p->id, 'lot_id' => $this->lot('hendhys', $h->id), 'quantity' => 5, 'expected_remaining' => 20],
            ['entity' => 'jihans', 'product_id' => $p->id, 'lot_id' => $this->lot('jihans', $j->id), 'quantity' => 5, 'expected_remaining' => 19]];
        $this->post(route('owner.production-writeoffs.bulk'), ['request_key' => (string) Str::uuid(), 'items' => $items,
            'reason' => 'rusak', 'notes' => 'Rusak setelah pemeriksaan barang.', 'confirmed' => true])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('production_writeoffs', 0); $this->assertDatabaseCount('production_writeoff_batches', 0);
        $this->assertDatabaseCount('production_lot_outflows', 0); $this->assertDatabaseCount('master_activity_logs', 0);
        $this->assertDatabaseCount('hendhys_stock_movements', 1); $this->assertDatabaseCount('jihans_gudang_stock_movements', 1);
        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $p->id, 'quantity' => 20]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 20]);
    }

    public function test_bulk_duplicates_and_nonowner_access_are_rejected(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'jihans', 'J-1'); $this->actingAs($this->owner());
        $item = ['entity' => 'jihans', 'product_id' => $p->id, 'lot_id' => $this->lot('jihans', $a->id), 'quantity' => 2, 'expected_remaining' => 20];
        $payload = ['request_key' => (string) Str::uuid(), 'items' => [$item, $item], 'reason' => 'rusak', 'notes' => 'Barang rusak diperiksa.', 'confirmed' => true];
        $this->post(route('owner.production-writeoffs.bulk'), $payload)->assertSessionHasErrors('items');
        $this->post(route('owner.production-writeoffs.bulk'), ['items' => array_fill(0, 51, $item)] + $payload)->assertSessionHasErrors('items');
        $this->post(route('owner.production-writeoffs.bulk'), ['confirmed' => false] + $payload)->assertSessionHasErrors('confirmed');
        $this->post(route('owner.production-writeoffs.reverse', 999999), ['request_key' => (string) Str::uuid(), 'notes' => 'Tidak ada catatan ini.', 'confirmed' => true])->assertNotFound();
        foreach (['super_admin', 'admin_jihans', 'admin_hendhys'] as $role) {
            $this->actingAs($this->owner($role));
            $this->post(route('owner.production-writeoffs.bulk'), $payload)->assertForbidden();
            $this->post(route('owner.production-writeoffs.reverse', 1), [])->assertForbidden();
        }
        $this->assertDatabaseCount('production_writeoffs', 0);
    }

    public function test_reversal_validation_and_ledger_mismatch_leave_stock_untouched(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'hendhys', 'CHECK'); $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'hendhys', $this->lot('hendhys', $a->id)))->assertSessionHas('success');
        $id = DB::table('production_writeoffs')->value('id');
        $payload = ['request_key' => (string) Str::uuid(), 'notes' => 'Salah input dan barang masih ada.', 'confirmed' => true];
        foreach ([['confirmed' => false], ['notes' => '   '], ['request_key' => 'wrong']] as $extra)
            $this->post(route('owner.production-writeoffs.reverse', $id), $extra + $payload)->assertSessionHasErrors(array_key_first($extra));
        HendhysStockPusat::where('product_id', $p->id)->update(['quantity' => 1]);
        $this->post(route('owner.production-writeoffs.reverse', $id), $payload)->assertSessionHasErrors('notes');
        $this->assertDatabaseCount('production_writeoff_reversals', 0);
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot('hendhys', $a->id), 'remaining' => 0]);
        $this->assertDatabaseHas('production_lot_outflows', ['source' => 'production_writeoff', 'restored_quantity' => 0]);
    }

    public function test_reversal_failure_after_stock_update_rolls_back_everything(): void
    {
        $p = $this->product(); $a = $this->produce($p, 'jihans', 'ATOMIC-UNDO'); $this->actingAs($this->owner());
        $this->post(route('owner.production-writeoffs.store'), $this->data($p, 'jihans', $this->lot('jihans', $a->id)))->assertSessionHas('success');
        $this->mock(ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new \RuntimeException('Reversal audit failure'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('owner.production-writeoffs.reverse', DB::table('production_writeoffs')->value('id')), [
                'request_key' => (string) Str::uuid(), 'notes' => 'Salah memilih stok yang dihapus.', 'confirmed' => true]);
            $this->fail('Expected simulated failure');
        } catch (\RuntimeException $e) { $this->assertSame('Reversal audit failure', $e->getMessage()); }
        $this->assertDatabaseCount('production_writeoff_reversals', 0);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $p->id, 'quantity' => 0]);
        $this->assertDatabaseHas('production_lots', ['id' => $this->lot('jihans', $a->id), 'remaining' => 0]);
        $this->assertDatabaseHas('production_lot_outflows', ['source' => 'production_writeoff', 'restored_quantity' => 0]);
        $this->assertDatabaseCount('jihans_gudang_stock_movements', 2);
    }

    public function test_bulk_and_reversal_ui_preview_with_isolated_records(): void
    {
        $p = $this->product(); $h = $this->produce($p, 'hendhys', 'HND-2026-001'); $j = $this->produce($p, 'jihans', 'JHS-2026-001');
        $this->actingAs($this->owner());
        $before = $this->get(route('owner.production-writeoffs.index'))->assertOk();
        $items = [['entity' => 'hendhys', 'product_id' => $p->id, 'lot_id' => $this->lot('hendhys', $h->id), 'quantity' => 7, 'expected_remaining' => 20],
            ['entity' => 'jihans', 'product_id' => $p->id, 'lot_id' => $this->lot('jihans', $j->id), 'quantity' => 5, 'expected_remaining' => 20]];
        $this->post(route('owner.production-writeoffs.bulk'), ['request_key' => (string) Str::uuid(), 'items' => $items,
            'reason' => 'tidak_habis', 'notes' => 'Sisa produksi kemarin dipisahkan.', 'confirmed' => true])->assertSessionHas('success');
        $after = $this->get(route('owner.production-writeoffs.index'))->assertOk();
        $id = DB::table('production_writeoffs')->where('entity', 'hendhys')->value('id');
        $this->post(route('owner.production-writeoffs.reverse', $id), ['request_key' => (string) Str::uuid(),
            'notes' => 'Salah input; 7 brownies sebenarnya masih ada di produksi.', 'confirmed' => true])->assertSessionHas('success');
        $undo = $this->get(route('owner.production-writeoffs.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('history.data', 2));
        $detail = $this->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $h->id]))->assertOk();
        if ($dir = getenv('HEJIRA_WRITEOFF_UNDO_PREVIEW')) {
            foreach (['before' => $before, 'after' => $after, 'undo' => $undo, 'detail' => $detail] as $name => $response)
                file_put_contents($dir.'/'.$name.'.html', $response->getContent());
        }
    }
}
