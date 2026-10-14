<?php

namespace Tests\Feature\Master;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserFormTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['owner', 'super_admin', 'kasir_hendhys', 'admin_hendhys', 'kasir_jihans', 'admin_jihans'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        $admin = User::factory()->create(['entity' => 'all']);
        $admin->assignRole('super_admin');

        return $admin;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Pengguna Baru', 'email' => 'baru@hejira.test',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'entity' => 'hendhys', 'role' => 'kasir_hendhys', 'branch_id' => null,
            'is_active' => true,
        ], $overrides);
    }

    private function branch(array $overrides = []): Branch
    {
        return Branch::create(array_replace([
            'code' => 'HB-01', 'name' => 'Hendhys Pusat', 'type' => 'pusat',
            'entity' => 'hendhys', 'is_active' => true,
        ], $overrides));
    }

    public function test_create_form_explains_every_role_and_includes_all_units_without_offering_owner(): void
    {
        $admin = $this->admin();
        $branch = $this->branch();
        $this->branch(['code' => 'HB-OLD', 'is_active' => false]);

        $this->actingAs($admin)->get(route('master.users.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Users/Form')
                ->has('unit_options', 5)
                ->where('unit_options.4.value', 'all')
                ->where('entity_roles.all', ['super_admin'])
                ->where('roles', fn ($roles) => ! in_array('owner', $roles->all(), true))
                ->has('role_descriptions', 8)
                ->where('role_descriptions', fn ($roles) => $roles->every(fn ($role) => ! empty($role['label']) && ! empty($role['description']) && ! empty($role['can']) && ! empty($role['cannot'])))
                ->has('branches', 1)
                ->where('branches.0.id', $branch->id)
                ->where('branches.0.type', 'pusat')
                ->where('branches.0.code', 'HB-01')
                ->where('branches.0.is_active', true));
    }

    public function test_super_admin_can_be_created_for_all_units_without_branch(): void
    {
        $this->actingAs($this->admin())->post(route('master.users.store'), $this->payload([
            'entity' => 'all', 'role' => 'super_admin',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('master.users.index'));
        $this->assertDatabaseHas('master_users', ['email' => 'baru@hejira.test', 'entity' => 'all', 'branch_id' => null]);
        $this->assertTrue(User::where('email', 'baru@hejira.test')->first()->hasRole('super_admin'));
    }

    public function test_business_user_can_be_created_with_matching_active_branch(): void
    {
        $admin = $this->admin();
        $branch = $this->branch();
        $this->actingAs($admin)->post(route('master.users.store'), $this->payload([
            'role' => 'admin_hendhys', 'branch_id' => $branch->id,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('master.users.index'));
        $this->assertDatabaseHas('master_users', ['email' => 'baru@hejira.test', 'entity' => 'hendhys', 'branch_id' => $branch->id]);
    }

    public function test_create_and_update_reject_inactive_branches(): void
    {
        $admin = $this->admin();
        $branch = $this->branch(['is_active' => false]);
        $this->actingAs($admin)->post(route('master.users.store'), $this->payload([
            'branch_id' => $branch->id,
        ]))->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('master_users', ['email' => 'baru@hejira.test']);

        $target = User::factory()->create(['entity' => 'hendhys']);
        $target->assignRole('kasir_hendhys');
        $this->put(route('master.users.update', $target), $this->payload([
            'email' => $target->email, 'branch_id' => $branch->id,
        ]))->assertSessionHasErrors('branch_id');
        $this->assertNull($target->fresh()->branch_id);
    }

    public function test_edit_exposes_old_inactive_branch_so_its_name_can_be_shown(): void
    {
        $admin = $this->admin();
        $old = $this->branch(['is_active' => false]);
        $this->branch(['code' => 'HB-OTHER', 'is_active' => false]);
        $target = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $old->id]);
        $target->assignRole('kasir_hendhys');

        $this->actingAs($admin)->get(route('master.users.edit', $target))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Users/Form')
                ->has('branches', 1)
                ->where('branches.0.id', $old->id)
                ->where('branches.0.name', 'Hendhys Pusat')
                ->where('branches.0.is_active', false));
    }

    public function test_gudang_and_all_units_reject_branch_placement(): void
    {
        $admin = $this->admin();
        $branch = $this->branch(['entity' => 'gudang']);
        foreach (['gudang', 'all'] as $entity) {
            $this->actingAs($admin)->post(route('master.users.store'), $this->payload([
                'entity' => $entity, 'role' => 'super_admin', 'branch_id' => $branch->id,
            ]))->assertSessionHasErrors('branch_id');
        }
        $this->assertDatabaseMissing('master_users', ['email' => 'baru@hejira.test']);
    }

    public function test_update_rejects_wrong_unit_branch_and_role(): void
    {
        $admin = $this->admin();
        $branch = $this->branch(['entity' => 'jihans']);
        $target = User::factory()->create(['entity' => 'hendhys']);
        $target->assignRole('kasir_hendhys');
        $this->actingAs($admin)->put(route('master.users.update', $target), $this->payload([
            'email' => $target->email, 'role' => 'kasir_jihans', 'branch_id' => $branch->id,
        ]))->assertSessionHasErrors(['role', 'branch_id']);
        $this->assertNull($target->fresh()->branch_id);
        $this->assertTrue($target->hasRole('kasir_hendhys'));
    }
}
