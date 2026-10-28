<?php

namespace Tests\Feature\Inventory;

use App\Models\{Branch, Product, ProductCategory, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessStockAccessTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_global_super_admin_can_record_finished_production_in_both_production_units(): void
    {
        $unit = Unit::firstOrCreate(['name' => 'Pieces'], ['abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $category = ProductCategory::firstOrCreate(['name' => 'Production access'], ['entity_scope' => 'all']);
        $product = Product::create(['code' => 'PROD-ACCESS', 'name' => 'Hasil produksi uji',
            'category_id' => $category->id, 'unit_id' => $unit->id, 'entity_scope' => 'all',
            'source_type' => 'produced', 'product_type' => 'INV', 'status' => 'active']);
        $this->actingAs($this->actor('super_admin'));

        foreach (['hendhys', 'jihans'] as $entity) {
            $route = $entity === 'hendhys' ? 'hendhys.productions' : 'jihans.production';
            $this->get(route($route.'.index'))->assertOk();
            $this->get(route($route.'.create'))->assertOk();
            $data = ['request_key' => (string) Str::uuid(), 'date' => today()->toDateString()];
            $data += $entity === 'hendhys'
                ? ['items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity_produced' => 10]]]
                : ['details' => [['product_id' => $product->id, 'quantity' => 10]]];

            foreach ([1, 2] as $_) {
                $this->post(route($route.'.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
            }
        }

        $this->assertDatabaseHas('hendhys_stock_pusat', ['product_id' => $product->id, 'quantity' => 10]);
        $this->assertDatabaseHas('jihans_gudang_stock', ['product_id' => $product->id, 'quantity' => 10]);
        $this->assertDatabaseCount('hendhys_productions', 1);
        $this->assertDatabaseCount('jihans_production_sessions', 1);
    }

    public function test_transfer_access_covers_all_units_and_keeps_operators_scoped_to_their_branch(): void
    {
        $locations = collect(['gudang', 'hendhys', 'jihans', 'izzi'])->mapWithKeys(function ($entity) {
            $branch = $entity === 'gudang' ? Branch::where('entity', 'gudang')->orderBy('id')->first() : null;
            $branch ??= Branch::create(['code' => 'ACCESS-'.strtoupper($entity), 'name' => 'Lokasi '.$entity,
                'entity' => $entity, 'type' => 'cabang', 'is_active' => true]);

            return [$entity => $branch];
        });

        foreach ($locations as $entity => $branch) {
            $roles = $entity === 'gudang' ? ['super_admin'] : ['admin_'.$entity, 'kasir_'.$entity];
            foreach ($roles as $role) {
                $this->actingAs($this->actor($role, $entity, $branch))
                    ->withSession(['active_branch_id' => $branch->id]);
                $this->get(route('branch-transfers.index'))->assertOk();
                $this->get(route('branch-transfers.create'))->assertOk()
                    ->assertInertia(fn (Assert $page) => $page
                        ->component('BranchTransfers/Create')
                        ->has('sources', 1)
                        ->where('sources.0.id', $branch->id)
                        ->has('destinations', 3));

                $other = $locations->first(fn ($candidate) => $candidate->id !== $branch->id);
                $this->get(route('branch-transfers.create', ['from_branch_id' => $other->id]))->assertForbidden();
            }
        }

        $this->actingAs($this->actor('super_admin'))->withSession(['active_branch_id' => null]);
        $this->get(route('branch-transfers.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('sources', 4)->has('destinations', 3));
    }

    public function test_super_admin_with_a_branch_assignment_can_open_centre_production_while_branch_admin_cannot(): void
    {
        $branch = Branch::create(['code' => 'PROD-MENU', 'name' => 'Cabang Hendhys', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => true]);
        $this->actingAs($this->actor('super_admin', 'all', $branch))->withSession(['active_branch_id' => $branch->id]);
        $this->get(route('hendhys.productions.index'))->assertOk();
        $this->get(route('hendhys.productions.create'))->assertOk();
        $this->actingAs($this->actor('admin_hendhys', 'hendhys', $branch));
        $this->get(route('hendhys.productions.create'))->assertForbidden();
    }
}
