<?php

namespace Tests\Feature\Hendhys;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HendhysProductionTest extends TestCase
{
    use RefreshDatabase;

    private function hendhysPusat(): User
    {
        $branch = Branch::create(['code' => 'HND-PST', 'name' => 'Hendhys Pusat', 'type' => 'pusat', 'is_active' => true]);
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');
        return $user;
    }

    private function hendhysCabang(): User
    {
        $branch = Branch::create(['code' => 'HND-CB1', 'name' => 'Hendhys Cabang 1', 'type' => 'cabang', 'is_active' => true]);
        Role::findOrCreate('kasir_hendhys', 'web');
        $user = User::factory()->create(['entity' => 'hendhys', 'branch_id' => $branch->id]);
        $user->assignRole('kasir_hendhys');
        return $user;
    }

    public function test_productions_index_renders_for_pusat(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.productions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Productions/Index')->has('productions'));
    }

    public function test_productions_index_forbidden_for_cabang(): void
    {
        $this->actingAs($this->hendhysCabang())
            ->get(route('hendhys.productions.index'))
            ->assertForbidden();
    }

    public function test_productions_create_renders_for_pusat(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.productions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Productions/Create')->has('products')->has('units'));
    }

    public function test_transfer_requests_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.transfer-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/TransferRequests/Index')->has('requests'));
    }

    public function test_branch_requests_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.branch-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/BranchRequests/Index')->has('requests'));
    }

    public function test_transfer_to_branch_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.transfer-to-branch.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/TransferToBranch/Index')->has('transfers'));
    }

    public function test_returns_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.returns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Returns/Index')->has('returns'));
    }

    public function test_returns_to_gudang_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.returns-to-gudang.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/ReturnsToGudang/Index')->has('returns'));
    }

    public function test_pos_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Pos/Index')->has('products')->has('paymentMethods'));
    }

    public function test_reports_index_renders(): void
    {
        $this->actingAs($this->hendhysPusat())
            ->get(route('hendhys.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Hendhys/Reports/Index'));
    }
}
