<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\HendhysPendingTransaction;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransaction;
use App\Models\JihansPendingTransaction;
use App\Models\JihansRetailStock;
use App\Models\JihansTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function entities(): array
    {
        return [['hendhys'], ['jihans']];
    }

    private function setupSale(string $entity): array
    {
        Role::findOrCreate("kasir_{$entity}", 'web');
        $branch = $entity === 'hendhys'
            ? Branch::firstOrCreate(['code' => 'SEC-P'], ['name' => 'Pusat', 'type' => 'pusat', 'entity' => 'hendhys', 'is_active' => true])
            : null;
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole("kasir_{$entity}");
        CashierShift::create(['user_id' => $user->id, 'branch_id' => $branch?->id, 'entity' => $entity, 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        $category = ProductCategory::create(['name' => 'Security Test', 'entity_scope' => 'all']);
        $unit = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product = Product::create([
            'code' => 'SEC-'.Str::random(8), 'name' => 'Test Item', 'selling_price' => 25000,
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);
        $stock = $entity === 'hendhys' ? HendhysStockPusat::class : JihansRetailStock::class;
        $stock::create(['product_id' => $product->id, 'quantity' => 1, 'unit_id' => $unit->id]);
        $payload = [
            'checkout_key' => (string) Str::uuid(), 'amount_paid' => 25000,
            'customer_name' => 'Test Customer', 'customer_type' => 'Pelanggan Individual',
            'ppn_type' => 'none', 'ppn_rate' => 0, 'subtotal' => 25000, 'discount_amount' => 0,
            'tax_amount' => 0, 'other_costs' => 0, 'grand_total' => 25000,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 25000, 'total' => 25000]],
        ];
        $this->actingAs($user);

        return [$user, $product, $payload, $stock];
    }

    #[DataProvider('entities')]
    public function test_underpayment_is_rejected_without_sale_or_stock_change(string $entity): void
    {
        [, $product, $payload, $stock] = $this->setupSale($entity);
        foreach ([0, 24999] as $paid) {
            $this->postJson(route("{$entity}.pos.store"), [...$payload, 'amount_paid' => $paid])
                ->assertUnprocessable()->assertJsonValidationErrors('amount_paid');
        }
        $this->assertDatabaseCount("{$entity}_transactions", 0);
        $this->assertEquals(1, $stock::where('product_id', $product->id)->value('quantity'));
    }

    #[DataProvider('entities')]
    public function test_checkout_requires_valid_request_key(string $entity): void
    {
        [, , $payload] = $this->setupSale($entity);
        foreach ([null, 'invalid-key'] as $key) {
            $this->postJson(route("{$entity}.pos.store"), [...$payload, 'checkout_key' => $key])
                ->assertUnprocessable()->assertJsonValidationErrors('checkout_key');
        }
        $this->assertDatabaseCount("{$entity}_transactions", 0);
    }

    #[DataProvider('entities')]
    public function test_retry_replays_success_after_pending_deleted_and_stock_exhausted(string $entity): void
    {
        [$user, $product, $payload, $stock] = $this->setupSale($entity);
        $pendingClass = $entity === 'hendhys' ? HendhysPendingTransaction::class : JihansPendingTransaction::class;
        $pending = $pendingClass::create([
            'pending_number' => 'SEC-PENDING', 'date' => today(), 'customer_name' => 'Test Customer',
            'customer_type' => 'Pelanggan Individual', 'created_by' => $user->id,
        ]);
        $payload['pending_id'] = $pending->id;
        $first = $this->postJson(route("{$entity}.pos.store"), $payload)->assertOk()->json();
        $this->assertDatabaseMissing("{$entity}_pending_transactions", ['id' => $pending->id]);
        $this->postJson(route("{$entity}.pos.store"), $payload)->assertOk()->assertExactJson($first);
        $this->assertDatabaseCount("{$entity}_transactions", 1);
        $this->assertDatabaseCount("{$entity}_transaction_payments", 1);
        $this->assertDatabaseCount('master_activity_logs', 1);
        $this->assertEquals(0, $stock::where('product_id', $product->id)->value('quantity'));
    }

    #[DataProvider('entities')]
    public function test_same_key_cannot_be_reused_for_changed_payload(string $entity): void
    {
        [, $product, $payload, $stock] = $this->setupSale($entity);
        $this->postJson(route("{$entity}.pos.store"), $payload)->assertOk();
        $this->postJson(route("{$entity}.pos.store"), [...$payload, 'customer_name' => 'Different Customer'])
            ->assertUnprocessable()->assertJsonValidationErrors('checkout_key');
        $this->assertDatabaseCount("{$entity}_transactions", 1);
        $this->assertEquals(0, $stock::where('product_id', $product->id)->value('quantity'));
    }

    #[DataProvider('entities')]
    public function test_failed_audit_log_rolls_back_sale_payment_stock_and_pending(string $entity): void
    {
        [$user, $product, $payload, $stock] = $this->setupSale($entity);
        $pendingClass = $entity === 'hendhys' ? HendhysPendingTransaction::class : JihansPendingTransaction::class;
        $pending = $pendingClass::create([
            'pending_number' => 'SEC-FAIL', 'date' => today(), 'created_by' => $user->id,
            'customer_name' => 'Test', 'customer_type' => 'Pelanggan Individual',
        ]);
        $payload['pending_id'] = $pending->id;
        $this->mock(ActivityLogService::class, fn ($mock) => $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated log outage')));
        $this->postJson(route("{$entity}.pos.store"), $payload)->assertStatus(500);
        $this->assertDatabaseCount("{$entity}_transactions", 0);
        $this->assertDatabaseCount("{$entity}_transaction_payments", 0);
        $this->assertDatabaseHas("{$entity}_pending_transactions", ['id' => $pending->id]);
        $this->assertEquals(1, $stock::where('product_id', $product->id)->value('quantity'));

        // The same request may safely succeed once the log service recovers.
        $this->app->forgetInstance(ActivityLogService::class);
        $this->app['router']->getRoutes()->getByName("{$entity}.pos.store")->flushController();
        $this->postJson(route("{$entity}.pos.store"), $payload)->assertOk();
        $this->assertDatabaseCount("{$entity}_transactions", 1);
        $this->assertDatabaseCount('master_activity_logs', 1);
    }

    public function test_hendhys_checkout_cannot_consume_pending_from_other_branch(): void
    {
        [$user, $product, $payload, $stock] = $this->setupSale('hendhys');
        $branch = Branch::create(['code' => 'SEC-OTHER', 'name' => 'Other', 'type' => 'cabang', 'entity' => 'hendhys']);
        $pending = HendhysPendingTransaction::create([
            'pending_number' => 'SEC-OTHER-PENDING', 'branch_id' => $branch->id, 'date' => today(),
            'customer_name' => 'Other', 'customer_type' => 'Pelanggan Individual', 'created_by' => $user->id,
        ]);
        $this->postJson(route('hendhys.pos.store'), [...$payload, 'pending_id' => $pending->id])->assertForbidden();
        $this->assertDatabaseHas('hendhys_pending_transactions', ['id' => $pending->id]);
        $this->assertDatabaseCount('hendhys_transactions', 0);
        $this->assertEquals(1, $stock::where('product_id', $product->id)->value('quantity'));
    }

    public function test_jihans_edit_cannot_mark_underpaid_transaction_as_paid(): void
    {
        [, $product, $payload, $stock] = $this->setupSale('jihans');
        $this->postJson(route('jihans.pos.store'), $payload)->assertOk();
        $transaction = JihansTransaction::first();
        $this->putJson(route('jihans.pos.update', $transaction), [...$payload, 'amount_paid' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('amount_paid');
        $this->assertEquals(25000, $transaction->payments()->sum('amount'));
        $this->assertEquals(0, $stock::where('product_id', $product->id)->value('quantity'));
    }
}
