<?php

namespace Tests\Feature\Jihans;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansKaryawanTest extends TestCase
{
    use RefreshDatabase;

    private function adminJihans(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');
        return $user;
    }

    public function test_karyawan_index_renders_inertia(): void
    {
        Karyawan::create(['name' => 'John Doe', 'entity_scope' => 'jihans']);
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.master.karyawan.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Karyawan/Index')->has('karyawans'));
    }

    public function test_karyawan_create_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.master.karyawan.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Master/Karyawan/Form'));
    }

    public function test_karyawan_store_creates_karyawan(): void
    {
        $response = $this->actingAs($this->adminJihans())
            ->post(route('jihans.master.karyawan.store'), [
                'name'         => 'Jane Doe',
                'phone'        => '081234567890',
                'is_active'    => true,
            ]);

        $response->assertRedirect(route('jihans.master.karyawan.index'));
        $this->assertDatabaseHas('master_karyawan', ['name' => 'Jane Doe', 'entity_scope' => 'jihans']);
    }
}
