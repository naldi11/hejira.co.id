<?php

namespace Tests\Feature\Master;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchUserTest extends TestCase
{
    use RefreshDatabase;

    private function adminGudang(): User
    {
        Role::findOrCreate('admin_gudang', 'web');
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        return $user;
    }

    public function test_branch_index_renders_inertia(): void
    {
        Branch::create(['code' => 'HND-1', 'name' => 'Pusat', 'type' => 'pusat', 'is_active' => true]);

        $this->actingAs($this->adminGudang())
            ->get(route('master.branches.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Branches/Index')->has('branches.data', 1));
    }

    public function test_branch_store_validates_and_persists(): void
    {
        $admin = $this->adminGudang();

        // Missing required fields → validation error.
        $this->actingAs($admin)->from(route('master.branches.create'))
            ->post(route('master.branches.store'), ['name' => ''])
            ->assertSessionHasErrors(['code', 'name', 'type']);

        $this->actingAs($admin)
            ->post(route('master.branches.store'), [
                'code' => 'CB-01', 'name' => 'Outlet A', 'type' => 'cabang', 'is_active' => true,
            ])
            ->assertRedirect(route('master.branches.index'));

        $this->assertDatabaseHas('master_branches', ['code' => 'CB-01', 'name' => 'Outlet A', 'type' => 'cabang']);
    }

    public function test_branch_code_must_be_unique(): void
    {
        Branch::create(['code' => 'DUP', 'name' => 'X', 'type' => 'cabang', 'is_active' => true]);

        $this->actingAs($this->adminGudang())->from(route('master.branches.create'))
            ->post(route('master.branches.store'), ['code' => 'DUP', 'name' => 'Y', 'type' => 'cabang'])
            ->assertSessionHasErrors('code');
    }

    public function test_user_store_creates_user_with_role(): void
    {
        Role::findOrCreate('kasir_jihans', 'web');

        $this->actingAs($this->adminGudang())
            ->post(route('master.users.store'), [
                'name' => 'Budi', 'email' => 'budi@hejira.test',
                'password' => 'secret123', 'password_confirmation' => 'secret123',
                'entity' => 'jihans', 'role' => 'kasir_jihans', 'is_active' => true,
            ])
            ->assertRedirect(route('master.users.index'));

        $user = User::where('email', 'budi@hejira.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('kasir_jihans'));
    }

    public function test_user_store_requires_password_confirmation(): void
    {
        Role::findOrCreate('kasir_jihans', 'web');

        $this->actingAs($this->adminGudang())->from(route('master.users.create'))
            ->post(route('master.users.store'), [
                'name' => 'Budi', 'email' => 'budi2@hejira.test',
                'password' => 'secret123', 'password_confirmation' => 'mismatch',
                'entity' => 'jihans', 'role' => 'kasir_jihans',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('master_users', ['email' => 'budi2@hejira.test']);
    }

    public function test_cannot_delete_own_account(): void
    {
        $admin = $this->adminGudang();

        $this->actingAs($admin)
            ->delete(route('master.users.destroy', $admin->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('master_users', ['id' => $admin->id]);
    }
}
