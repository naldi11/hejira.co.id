<?php

namespace Tests\Feature\Jihans;

use App\Models\JihansTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansFakturTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        Role::findOrCreate('kasir_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('kasir_jihans');

        return $user;
    }

    private function transaction(User $user): JihansTransaction
    {
        $category = ProductCategory::create(['name' => 'Frozen', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Tortilla',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0, 'selling_price' => 15000,
        ]);

        $trx = JihansTransaction::create([
            'transaction_number' => 'JHS-INV-0001', 'date' => today(), 'time' => now()->toTimeString(),
            'customer_name' => 'Umum', 'customer_type' => 'Pelanggan Retail',
            'ppn_type' => 'none', 'ppn_rate' => 0, 'subtotal' => 30000, 'discount_amount' => 0,
            'tax_amount' => 0, 'other_costs' => 0, 'grand_total' => 30000, 'status' => 'paid', 'created_by' => $user->id,
        ]);
        $trx->details()->create([
            'product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 2,
            'unit_id' => $unit->id, 'price' => 15000, 'discount_amount' => 0, 'total' => 30000,
        ]);
        $trx->payments()->create(['payment_method' => 'cash', 'amount' => 30000]);

        return $trx;
    }

    public function test_faktur_preview_shows_actions_and_does_not_autoprint(): void
    {
        $user = $this->kasir();
        $trx = $this->transaction($user);

        $res = $this->actingAs($user)->get(route('jihans.transactions.show', $trx->id))->assertOk();
        $res->assertSee('Cetak Faktur');
        $res->assertSee('Unduh PDF');
        // Preview-first: no auto window.print on load.
        $res->assertDontSee('setTimeout(function() { window.print(); }', false);
    }

    public function test_faktur_pdf_route_returns_pdf(): void
    {
        $user = $this->kasir();
        $trx = $this->transaction($user);

        $res = $this->actingAs($user)->get(route('jihans.transactions.pdf', $trx->id))->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));
    }
}
