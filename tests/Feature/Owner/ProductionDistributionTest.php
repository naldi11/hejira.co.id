<?php

namespace Tests\Feature\Owner;

use App\Models\{Branch, HendhysProduction, HendhysTransferToBranch, JihansProductionSession, Product, ProductCategory, TransferOut, Unit, User};
use Carbon\Carbon;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionDistributionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        Role::findOrCreate('owner', 'web');
        $user = User::factory()->create(['entity' => 'all']);
        $user->assignRole('owner');
        return $user;
    }

    private function product(string $entity = 'hendhys'): Product
    {
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::create(['name' => 'Produksi', 'entity_scope' => 'all']);
        return Product::create(['code' => fake()->unique()->numerify('P-#####'), 'name' => 'Brownies '.$entity,
            'unit_id' => $unit->id, 'category_id' => $category->id, 'product_type' => 'INV',
            'source_type' => 'produced', 'entity_scope' => $entity, 'status' => 'active']);
    }

    private function production(Product $product, User $owner, string $type = 'aktual')
    {
        $attrs = ['type' => $type, 'date' => '2026-08-03', 'created_by' => $owner->id];
        if ($product->entity_scope === 'jihans') {
            $production = JihansProductionSession::create($attrs + ['session_number' => 'JHS-1']);
            $production->details()->create(['product_id' => $product->id, 'quantity' => 1000]);
        } else {
            $production = HendhysProduction::create($attrs + ['production_number' => 'HND-1']);
            $production->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_produced' => 1000]);
        }
        if ($type === 'aktual') {
            $stock = app(StockService::class);
            if ($product->entity_scope === 'jihans') $stock->creditJihansGudang($product->id, $product->unit_id, 1000, 'production', $production->id, $owner->id);
            else $stock->creditHendhys($product->id, $product->unit_id, 1000, null, 'production', $production->id, $owner->id);
        }
        return $production;
    }

    private function shipment(Product $product, User $sender, string $number, string $status = 'sent', string $date = '2026-08-03')
    {
        $branch = Branch::create(['code' => $number, 'name' => 'Hendhys '.$number, 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $model = $product->entity_scope === 'jihans' ? TransferOut::class : HendhysTransferToBranch::class;
        $shipment = $model::create(['transfer_number' => $number, 'status' => $status, 'date' => $date, 'branch_id' => $branch->id,
            'created_by' => $sender->id, 'to_entity' => 'hendhys', 'notes' => 'Pengiriman pagi']);
        $shipment->forceFill(['created_at' => Carbon::parse($date.' 09:25:32', 'Asia/Jakarta'), 'updated_at' => Carbon::parse($date.' 16:00:00', 'Asia/Jakarta')])->save();
        $detail = $shipment->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 25,
            'received_quantity' => $status === 'received' ? 23 : null] + ($product->entity_scope === 'jihans' ? ['hpp_price' => 1000, 'total' => 25000] : []));
        if ($status !== 'cancelled' && DB::table('production_lots')->where('product_id', $product->id)->whereNotNull('production_id')->exists()) {
            $stock = app(StockService::class);
            if ($product->entity_scope === 'jihans') $stock->debitJihansGudang($product->id, 25, 'transfer_out', $shipment->id, $sender->id, null, null, $detail->id);
            else $stock->debitHendhys($product->id, 25, null, 'transfer_to_branch', $shipment->id, $sender->id, null, $detail->id);
        }
        return $shipment;
    }

    public function test_owner_sees_destination_quantity_dispatch_time_and_sender_without_unrelated_products(): void
    {
        $owner = $this->owner(); $product = $this->product(); $production = $this->production($product, $owner);
        $sender = User::factory()->create(['name' => 'Pengirim Produksi', 'entity' => 'hendhys']);
        $shipment = $this->shipment($product, $sender, 'TRF-01', 'received');
        $other = $this->product();
        $shipment->details()->create(['product_id' => $other->id, 'unit_id' => $other->unit_id, 'quantity' => 99]);
        $this->shipment($other, $sender, 'UNRELATED');
        $this->shipment($product, $sender, 'CANCELLED', 'cancelled');
        $this->shipment($product, $sender, 'TOMORROW', 'sent', '2026-08-04');
        $secondSender = User::factory()->create(['name' => 'Pengirim Kedua', 'entity' => 'hendhys']);
        $this->shipment($product, $secondSender, 'TRF-02');

        $this->actingAs($owner)->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $production->id, 'distribution_date' => '2026-08-03']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distribution_date', '2026-08-03')->where('distributions.total', 2)
                ->where('distributions.data.0.destination', 'Hendhys TRF-02')->where('distributions.data.0.sender', 'Pengirim Kedua')
                ->where('distributions.data.1.destination_code', 'TRF-01')->where('distributions.data.1.sender', 'Pengirim Produksi')
                ->where('distributions.data.1.sent_at', '03/08/2026 09:25:32 WIB')->where('distributions.data.1.status', 'received')
                ->has('distributions.data.1.items', 1)->where('distributions.data.1.items.0.quantity', 25)
                ->where('distributions.data.1.items.0.received_quantity', 23)->where('distributions.data.1.items.0.unit', 'PCS'));
    }

    public function test_owner_can_choose_later_day_and_invalid_date_is_rejected(): void
    {
        $owner = $this->owner(); $product = $this->product(); $production = $this->production($product, $owner);
        $this->shipment($product, $owner, 'LATE', 'sent', '2026-08-04');
        $params = ['entity' => 'hendhys', 'id' => $production->id, 'distribution_date' => '2026-08-04'];
        $this->actingAs($owner)->get(route('owner.production.show', $params))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distribution_date', '2026-08-04')->where('distributions.total', 1)
                ->where('distributions.data.0.number', 'LATE')->where('distributions.data.0.sent_at', '04/08/2026 09:25:32 WIB'));
        $params['distribution_date'] = 'invalid';
        $this->get(route('owner.production.show', $params))->assertSessionHasErrors('distribution_date');
    }

    public function test_predictions_do_not_claim_existing_shipments_as_their_distribution(): void
    {
        $owner = $this->owner(); $product = $this->product(); $production = $this->production($product, $owner, 'prediksi');
        $this->shipment($product, $owner, 'EXISTING');
        $this->actingAs($owner)->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $production->id]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('distributions', null));
    }

    public function test_jihans_reads_only_warehouse_shipments_and_preserves_missing_received_quantity(): void
    {
        $owner = $this->owner(); $product = $this->product('jihans'); $production = $this->production($product, $owner);
        // Jihan’s memakai alokasi gudang yang terpisah dari pusat Hendhys.
        $shipment = $this->shipment($product, $owner, 'GDG-1', 'received');
        $shipment->details()->update(['received_quantity' => null]);
        $h = HendhysTransferToBranch::create(['transfer_number' => 'HND-OTHER', 'date' => '2026-08-03', 'status' => 'sent', 'branch_id' => $shipment->branch_id, 'created_by' => $owner->id]);
        $h->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 99]);
        $product->delete();
        $this->actingAs($owner)->get(route('owner.production.show', ['entity' => 'jihans', 'id' => $production->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distributions.total', 1)->where('distributions.data.0.number', 'GDG-1')
                ->where('distributions.data.0.items.0.product', $product->name)->where('distributions.data.0.items.0.received_quantity', null));
    }

    public function test_distributions_paginate_and_partial_refresh_includes_new_shipments(): void
    {
        $owner = $this->owner(); $product = $this->product(); $production = $this->production($product, $owner);
        for ($i = 1; $i <= 16; $i++) $this->shipment($product, $owner, 'TRF-'.$i);
        $params = ['entity' => 'hendhys', 'id' => $production->id, 'distribution_page' => 2, 'distribution_date' => '2026-08-03'];
        $this->actingAs($owner)->get(route('owner.production.show', $params))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distributions.total', 16)->where('distributions.current_page', 2)->has('distributions.data', 1));
        $this->shipment($product, $owner, 'NEW');
        $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
            'X-Inertia-Partial-Component' => 'Owner/Production/Show', 'X-Inertia-Partial-Data' => 'distributions'])
            ->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $production->id]))->assertOk()
            ->assertJsonPath('props.distributions.total', 17)->assertJsonPath('props.distributions.data.0.number', 'NEW');
    }
    public function test_same_product_shipment_allocations_and_stock_are_separated_by_production_number(): void
    {
        $owner = $this->owner(); $product = $this->product(); $first = $this->production($product, $owner);
        $second = HendhysProduction::create(['production_number' => 'HND-SECOND', 'date' => '2026-08-03', 'type' => 'aktual', 'created_by' => $owner->id]);
        $second->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_produced' => 20]);
        $stock = app(StockService::class);
        $stock->creditHendhys($product->id, $product->unit_id, 20, null, 'production', $second->id, $owner->id);
        $stock->debitHendhys($product->id, 990, null, 'pos_sale', 80, $owner->id);
        $this->shipment($product, $owner, 'MIXED', 'received', '2026-08-04');
        $this->actingAs($owner)->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $first->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distribution_date', null)->where('distributions.total', 1)
                ->where('distributions.data.0.items.0.quantity', 10)->where('distributions.data.0.items.0.document_quantity', 25)
                ->where('distributions.data.0.items.0.received_quantity', null)->where('batch_stock.0.remaining', 0));
        $this->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $second->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distributions.data.0.items.0.quantity', 15)
                ->where('batch_stock.0.remaining', 5)->where('batch_stock.0.distributed', 15));
    }

    public function test_old_production_with_no_lot_does_not_claim_matching_historical_shipments(): void
    {
        $owner = $this->owner(); $product = $this->product();
        $old = HendhysProduction::create(['production_number' => 'OLD', 'date' => '2026-08-03', 'type' => 'aktual', 'created_by' => $owner->id]);
        $old->details()->create(['product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity_produced' => 100]);
        $this->shipment($product, $owner, 'HISTORICAL');
        $this->actingAs($owner)->get(route('owner.production.show', ['entity' => 'hendhys', 'id' => $old->id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('distributions.total', 0)->where('batch_stock', []));
    }

}
