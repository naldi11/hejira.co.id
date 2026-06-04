<?php

namespace Tests\Feature\Gudang;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TransferRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferRequestTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        return $user;
    }

    private function pendingRequest(float $requested = 10): TransferRequest
    {
        $category = ProductCategory::create(['name' => 'Bahan', 'entity_scope' => 'all']);
        $unit     = Unit::create(['name' => 'Pieces', 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $product  = Product::create([
            'code' => 'P-'.fake()->unique()->numberBetween(10000, 99999), 'name' => 'Tepung',
            'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'INV', 'entity_scope' => 'all', 'status' => 'active', 'stock_min' => 0,
        ]);

        $requester = User::factory()->create(['entity' => 'jihans']);

        $tr = TransferRequest::create([
            'request_number' => 'TR-'.fake()->unique()->numberBetween(1000, 9999),
            'from_entity'    => 'jihans',
            'date'           => now()->toDateString(),
            'status'         => 'pending',
            'requested_by'   => $requester->id,
        ]);

        $tr->details()->create([
            'product_id'         => $product->id,
            'quantity_requested' => $requested,
            'unit_id'            => $unit->id,
        ]);

        return $tr->load('details');
    }

    public function test_index_renders_with_status_counts(): void
    {
        $this->pendingRequest();

        $this->actingAs($this->adminGudang())
            ->get(route('gudang.transfer-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gudang/TransferRequests/Index')
                ->has('requests.data', 1)
                ->where('counts.pending', 1)
            );
    }

    public function test_approve_within_requested_quantity_succeeds(): void
    {
        $tr = $this->pendingRequest(10);
        $detail = $tr->details->first();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.transfer-requests.approve', $tr), [
                'items' => [['id' => $detail->id, 'quantity_approved' => 8]],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 8 < 10 requested → partial fulfilment.
        $this->assertSame('partial', $tr->fresh()->status);
        $this->assertEquals(8.0, (float) $detail->fresh()->quantity_approved);
    }

    public function test_approve_above_requested_quantity_is_rejected(): void
    {
        $tr = $this->pendingRequest(10);
        $detail = $tr->details->first();

        $this->actingAs($this->adminGudang())
            ->from(route('gudang.transfer-requests.show', $tr))
            ->post(route('gudang.transfer-requests.approve', $tr), [
                'items' => [['id' => $detail->id, 'quantity_approved' => 15]],
            ])
            ->assertSessionHasErrors('items');

        // Status must remain pending — the transaction rolled back.
        $this->assertSame('pending', $tr->fresh()->status);
        $this->assertNull($detail->fresh()->quantity_approved);
    }

    public function test_reject_requires_reason_and_sets_status(): void
    {
        $tr = $this->pendingRequest();

        $admin = $this->adminGudang();

        $this->actingAs($admin)
            ->from(route('gudang.transfer-requests.show', $tr))
            ->post(route('gudang.transfer-requests.reject', $tr), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($admin)
            ->post(route('gudang.transfer-requests.reject', $tr), ['rejection_reason' => 'Stok tidak tersedia'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('rejected', $tr->fresh()->status);
    }

    public function test_cannot_approve_already_processed_request(): void
    {
        $tr = $this->pendingRequest();
        $tr->update(['status' => 'approved']);
        $detail = $tr->details->first();

        $this->actingAs($this->adminGudang())
            ->post(route('gudang.transfer-requests.approve', $tr), [
                'items' => [['id' => $detail->id, 'quantity_approved' => 5]],
            ])
            ->assertForbidden();
    }
}
