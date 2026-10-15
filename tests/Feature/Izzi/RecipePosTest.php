<?php
namespace Tests\Feature\Izzi;

use App\Models\{Branch, CashierShift, Product, ProductCategory, Unit, User};
use App\Services\ActivityLogService;
use App\Services\Izzi\Quantity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipePosTest extends TestCase
{
    use RefreshDatabase;
    private Branch $branch;
    private User $admin;
    private User $cashier;
    private User $owner;
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 15:00', 'Asia/Jakarta'));
        $this->branch = $this->branch();
        $this->admin = $this->user('admin_izzi', 'izzi', $this->branch);
        $this->cashier = $this->user('kasir_izzi', 'izzi', $this->branch);
        $this->owner = $this->user('owner', 'all');
    }
    private function branch(): Branch { return Branch::create(['code'=>Str::random(6),'name'=>'Izzi '.Str::random(4),'type'=>'cabang','entity'=>'izzi','is_active'=>true]); }
    private function user(string $role, string $entity, ?Branch $b = null): User
    {
        Role::findOrCreate($role,'web'); $u = User::factory()->create(['entity'=>$entity,'branch_id'=>$b?->id]); $u->assignRole($role); return $u;
    }
    private function as(User $user) { return $this->actingAs($user)->withSession(['active_branch_id'=>$user->branch_id]); }
    private function ingredient(string $unit='gram', string $factor='2000'): int
    {
        $code=Str::random(8);
        $masterUnit=Unit::firstOrCreate(['name'=>'Master '.$unit],['abbreviation'=>$unit,'entity_scope'=>'all']);
        $category=ProductCategory::firstOrCreate(['name'=>'Bahan bersama'],['entity_scope'=>'all']);
        $product=Product::create(['code'=>$code,'name'=>'Bahan '.$code,'unit_id'=>$masterUnit->id,'category_id'=>$category->id,'entity_scope'=>'all','status'=>'active','source_type'=>'purchased','product_type'=>'INV']);
        $this->as($this->admin)->post(route('izzi.ingredients.store'), ['product_id'=>$product->id,'purchase_unit'=>'Kemasan','purchase_factor'=>$factor])->assertSessionHasNoErrors()->assertRedirect();
        return $product->id;
    }
    private function menu(array $recipe, int $price=20000): int
    {
        $code=Str::random(8);
        $this->as($this->admin)->post(route('izzi.menus.store'), ['code'=>$code,'name'=>'Menu '.$code,'category'=>'Kebab','price'=>$price,'is_active'=>true,'recipe'=>$recipe])->assertSessionHasNoErrors()->assertRedirect();
        return (int) DB::table('izzi_menus')->where('code',$code)->value('id');
    }
    private function receiveData(int $id, string $qty='1', string $mode='purchase', ?Branch $branch=null): array
    {
        return ['request_key'=>(string)Str::uuid(),'branch_id'=>($branch??$this->branch)->id,'date'=>'2026-10-14','time'=>'14:00','origin'=>'Pemasok bahan','notes'=>'Penerimaan bahan sudah diperiksa','items'=>[['product_id'=>$id,'quantity'=>$qty,'mode'=>$mode]]];
    }
    private function receive(int $id, string $qty='1', string $mode='purchase'): void { $this->as($this->admin)->post(route('izzi.receipts.store'),$this->receiveData($id,$qty,$mode))->assertSessionHasNoErrors()->assertRedirect(); }
    private function shift(): void { $this->as($this->cashier)->post(route('izzi.shifts.open'),['starting_cash'=>100000])->assertSessionHasNoErrors()->assertRedirect(); }
    private function checkout(array $menus, int $paid=100000): array
    {
        return ['checkout_key'=>(string)Str::uuid(),'branch_id'=>$this->branch->id,'payment_type'=>'cash','amount_paid'=>$paid,'items'=>array_map(fn($m)=>['menu_id'=>$m[0],'quantity'=>$m[1],'price'=>DB::table('izzi_menus')->where('id',$m[0])->value('price')],$menus)];
    }
    private function sold(int $m, int $qty=1): int
    {
        $this->shift(); $this->as($this->cashier)->post(route('izzi.checkout'),$this->checkout([[$m,$qty]]))->assertSessionHasNoErrors()->assertRedirect();
        return (int)DB::table('izzi_sales')->max('id');
    }
    private function cancelData(int $sale, int $unused, int $wasted): array
    {
        return ['request_key'=>(string)Str::uuid(),'branch_id'=>$this->branch->id,'reason'=>'Salah pesanan pelanggan','confirmed'=>true,'items'=>[['line_id'=>DB::table('izzi_sale_lines')->where('sale_id',$sale)->value('id'),'unused'=>$unused,'wasted'=>$wasted]]];
    }
    private function balance(int $id): int { return (int)DB::table('izzi_stock')->where('branch_id',$this->branch->id)->where('product_id',$id)->value('quantity'); }

    public function test_recipe_is_empty_until_user_inputs_it_and_owner_can_open_empty_setup(): void
    {
        $this->assertDatabaseCount('izzi_menus',0); $this->assertDatabaseCount('izzi_ingredients',0);
        $this->admin->update(['branch_id'=>null]);$this->cashier->update(['branch_id'=>null]);$this->branch->delete();
        $this->as($this->owner)->get(route('izzi.dashboard'))->assertInertia(fn(Assert $p)=>$p->component('Izzi/Dashboard')->where('branch',null)->where('canManage',false));
    }
    public function test_all_recipe_ingredients_are_deducted_exactly_in_each_base_unit(): void
    {
        $g=$this->ingredient(); $pcs=$this->ingredient('pcs','10'); $ml=$this->ingredient('ml','1000');
        foreach([$g,$pcs,$ml] as $id) $this->receive($id);
        $m=$this->menu([['product_id'=>$g,'quantity'=>'40.125'],['product_id'=>$pcs,'quantity'=>'1'],['product_id'=>$ml,'quantity'=>'15.005']]);
        $sale=$this->sold($m,2);
        $this->assertSame(1919750,$this->balance($g)); $this->assertSame(8000,$this->balance($pcs)); $this->assertSame(969990,$this->balance($ml));
        $this->assertDatabaseCount('izzi_sale_ingredients',3);
        $this->as($this->owner)->get(route('izzi.sales.show',['sale'=>$sale,'branch_id'=>$this->branch->id]))->assertInertia(fn(Assert $p)=>$p->component('Izzi/Sale')->where('lines.0.ingredients.0.per_menu','40.125')->where('canCancel',false));
        $this->get(route('izzi.sales.receipt',['sale'=>$sale,'branch_id'=>$this->branch->id]))->assertOk()->assertSee('IZ-POS-20261014-00001');
    }
    public function test_purchases_and_decimal_packages_convert_once_with_receipt_snapshot(): void
    {
        $id=$this->ingredient(); $data=$this->receiveData($id,'1.25');
        $this->as($this->admin)->post(route('izzi.receipts.store'),$data)->assertSessionHasNoErrors();
        $this->post(route('izzi.receipts.store'),$data)->assertSessionHasNoErrors();
        $this->assertSame(2500000,$this->balance($id)); $this->assertDatabaseCount('izzi_receipts',1); $this->assertDatabaseCount('izzi_stock_movements',1);
        DB::table('izzi_ingredients')->where('product_id',$id)->update(['purchase_factor'=>3000000]);
        $this->post(route('izzi.receipts.store'),$data)->assertSessionHasNoErrors(); $this->assertSame(2500000,$this->balance($id));
        $snapshot=json_decode(DB::table('izzi_receipts')->value('items'),true)[0]; $this->assertSame('2000',$snapshot['factor']);
        $data['date']='2026-10-13'; $this->post(route('izzi.receipts.store'),$data)->assertSessionHasErrors('request_key');
    }
    public function test_insufficient_shared_ingredient_rolls_back_the_whole_basket(): void
    {
        $id=$this->ingredient();$this->receive($id,'50','base');
        $a=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$b=$this->menu([['product_id'=>$id,'quantity'=>'20']]);$this->shift();
        $this->as($this->cashier)->post(route('izzi.checkout'),$this->checkout([[$a,1],[$b,1]]))->assertSessionHasErrors('items');
        $this->assertSame(50000,$this->balance($id));$this->assertDatabaseCount('izzi_sales',0);$this->assertDatabaseCount('izzi_sale_lines',0);$this->assertDatabaseCount('izzi_stock_movements',1);
    }
    public function test_checkout_replay_does_not_deduct_twice_and_changed_price_is_rejected(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$this->shift();$data=$this->checkout([[$m,1]]);
        $this->as($this->cashier)->post(route('izzi.checkout'),$data)->assertSessionHasNoErrors();$this->post(route('izzi.checkout'),$data)->assertSessionHasNoErrors();
        $this->assertSame(1960000,$this->balance($id));$this->assertDatabaseCount('izzi_sales',1);
        $data['amount_paid']=90000;$this->post(route('izzi.checkout'),$data)->assertSessionHasErrors('checkout_key');
        $data=$this->checkout([[$m,1]]);$data['items'][0]['price']=1;$this->post(route('izzi.checkout'),$data)->assertSessionHasErrors('items');$this->assertDatabaseCount('izzi_sales',1);
    }
    public function test_recipe_changes_do_not_change_original_cancel_quantity_or_refund(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40.125']]);$sale=$this->sold($m,2);$old=DB::table('izzi_menus')->find($m);
        $this->as($this->admin)->put(route('izzi.menus.update',$m),['code'=>$old->code,'name'=>'Resep terbaru','category'=>'Kebab','price'=>30000,'is_active'=>true,'recipe'=>[['product_id'=>$id,'quantity'=>'80']]])->assertSessionHasNoErrors();
        $data=$this->cancelData($sale,1,0);$this->post(route('izzi.sales.cancel',$sale),$data)->assertSessionHasNoErrors();$this->post(route('izzi.sales.cancel',$sale),$data)->assertSessionHasNoErrors();
        $this->assertSame(1959875,$this->balance($id));$this->assertDatabaseCount('izzi_cancellations',1);
        $this->assertDatabaseHas('izzi_sale_lines',['sale_id'=>$sale,'recipe_version'=>1,'price'=>20000,'unused_quantity'=>1]);
        $this->assertDatabaseHas('izzi_sales',['id'=>$sale,'total'=>40000,'net_total'=>20000,'status'=>'part_cancelled']);$this->assertDatabaseCount('izzi_recipe_versions',2);
    }
    public function test_prepared_food_is_marked_wasted_without_restore_or_second_deduction(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40.125']]);$sale=$this->sold($m);$data=$this->cancelData($sale,0,1);
        $this->as($this->admin)->post(route('izzi.sales.cancel',$sale),$data)->assertSessionHasNoErrors();
        $this->assertSame(1959875,$this->balance($id));$this->assertDatabaseHas('izzi_sale_ingredients',['per_menu'=>40125,'restored_quantity'=>0,'wasted_quantity'=>40125]);
        $this->assertDatabaseHas('izzi_stock_movements',['source'=>'cancellation_waste','type'=>'same','quantity'=>0,'quantity_before'=>1959875,'quantity_after'=>1959875]);
        $this->assertDatabaseHas('izzi_sales',['id'=>$sale,'net_total'=>0,'status'=>'cancelled']);
    }
    public function test_mixed_partial_cancellations_and_over_cancellation_are_auditable(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m,3);
        $this->as($this->admin)->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,1))->assertSessionHasNoErrors();$this->assertSame(1920000,$this->balance($id));
        $this->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,1))->assertSessionHasErrors('items');$this->assertSame(1920000,$this->balance($id));
        $this->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertSessionHasNoErrors();$this->assertSame(1960000,$this->balance($id));$this->assertDatabaseCount('izzi_cancellations',2);
        $this->assertDatabaseHas('izzi_sale_lines',['sale_id'=>$sale,'cancelled_quantity'=>3,'unused_quantity'=>2,'wasted_quantity'=>1]);
    }
    public function test_cashier_and_owner_cannot_manage_recipe_receive_or_cancel(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m);
        foreach([$this->cashier,$this->owner,$this->user('super_admin','gudang')] as $user) {
            $this->as($user)->get(route('izzi.menus'))->assertForbidden();$this->post(route('izzi.receipts.store'),$this->receiveData($id))->assertForbidden();$this->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertForbidden();
        }
        $this->as($this->admin)->post(route('izzi.checkout'),$this->checkout([[$m,1]]))->assertForbidden();
    }
    public function test_branch_stock_and_transaction_access_do_not_leak_between_outlets(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m);$other=$this->branch();$actor=$this->user('admin_izzi','izzi',$other);
        $this->as($actor)->get(route('izzi.sales.show',['sale'=>$sale]))->assertForbidden();
        $this->post(route('izzi.receipts.store'),$this->receiveData($id))->assertForbidden();$this->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertForbidden();
        $this->get(route('izzi.stock'))->assertInertia(fn(Assert $p)=>$p->where('stocks.data.0.quantity','0'));
        $this->assertSame(1960000,$this->balance($id));
        $this->as($this->owner)->get(route('izzi.sales.show',['sale'=>$sale,'branch_id'=>$this->branch->id]))->assertOk();
    }
    public function test_pos_requires_own_shift_and_correct_payment(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);
        $this->as($this->cashier)->get(route('izzi.pos'))->assertRedirect(route('izzi.shifts'));
        $this->post(route('izzi.checkout'),$this->checkout([[$m,1]]))->assertSessionHasErrors('shift');$this->shift();
        $data=$this->checkout([[$m,1]],1);$this->post(route('izzi.checkout'),$data)->assertSessionHasErrors('amount_paid');
        $data=$this->checkout([[$m,1]],20001);$data['payment_type']='transfer';$this->post(route('izzi.checkout'),$data)->assertSessionHasErrors('amount_paid');$data['amount_paid']=20000;$this->post(route('izzi.checkout'),$data)->assertSessionHasNoErrors();
        $shift=CashierShift::first();$this->assertSame(0,$shift->calculatePaymentSummary()['tunai']);$this->assertSame(20000,$shift->calculatePaymentSummary()['transfer']);
    }
    public function test_closing_shift_uses_net_cash_and_prevents_subsequent_cancel(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m,2);
        $this->as($this->admin)->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertSessionHasNoErrors();
        $this->as($this->cashier)->post(route('izzi.shifts.close'),['actual_cash'=>120000,'note'=>'Uang sesuai setelah pengembalian'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_cashier_shifts',['entity'=>'izzi','status'=>'closed','expected_cash'=>120000,'actual_cash'=>120000,'discrepancy'=>0]);
        $this->as($this->admin)->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,0,1))->assertSessionHasErrors('reason');$this->assertDatabaseCount('izzi_cancellations',1);
    }
    public function test_base_unit_cannot_change_and_precision_cannot_silently_round(): void
    {
        $id=$this->ingredient();$p=Product::find($id);
        $this->as($this->admin)->put(route('izzi.ingredients.update',$id),['code'=>$p->code,'name'=>$p->name,'unit'=>'ml','purchase_unit'=>'Botol','purchase_factor'=>'1000','is_active'=>true])->assertSessionHasErrors('unit');
        $this->post(route('izzi.receipts.store'),$this->receiveData($id,'0.0001'))->assertSessionHasErrors('items');$this->assertDatabaseCount('izzi_stock',0);
        $this->assertSame(40125,Quantity::parse('40.125'));$this->assertSame('40.125',Quantity::display(40125));
    }
    public function test_logging_failure_rolls_back_sale_and_stock(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$this->shift();
        $this->mock(ActivityLogService::class,fn($m)=>$m->shouldReceive('log')->andThrow(new \RuntimeException('Audit unavailable')));
        $this->as($this->cashier)->post(route('izzi.checkout'),$this->checkout([[$m,1]]))->assertStatus(500);
        $this->assertSame(2000000,$this->balance($id));$this->assertDatabaseCount('izzi_sales',0);$this->assertDatabaseCount('izzi_stock_movements',1);
    }
    public function test_izzi_opname_requires_owner_permission_and_preserves_decimal_history(): void
    {
        $id=$this->ingredient();$this->receive($id,'200.125','base');$data=['request_key'=>(string)Str::uuid(),'location_key'=>'branch-'.$this->branch->id,'expires_at'=>'2026-10-14T17:00','notes'=>'Hitung bahan di cabang Izzi','confirmed'=>true];
        $this->as($this->admin)->post(route('owner.stock-opname.open'),$data)->assertForbidden();
        $this->as($this->owner)->post(route('owner.stock-opname.open'),$data)->assertSessionHasNoErrors();$session=DB::table('stock_opname_sessions')->value('id');
        $count=['product_id'=>$id,'expected_quantity'=>'200.125','quantity'=>'180.001','notes'=>'Selisih fisik sudah diperiksa','confirmed'=>true];
        $this->as($this->cashier)->post(route('stock-opname.count',$session),$count)->assertSessionHasNoErrors();$this->post(route('stock-opname.count',$session),$count)->assertSessionHasNoErrors();
        $this->assertSame(180001,$this->balance($id));$this->assertDatabaseCount('stock_opname_counts',1);
        $c=DB::table('stock_opname_counts')->first();$this->assertSame(1000,(int)$c->quantity_scale);
        $this->get(route('stock-opname.result',$c->id))->assertInertia(fn(Assert $p)=>$p->where('count.quantity_before','200.125')->where('count.quantity_after','180.001')->where('count.difference','-20.124'));
        $this->as($this->owner)->post(route('owner.stock-opname.close',$session),['confirmed'=>true])->assertSessionHasNoErrors();
        $this->as($this->cashier)->post(route('stock-opname.count',$session),$count)->assertSessionHasErrors('session_id');
    }
    public function test_opname_rejects_stale_stock_and_other_branch_access(): void
    {
        $id=$this->ingredient();$this->receive($id,'200.125','base');$data=['request_key'=>(string)Str::uuid(),'location_key'=>'branch-'.$this->branch->id,'expires_at'=>'2026-10-14T17:00','notes'=>'Hitung bahan di cabang Izzi','confirmed'=>true];
        $this->as($this->owner)->post(route('owner.stock-opname.open'),$data)->assertSessionHasNoErrors();$session=DB::table('stock_opname_sessions')->value('id');$count=['product_id'=>$id,'expected_quantity'=>'200','quantity'=>'180','notes'=>'Selisih fisik diperiksa','confirmed'=>true];
        $other=$this->user('admin_izzi','izzi',$this->branch());$this->as($other)->post(route('stock-opname.count',$session),$count)->assertForbidden();
        $this->as($this->admin)->post(route('stock-opname.count',$session),$count)->assertSessionHasErrors('expected_quantity');$this->assertDatabaseCount('stock_opname_counts',0);$this->assertSame(200125,$this->balance($id));
    }
    public function test_catalogue_and_histories_are_paginated(): void
    {
        $id=$this->ingredient();$this->receive($id);for($i=0;$i<23;$i++)$this->menu([['product_id'=>$id,'quantity'=>'10']]);$this->shift();
        $this->as($this->cashier)->get(route('izzi.pos',['lookup'=>'products']))->assertOk()->assertJsonCount(20,'products')->assertJsonPath('catalogue.has_more',true);
        $this->as($this->admin)->get(route('izzi.menus'))->assertInertia(fn(Assert $p)=>$p->has('menus.data',20)->where('menus.total',23));$this->get(route('izzi.menus',['per_page'=>'all']))->assertInertia(fn(Assert $p)=>$p->has('menus.data',23));
    }
    public function test_owner_totals_reports_and_exports_include_izzi_net_sales(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m,2);
        $this->as($this->admin)->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertSessionHasNoErrors();
        $this->as($this->owner)->get(route('owner.dashboard',['period'=>'today']))->assertOk()->assertInertia(fn(Assert $p)=>$p->where('stats.izzi_revenue',20000)->where('stats.total_revenue',20000)->where('stats.izzi_today',20000));
        $this->get(route('owner.reports',['unit_bisnis'=>'all','periode'=>'harian']))->assertOk()->assertInertia(fn(Assert $p)=>$p->where('reportData.0.izzi',20000)->where('reportData.0.total',20000)->where('reportData.0.label','2026-10-14'));
        $this->get(route('owner.dashboard.detail',['mode'=>'omset','unit'=>'all_transactions','filter'=>'today']))->assertOk()->assertInertia(fn(Assert $p)=>$p->has('list',1)->where('list.0.grand_total',20000)->where('list.0.href',route('izzi.sales.show',['sale'=>$sale,'branch_id'=>$this->branch->id])));
        $response=$this->get(route('owner.reports.export',['unit_bisnis'=>'izzi','periode'=>'harian','format'=>'csv']))->assertOk();$this->assertStringContainsString('20000',$response->streamedContent());
        $this->get(route('owner.reports.export',['unit_bisnis'=>'izzi','periode'=>'harian','format'=>'pdf']))->assertOk()->assertHeader('Content-Type','application/pdf');
    }
    public function test_superadmin_can_create_izzi_accounts_and_master_products_cannot_bypass_recipe_units(): void
    {
        $sa=$this->user('super_admin','all');
        $this->as($sa)->post(route('master.users.store'),['name'=>'Kasir Izzi Baru','email'=>'kasir@izzi.test','password'=>'secret123','password_confirmation'=>'secret123','entity'=>'izzi','role'=>'kasir_izzi','branch_id'=>$this->branch->id,'is_active'=>true])->assertSessionHasNoErrors();
        $created=User::where('email','kasir@izzi.test')->firstOrFail();$this->assertTrue($created->hasRole('kasir_izzi'));
        $this->as($created)->get(route('dashboard'))->assertRedirect(route('izzi.dashboard'));
        $id=$this->ingredient();$p=Product::find($id);$p->update(['entity_scope'=>'izzi']);$this->as($sa)->put(route('master.products.update',$id),['unit_id'=>999])->assertRedirect(route('izzi.ingredients'));$this->assertSame($p->unit_id,Product::find($id)->unit_id);
        $this->delete(route('master.products.destroy',$id))->assertRedirect(route('izzi.ingredients'));$this->assertNotNull(Product::find($id));
        $hnd=Branch::create(['code'=>'HND-SAFE','name'=>'Hendhys Uji','entity'=>'hendhys','type'=>'cabang','is_active'=>true]);
        $this->as($this->user('admin_hendhys','hendhys',$hnd))->put(route('hendhys.master.products.update',$id),[])->assertForbidden();
    }
    public function test_izzi_branch_with_stock_or_history_cannot_be_reassigned_or_deleted(): void
    {
        $id=$this->ingredient();$this->receive($id);$sa=$this->user('super_admin','all');
        $this->as($sa)->put(route('master.branches.update',$this->branch),['code'=>$this->branch->code,'name'=>$this->branch->name,'type'=>'cabang','entity'=>'hendhys','is_active'=>true])->assertSessionHasErrors('entity');
        $this->delete(route('master.branches.destroy',$this->branch))->assertSessionHas('error');$this->assertDatabaseHas('master_branches',['id'=>$this->branch->id,'entity'=>'izzi']);
    }
    public function test_receipt_and_cancel_logging_failure_leave_balances_and_events_unchanged(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m);
        $this->mock(ActivityLogService::class,fn($m)=>$m->shouldReceive('log')->andThrow(new \RuntimeException('Audit unavailable')));
        $this->app['router']->getRoutes()->getByName('izzi.receipts.store')->flushController();
        $this->as($this->admin)->post(route('izzi.receipts.store'),$this->receiveData($id))->assertStatus(500);$this->assertSame(1960000,$this->balance($id));$this->assertDatabaseCount('izzi_receipts',1);
        $this->post(route('izzi.sales.cancel',$sale),$this->cancelData($sale,1,0))->assertStatus(500);$this->assertSame(1960000,$this->balance($id));$this->assertDatabaseCount('izzi_cancellations',0);$this->assertDatabaseHas('izzi_sales',['id'=>$sale,'net_total'=>20000]);
    }

    public function test_setup_pages_and_recipe_versions_include_snapshots_and_bounded_catalogue(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40.125']]);
        $this->as($this->admin)->get(route('izzi.menus.edit',$m))->assertInertia(fn(Assert $p)=>$p->where('versions.0.recipe.0.quantity','40.125')->where('versions.0.recipe.0.unit_name','gram')->has('ingredients',1));
        foreach(['stock'=>'Izzi/Stock','movements'=>'Izzi/Movements','receipts'=>'Izzi/Receipts','receipts.create'=>'Izzi/Receive','shifts'=>'Izzi/Shifts','dashboard'=>'Izzi/Dashboard'] as $r=>$component) $this->get(route('izzi.'.$r))->assertOk()->assertInertia(fn(Assert $p)=>$p->component($component));
        $this->shift();$this->as($this->cashier)->get(route('izzi.pos'))->assertInertia(fn(Assert $p)=>$p->component('Izzi/Pos')->has('products',1));
    }
    public function test_retired_branches_preserve_read_only_history_for_owner(): void
    {
        $id=$this->ingredient();$this->receive($id);$m=$this->menu([['product_id'=>$id,'quantity'=>'40']]);$sale=$this->sold($m);$this->branch->update(['is_active'=>false]);
        $this->as($this->owner)->get(route('izzi.sales.show',['sale'=>$sale,'branch_id'=>$this->branch->id]))->assertOk()->assertInertia(fn(Assert $p)=>$p->where('branch.is_active',false));
        $sa=$this->user('super_admin','all');$this->as($sa)->post(route('izzi.receipts.store'),$this->receiveData($id))->assertForbidden();$this->assertSame(1960000,$this->balance($id));
    }
    public function test_izzi_migration_can_resume_without_removing_existing_rows(): void
    {
        $id=$this->ingredient();$this->receive($id);$migration=require database_path('migrations/2026_10_14_020000_create_izzi_recipe_pos.php');$migration->up();
        $this->assertSame(2000000,$this->balance($id));$this->assertDatabaseCount('izzi_receipts',1);$this->assertDatabaseCount('izzi_stock_movements',1);
    }

}
