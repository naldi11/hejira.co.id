<?php

namespace Tests\Audit;

use App\Models\{Branch, CashierShift, GudangReturn, HendhysProduction, HendhysStockPusat, Product, ProductCategory, PurchaseOrder, Supplier, TransferOut, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Isolated evidence of audit findings, not safety regression tests. Assertions document current unsafe behavior. */
class SystemAuditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $entity = 'all', ?Branch $branch = null): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['entity' => $entity, 'branch_id' => $branch?->id]); $user->assignRole($role);
        return $user;
    }

    private function branch(string $name, string $type = 'cabang'): Branch
    {
        return Branch::create(['code' => $name, 'name' => $name, 'entity' => 'hendhys', 'type' => $type, 'is_active' => true]);
    }

    private function product(string $scope = 'all', string $source = 'purchased'): Product
    {
        $unit = Unit::create(['name' => 'Pcs'.fake()->unique()->numberBetween(1, 99999), 'abbreviation' => 'PCS', 'entity_scope' => 'all']);
        $cat = ProductCategory::create(['name' => 'Produk'.fake()->unique()->numberBetween(1, 99999), 'entity_scope' => 'all']);
        return Product::create(['code' => fake()->unique()->numerify('P-#####'), 'name' => 'Produk'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('######'), 'unit_id' => $unit->id, 'category_id' => $cat->id,
            'product_type' => 'INV', 'source_type' => $source, 'entity_scope' => $scope, 'status' => 'active', 'selling_price' => 1000]);
    }

    public function test_assigned_branch_admin_can_switch_to_production_centre_without_new_assignment(): void
    {
        $a = $this->branch('A'); $centre = $this->branch('CENTRE', 'pusat'); $admin = $this->user('admin_hendhys', 'hendhys', $a);
        $this->actingAs($admin)->get(route('hendhys.productions.create'))->assertForbidden();
        $this->post(route('select-branch.post'), ['branch_id' => $centre->id])->assertRedirect()->assertSessionHas('active_branch_id', $centre->id);
        $this->get(route('hendhys.productions.create'))->assertOk();
        $this->assertSame($a->id, $admin->fresh()->branch_id);
    }

    public function test_hendhys_excel_import_can_modify_a_jihans_only_product(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat')); $p = $this->product('jihans');
        $csv = "Template\n\n\n\nnama_produk,barcode,kategori,satuan,harga_jual,entitas_scope\n{$p->name},{$p->barcode},Umum,Pcs,9999,hendhys\n";
        $this->actingAs($admin)->post(route('hendhys.master.products.import'), ['file' => UploadedFile::fake()->createWithContent('products.csv', $csv)])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('hendhys', $p->fresh()->entity_scope);
        $this->assertEquals(9999, $p->fresh()->selling_price);
    }

    public function test_photo_url_accepts_local_file_and_publishes_its_contents_without_image_validation(): void
    {
        Storage::fake('public'); $admin = $this->user('super_admin');
        $supplier = Supplier::create(['code' => 'AUDIT-SUP', 'name' => 'Supplier', 'is_active' => true]); $p = $this->product();
        $markerPath = tempnam(sys_get_temp_dir(), 'hejira-audit-marker-'); file_put_contents($markerPath, 'SAFE_AUDIT_MARKER_ONLY');
        try {
            $this->actingAs($admin)->post(route('gudang.receiving.store'), [
                'supplier_id' => $supplier->id, 'date' => now()->toDateString(),
                'items' => [['product_id' => $p->id, 'unit_id' => $p->unit_id, 'quantity_bagus' => 1, 'quantity_rusak' => 0, 'hpp_price' => 1]],
                'photo_urls' => ['file://'.$markerPath],
            ])->assertRedirect()->assertSessionHas('success');
            $photo = \App\Models\ReceivingPhoto::firstOrFail();
            $this->assertSame('SAFE_AUDIT_MARKER_ONLY', Storage::disk('public')->get($photo->path));
        } finally { unlink($markerPath); }
    }

    public function test_last_owner_can_delete_their_own_account_through_profile(): void
    {
        $owner = $this->user('owner');
        $owner->update(['password' => \Illuminate\Support\Facades\Hash::make('audit-password')]);
        $this->actingAs($owner)->delete(route('profile.destroy'), ['password' => 'audit-password'])->assertRedirect('/');
        $this->assertSoftDeleted($owner);
        $this->assertSame(0, User::role('owner')->count());
    }

    public function test_centre_admin_is_excluded_from_pending_request_notifications(): void
    {
        $admin = $this->user('admin_hendhys', 'hendhys', $this->branch('CENTRE', 'pusat'));
        \App\Models\HendhysBranchRequest::create(['request_number' => 'AUDIT-REQUEST', 'branch_id' => $this->branch('A')->id,
            'date' => now()->toDateString(), 'status' => 'pending', 'requested_by' => $admin->id]);
        $this->actingAs($admin);
        $counts = app(\App\Http\Controllers\Api\NotificationController::class)->getCounts()->getData(true);
        $this->assertSame(1, \App\Models\HendhysBranchRequest::where('status', 'pending')->count());
        $this->assertSame(0, $counts['hendhys_pusat_pending']);
    }
}
