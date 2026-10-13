<?php

namespace Tests\Feature\Master;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['owner', 'super_admin', 'kasir_hendhys', 'admin_hendhys', 'kasir_jihans', 'admin_jihans'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $admin = User::factory()->create(['entity' => 'all']);
        $admin->assignRole('super_admin');

        return $admin;
    }

    private function user(array $attributes, string $role): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    public function test_list_uses_shared_labels_and_exposes_assigned_inactive_branch_details(): void
    {
        $admin = $this->admin();
        $this->user(['entity' => 'owner'], 'owner');
        $branch = Branch::create(['code' => 'HB-OLD', 'name' => 'Cabang Lama', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => false]);
        $unused = Branch::create(['code' => 'HB-UNUSED', 'name' => 'Tidak Digunakan', 'entity' => 'hendhys', 'type' => 'cabang', 'is_active' => false]);
        $target = $this->user(['entity' => 'hendhys', 'branch_id' => $branch->id], 'kasir_hendhys');

        $this->actingAs($admin)->get(route('master.users.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Users/Index')
                ->where('role_descriptions.kasir_hendhys.label', config('user_access.roles.kasir_hendhys.label'))
                ->where('role_descriptions.owner.label', 'Pemilik (Owner)')
                ->where('unit_options.3.label', 'Semua unit usaha')
                ->where('unit_options.4.label', 'Pemantauan seluruh unit')
                ->where('users', fn ($users) => collect($users)->firstWhere('id', $target->id)['branch_details'] === [
                    'name' => 'Cabang Lama', 'code' => 'HB-OLD', 'type' => 'cabang', 'is_active' => false,
                ])
                ->where('branchOptions', fn ($branches) => collect($branches)->contains('id', $branch->id) && ! collect($branches)->contains('id', $unused->id)));

        $this->get(route('master.users.index', ['branch_id' => $branch->id]))
            ->assertInertia(fn (Assert $page) => $page->has('users', 1)->where('users.0.id', $target->id));
    }

    public function test_unit_filter_combines_with_role_and_status(): void
    {
        $admin = $this->admin();
        $target = $this->user(['entity' => 'hendhys', 'is_active' => true], 'kasir_hendhys');
        $this->user(['entity' => 'hendhys', 'is_active' => false], 'kasir_hendhys');
        $this->user(['entity' => 'hendhys'], 'admin_hendhys');
        $this->user(['entity' => 'jihans'], 'kasir_jihans');

        $this->actingAs($admin)->get(route('master.users.index', ['entity' => 'hendhys', 'role' => 'kasir_hendhys', 'status' => 'aktif']))
            ->assertInertia(fn (Assert $page) => $page->has('users', 1)
                ->where('users.0.id', $target->id)
                ->has('unit_options', 4)
                ->where('filters.entity', 'hendhys')
                ->where('totalUsers', 5));
    }

    public function test_no_branch_filter_distinguishes_unassigned_business_accounts_from_all_unit_accounts(): void
    {
        $admin = $this->admin();
        $branch = Branch::create(['code' => 'HB-01', 'name' => 'Pusat', 'entity' => 'hendhys', 'type' => 'pusat', 'is_active' => true]);
        $target = $this->user(['entity' => 'hendhys', 'branch_id' => null], 'kasir_hendhys');
        $this->user(['entity' => 'hendhys', 'branch_id' => $branch->id], 'admin_hendhys');

        $this->actingAs($admin)->get(route('master.users.index', ['branch_id' => 'none']))
            ->assertInertia(fn (Assert $page) => $page->has('users', 2));
        $this->get(route('master.users.index', ['branch_id' => 'none', 'entity' => 'hendhys']))
            ->assertInertia(fn (Assert $page) => $page->has('users', 1)->where('users.0.id', $target->id));
    }
}
