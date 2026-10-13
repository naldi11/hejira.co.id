<?php

namespace Tests\Feature\Owner;

use App\Models\{Branch, GudangReturn, JihansGudangStockMovement, JihansProductionSession, Product, ProductCategory, PurchaseOrder, Receiving, Supplier, TransferOut, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'owner'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['name' => $role === 'owner' ? 'Owner' : 'Petugas Gudang', 'entity' => 'all']);
        $user->assignRole($role);
        return $user;
    }

    private function product(string $name = 'Mayo Mc. Lewis'): Product
    {
        $unit = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Bahan'], ['entity_scope' => 'all']);
        return Product::create(['code' => 'AUD-'.fake()->unique()->numberBetween(1, 999999), 'name' => $name,
            'category_id' => $category->id, 'unit_id' => $unit->id, 'source_type' => 'purchased',
            'entity_scope' => 'all', 'product_type' => 'INV', 'status' => 'active']);
    }

    private function movement(Product $product, string $source, ?int $ref, User $actor, array $extra = []): JihansGudangStockMovement
    {
        return JihansGudangStockMovement::create($extra + ['product_id' => $product->id, 'source' => $source, 'reference_id' => $ref,
            'type' => 'in', 'quantity' => 20, 'quantity_before' => 30, 'quantity_after' => 50, 'created_by' => $actor->id,
            'created_at' => now(), 'notes' => 'Penerimaan retur (Bagus) dari Hendhys']);
    }

    private function show(JihansGudangStockMovement $movement)
    {
        return $this->get(route('owner.movements.show', $movement));
    }

    public function test_owner_opens_return_with_all_items_and_balance(): void
    {
        $actor = $this->user('super_admin'); $owner = $this->user(); $product = $this->product();
        $branch = Branch::create(['name' => 'Hendhys Beringin', 'code' => 'HB-AUD', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $return = GudangReturn::create(['return_number' => 'RET-HND-2026-001', 'from_entity' => 'hendhys', 'branch_id' => $branch->id,
            'date' => today(), 'status' => 'received', 'created_by' => $actor->id, 'received_by' => $actor->id, 'received_at' => now()]);
        foreach ([[$product, 20], [$this->product('Pizza Salami'), 1]] as [$p, $qty]) {
            $return->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => $qty, 'received_quantity' => $qty, 'condition' => 'Bagus (Siap Jual)']);
        }
        $movement = $this->movement($product, 'return_receiving', $return->id, $actor);
        $this->actingAs($owner);
        $list = $this->get(route('owner.dashboard.detail', ['mode' => 'stock', 'unit' => 'movements']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('list.0.id', $movement->id));
        $response = $this->show($movement)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Owner/StockMovement')
            ->where('movement.before', 30)->where('movement.after', 50)->where('movement.delta', 20)
            ->where('document.number', $return->return_number)->has('document.items', 2)
            ->where('document.items.0.received', 20)->where('document.items.0.related', true)->where('document.items.1.related', false));
        // Optional isolated UI fixture: no writes to the operational database.
        if ($directory = getenv('HEJIRA_MOVEMENT_PREVIEW')) {
            file_put_contents($directory.'/list.html', $list->getContent());
            file_put_contents($directory.'/detail.html', $response->getContent());
        }
    }

    public function test_damaged_return_preserves_recorded_quantity_without_claiming_sellable_stock_increase(): void
    {
        $actor = $this->user(); $p = $this->product();
        $return = GudangReturn::create(['return_number' => 'RET-DAMAGE', 'from_entity' => 'hendhys', 'date' => today(), 'status' => 'received', 'created_by' => $actor->id]);
        $return->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20, 'received_quantity' => 20, 'condition' => 'Rusak (Defect)']);
        $m = $this->movement($p, 'return_receive', $return->id, $actor, ['quantity_after' => 30]); $p->delete();
        $this->actingAs($actor); $this->show($m)->assertInertia(fn (Assert $page) => $page->where('movement.delta', 0)
            ->where('movement.quantity', 20)->where('movement.product', $p->name)->where('document.items.0.condition', 'Rusak (Defect)')
            ->where('document.items.0.product', $p->name));
    }

    public static function receiptSources(): array
    {
        return [['purchase_receiving'], ['receiving'], ['receiving_edit']];
    }

    #[DataProvider('receiptSources')]
    public function test_receipt_and_correction_link_to_grn_and_po(string $source): void
    {
        $actor = $this->user(); $p = $this->product(); $supplier = Supplier::create(['code' => 'S-AUD', 'name' => 'Supplier', 'is_active' => true]);
        $po = PurchaseOrder::create(['po_number' => 'PO-AUD', 'supplier_id' => $supplier->id, 'date' => today(), 'status' => 'received', 'created_by' => $actor->id]);
        $grn = Receiving::create(['grn_number' => 'GRN-AUD', 'po_id' => $po->id, 'supplier_id' => $supplier->id, 'date' => today(), 'status' => 'open', 'created_by' => $actor->id]);
        $grn->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20, 'kondisi' => 'baik', 'hpp_price' => 5000, 'total' => 100000]);
        $m = $this->movement($p, $source, $grn->id, $actor);
        $this->actingAs($actor); $this->show($m)->assertInertia(fn (Assert $page) => $page->where('document.number', 'GRN-AUD')
            ->where('document.items.0.total', 100000)->where('document.links.0.href', route('owner.po.show', $po)));
    }

    public function test_transfer_and_shortfall_use_transfer_not_return_with_same_id(): void
    {
        $actor = $this->user(); $p = $this->product();
        $transfer = TransferOut::create(['transfer_number' => 'TRF-AUD', 'to_entity' => 'jihans', 'date' => today(), 'status' => 'received', 'created_by' => $actor->id]);
        $transfer->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20, 'received_quantity' => 18, 'hpp_price' => 1, 'total' => 20]);
        $return = GudangReturn::create(['return_number' => 'RET-COLLISION', 'from_entity' => 'jihans', 'date' => today(), 'status' => 'received', 'created_by' => $actor->id]);
        $return->details()->create(['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity' => 20, 'condition' => 'Bagus (Siap Jual)']);
        $this->assertSame($transfer->id, $return->id);
        $this->actingAs($actor);
        foreach ([['transfer_out', 'Pengiriman Gudang'], ['return_receiving', 'Selisih penerimaan dari TRF-AUD']] as [$source, $notes]) {
            $m = $this->movement($p, $source, $transfer->id, $actor, ['notes' => $notes]);
            $this->show($m)->assertInertia(fn (Assert $page) => $page->where('document.number', 'TRF-AUD')->where('document.items.0.received', 18));
        }
        $m = $this->movement($p, 'return_receiving', $transfer->id, $actor, ['notes' => 'Selisih penerimaan dari NOMOR-SALAH']);
        $this->show($m)->assertInertia(fn (Assert $page) => $page->where('document', null));
    }

    public function test_production_links_to_existing_owner_production_page(): void
    {
        $actor = $this->user(); $p = $this->product();
        $prod = JihansProductionSession::create(['session_number' => 'JHS-AUD', 'date' => today(), 'type' => 'aktual', 'created_by' => $actor->id]);
        $prod->details()->create(['product_id' => $p->id, 'quantity' => 20]);
        $m = $this->movement($p, 'production', $prod->id, $actor);
        $this->actingAs($actor); $this->show($m)->assertInertia(fn (Assert $page) => $page->where('document.number', 'JHS-AUD')
            ->where('document.items.0.quantity', 20)->where('document.links.0.href', route('owner.production.show', ['entity' => 'jihans', 'id' => $prod->id])));
    }

    public function test_missing_unrelated_and_manual_documents_leave_movement_visible(): void
    {
        $actor = $this->user(); $p = $this->product(); $other = $this->product('Barang lain');
        $return = GudangReturn::create(['return_number' => 'RET-OTHER', 'from_entity' => 'jihans', 'date' => today(), 'status' => 'sent', 'created_by' => $actor->id]);
        $return->details()->create(['product_id' => $other->id, 'unit_id' => $other->unit_id, 'quantity' => 20, 'condition' => 'Bagus (Siap Jual)']);
        $this->actingAs($actor);
        foreach ([['return_receiving', 99999], ['return_receiving', $return->id], ['adjustment', null], ['source-unknown', 1]] as [$source, $ref]) {
            $m = $this->movement($p, $source, $ref, $actor);
            $this->show($m)->assertOk()->assertInertia(fn (Assert $page) => $page->where('movement.id', $m->id)->where('document', null)
                ->where('document_message', fn ($message) => is_string($message) && strlen($message) > 0));
        }
    }

    public function test_owner_only_and_invalid_ids(): void
    {
        $actor = $this->user(); $m = $this->movement($this->product(), 'adjustment', null, $actor);
        $this->show($m)->assertRedirect(route('login'));
        $this->actingAs($this->user('super_admin')); $this->show($m)->assertForbidden();
        $this->actingAs($actor); $this->get('/owner/stock-movements/99999')->assertNotFound();
        $this->get('/owner/stock-movements/not-a-number')->assertNotFound();
    }
}
