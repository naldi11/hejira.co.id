<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up roles needed for dashboard checks
        Role::findOrCreate('owner', 'web');
        Role::findOrCreate('admin_gudang', 'web');
        Role::findOrCreate('kasir_jihans', 'web');
        Role::findOrCreate('admin_jihans', 'web');
        Role::findOrCreate('kasir_hendhys', 'web');
        Role::findOrCreate('admin_hendhys', 'web');
    }

    public function test_owner_redirected_to_owner_dashboard(): void
    {
        $user = User::factory()->create(['entity' => 'all']);
        $user->assignRole('owner');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_admin_gudang_redirected_to_gudang_dashboard(): void
    {
        $user = User::factory()->create(['entity' => 'gudang']);
        $user->assignRole('admin_gudang');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('gudang.dashboard'));
    }

    public function test_jihans_users_redirected_to_jihans_dashboard(): void
    {
        // Kasir Jihan's
        $kasir = User::factory()->create(['entity' => 'jihans']);
        $kasir->assignRole('kasir_jihans');
        $response1 = $this->actingAs($kasir)->get('/dashboard');
        $response1->assertRedirect(route('jihans.dashboard'));

        // Admin Jihan's
        $admin = User::factory()->create(['entity' => 'jihans']);
        $admin->assignRole('admin_jihans');
        $response2 = $this->actingAs($admin)->get('/dashboard');
        $response2->assertRedirect(route('jihans.dashboard'));
    }

    public function test_hendhys_users_redirected_to_hendhys_dashboard(): void
    {
        $branch = Branch::create(['code' => 'HND-PST', 'name' => 'Hendhys Pusat', 'type' => 'pusat', 'is_active' => true]);

        // Kasir Hendhys
        $kasir = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $kasir->assignRole('kasir_hendhys');
        $response1 = $this->actingAs($kasir)->get('/dashboard');
        $response1->assertRedirect(route('hendhys.dashboard'));

        // Admin Hendhys
        $admin = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $admin->assignRole('admin_hendhys');
        $response2 = $this->actingAs($admin)->get('/dashboard');
        $response2->assertRedirect(route('hendhys.dashboard'));
    }

    public function test_user_without_valid_role_is_logged_out_and_redirected_to_login_with_error(): void
    {
        $user = User::factory()->create();
        // Do not assign any role

        $response = $this->actingAs($user)->get('/dashboard');

        // Assert that they are redirected to /login with email error
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        
        // Assert they are logged out
        $this->assertGuest();
    }
}
