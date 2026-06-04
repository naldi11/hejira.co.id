<?php

namespace Tests\Feature\Jihans;

use App\Models\JihansProductionConfig;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansTortillaTest extends TestCase
{
    use RefreshDatabase;

    private function adminJihans(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');
        return $user;
    }

    public function test_tortilla_index_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.tortilla.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Tortilla/Index'));
    }

    public function test_tortilla_create_aktual_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.tortilla.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Tortilla/Form')->where('type', 'aktual'));
    }

    public function test_tortilla_create_prediksi_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.tortilla.prediksi.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Tortilla/Form')->where('type', 'prediksi'));
    }

    public function test_tortilla_recap_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.tortilla.recap'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Tortilla/Recap')->has('recap'));
    }
}
