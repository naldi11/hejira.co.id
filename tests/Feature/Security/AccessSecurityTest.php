<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\HendhysTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $entity): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_cannot_edit_reset_disable_or_delete_owner(): void
    {
        $admin = $this->actor('super_admin', 'all');
        $owner = $this->actor('owner', 'all');
        $original = $owner->fresh()->getAttributes();
        $this->actingAs($admin)->get(route('master.users.edit', $owner))->assertForbidden();

        foreach ([
            ['password' => 'newpassword123', 'password_confirmation' => 'newpassword123'],
            ['email' => 'replacement@example.test'],
            ['is_active' => false],
            ['entity' => 'owner'],
        ] as $change) {
            $this->put(route('master.users.update', $owner), array_replace([
                'name' => $owner->name, 'email' => $owner->email,
                'entity' => 'all', 'role' => 'owner', 'is_active' => true,
            ], $change))->assertForbidden();
        }

        $this->delete(route('master.users.destroy', $owner))->assertForbidden();
        $this->assertSame($original, $owner->fresh()->getAttributes());
        $this->get(route('master.users.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('users', fn ($users) => collect($users)->firstWhere('id', $owner->id)['can_manage'] === false));
    }

    public function test_inactive_user_cannot_login_even_with_correct_password(): void
    {
        $user = $this->actor('super_admin', 'all');
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public static function responseTypes(): array
    {
        return [['html'], ['json'], ['inertia']];
    }

    #[DataProvider('responseTypes')]
    public function test_deactivated_user_loses_existing_session(string $type): void
    {
        $user = $this->actor('super_admin', 'all');
        $this->actingAs($user)->withSession(['active_branch_id' => 123]);
        $user->update(['is_active' => false]);

        $headers = match ($type) {
            'json' => ['Accept' => 'application/json'],
            'inertia' => ['X-Inertia' => 'true'],
            default => [],
        };
        $response = $this->get(route('gudang.dashboard'), $headers);
        match ($type) {
            'json' => $response->assertUnauthorized(),
            'inertia' => $response->assertStatus(409)->assertHeader('X-Inertia-Location', route('login')),
            default => $response->assertRedirect(route('login')),
        };
        $this->assertGuest();
        $response->assertSessionMissing('active_branch_id');
    }

    public function test_cashier_cannot_read_other_branch_receipt_invoice_or_history(): void
    {
        $user = $this->actor('kasir_hendhys', 'hendhys');
        $branch = Branch::create(['code' => 'SEC-C', 'name' => 'Other Branch', 'type' => 'cabang', 'entity' => 'hendhys']);
        CashierShift::create(['user_id' => $user->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        $transaction = HendhysTransaction::create([
            'transaction_number' => 'SEC-INV', 'branch_id' => $branch->id, 'date' => today(),
            'time' => now()->toTimeString(), 'customer_name' => 'Private Customer',
            'customer_type' => 'Pelanggan Individual', 'ppn_type' => 'none', 'subtotal' => 25000,
            'discount_amount' => 0, 'tax_amount' => 0, 'other_costs' => 0, 'grand_total' => 25000,
            'status' => 'paid', 'created_by' => $user->id,
        ]);
        // A valid pusat branch avoids CheckBranch redirecting to branch selection.
        $pusat = Branch::create(['code' => 'SEC-P', 'name' => 'Pusat', 'type' => 'pusat', 'entity' => 'hendhys']);
        $user->update(['branch_id' => $pusat->id]);
        CashierShift::where('user_id', $user->id)->update(['branch_id' => $pusat->id]);
        $this->actingAs($user);
        foreach (['hendhys.pos.receipt', 'hendhys.pos.invoice', 'hendhys.transactions.show'] as $route) {
            $this->get(route($route, $transaction))->assertForbidden();
        }
        $transaction->update(['branch_id' => null]);
        $this->get(route('hendhys.pos.invoice', $transaction))->assertOk()->assertSee('Private Customer');
    }

    public function test_shift_details_and_list_cannot_cross_entities(): void
    {
        $cashier = $this->actor('kasir_hendhys', 'hendhys');
        $shift = CashierShift::create(['user_id' => $cashier->id, 'entity' => 'hendhys', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        $admin = $this->actor('admin_jihans', 'jihans');
        $this->actingAs($admin)->getJson(route('jihans.shifts.details', $shift))->assertForbidden();
        $this->getJson(route('jihans.shifts.by-date', ['entity' => 'hendhys']))->assertForbidden();
    }

    public function test_cashier_only_sees_own_shifts_but_admin_can_see_same_entity(): void
    {
        // This test exercises authorization against the MySQL LEAST expression
        // used by reports, with an equivalent function on the SQLite test DB.
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$values) => min($values), -1);
        $owner = $this->actor('kasir_jihans', 'jihans');
        $other = $this->actor('kasir_jihans', 'jihans');
        $shift = CashierShift::create(['user_id' => $owner->id, 'entity' => 'jihans', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => 0]);
        $this->actingAs($other)->getJson(route('jihans.shifts.details', $shift))->assertForbidden();
        $this->getJson(route('jihans.shifts.by-date', ['entity' => 'jihans']))->assertExactJson([]);
        $this->actingAs($owner)->getJson(route('jihans.shifts.details', $shift))->assertOk();
        $admin = $this->actor('admin_jihans', 'jihans');
        $this->actingAs($admin)->getJson(route('jihans.shifts.details', $shift))->assertOk();
        $this->getJson(route('jihans.shifts.by-date', ['entity' => 'jihans']))->assertJsonCount(1);
    }
}
