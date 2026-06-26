<?php

namespace Tests\Feature\Jihans;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JihansReportTest extends TestCase
{
    use RefreshDatabase;

    private function adminJihans(): User
    {
        Role::findOrCreate('admin_jihans', 'web');
        $user = User::factory()->create(['entity' => 'jihans']);
        $user->assignRole('admin_jihans');
        return $user;
    }

    public function test_reports_index_renders_inertia(): void
    {
        $this->actingAs($this->adminJihans())
            ->get(route('jihans.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Jihans/Reports/Index'));
    }
}
