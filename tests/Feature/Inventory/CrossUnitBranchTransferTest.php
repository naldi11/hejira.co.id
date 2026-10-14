<?php
namespace Tests\Feature\Inventory;
use App\Models\{Branch,Product,ProductCategory,Unit,User};
use App\Services\{ActivityLogService,BranchStockTransfers};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
class CrossUnitBranchTransferTest extends TestCase
{
    use RefreshDatabase;
    private function branch(string $entity): Branch { return Branch::create(['code'=>Str::random(8),'name'=>$entity.' '.Str::random(5),'entity'=>$entity,'type'=>'cabang','is_active'=>true]); }
    private function user(string $role, string $entity='all', ?Branch $b=null): User { Role::findOrCreate($role,'web');$u=User::factory()->create(['entity'=>$entity,'branch_id'=>$b?->id]);$u->assignRole($role);return $u; }
    private function product(string $abbr='PCS', bool $ingredient=true): Product
    {
        $unit=Unit::firstOrCreate(['name'=>'Unit '.$abbr],['abbreviation'=>$abbr,'entity_scope'=>'all']);$category=ProductCategory::firstOrCreate(['name'=>'Produk uji'],['entity_scope'=>'all']);
        $p=Product::create(['code'=>Str::random(8),'name'=>'Barang '.Str::random(5),'unit_id'=>$unit->id,'category_id'=>$category->id,'entity_scope'=>'all','status'=>'active','source_type'=>'purchased','product_type'=>'INV']);
        if($ingredient)DB::table('izzi_ingredients')->insert(['product_id'=>$p->id,'purchase_unit'=>$abbr,'purchase_factor'=>1000,'created_at'=>now(),'updated_at'=>now()]);return $p;
    }
    private function stock(Branch $b, Product $p, int $qty): void { DB::table(app(BranchStockTransfers::class)->stockTable($b->entity))->insert(['product_id'=>$p->id,'unit_id'=>$p->unit_id,'quantity'=>$b->entity==='izzi'?$qty*1000:$qty,'last_updated'=>now(),...($b->entity!=='jihans'?['branch_id'=>$b->id]:[])]); }
    private function data(Branch $a, Branch $b, Product $p, mixed $qty=3): array { return ['request_key'=>(string)Str::uuid(),'from_branch_id'=>$a->id,'to_branch_id'=>$b->id,'date'=>now()->toDateString(),'occurred_time'=>'09:00','physical_sender_name'=>'Pengirim Uji','physical_receiver_name'=>'Penerima Uji','reason'=>'Mutasi kebutuhan operasional cabang','notes'=>'Barang sudah diterima','confirmed'=>true,'items'=>[['product_id'=>$p->id,'quantity'=>$qty]]]; }
    private function as(User $u) { return $this->actingAs($u)->withSession(['active_branch_id'=>$u->branch_id]); }
    private function qty(Branch $b, Product $p): int { return (int)app(BranchStockTransfers::class)->stockQuery($b,$p->id)->value('quantity'); }
    public function test_all_nine_unit_pairs_update_correct_stock_and_preserve_shared_jihans_pool(): void
    {
        foreach(['jihans','hendhys','izzi'] as $from)foreach(['jihans','hendhys','izzi'] as $to){
            $a=$this->branch($from);$b=$this->branch($to);$p=$this->product();$this->stock($a,$p,20);if($from!=='jihans'||$to!=='jihans')$this->stock($b,$p,5);
            $sender=$this->user('kasir_'.$from,$from,$a);$data=$this->data($a,$b,$p);$this->as($sender)->post(route('branch-transfers.store'),$data)->assertSessionHasNoErrors()->assertSessionHas('success');$id=DB::table('branch_stock_transfers')->max('id');
            $this->post(route('branch-transfers.store'),$data)->assertSessionHasNoErrors();
            $same=$from==='jihans'&&$to==='jihans';$this->assertSame(($same?20:17)*($from==='izzi'?1000:1),$this->qty($a,$p));$this->assertSame(($same?20:8)*($to==='izzi'?1000:1),$this->qty($b,$p));
            $this->assertDatabaseHas('branch_stock_transfers',['id'=>$id,'from_entity'=>$from,'to_entity'=>$to,'status'=>'received']);
            $this->as($this->user('kasir_'.$to,$to,$b))->get(route('branch-transfers.show',$id))->assertOk()->assertInertia(fn(Assert $v)=>$v->has('stockChanges',2)->where('stockChanges.0.location',$a->name)->where('stockChanges.1.location',$b->name)->where('transfer.to_entity',$to));
        }
        $this->assertDatabaseCount('branch_stock_transfers',9);$this->assertDatabaseCount('branch_stock_transfer_receipts',9);
    }
    public function test_package_to_izzi_grams_and_reverse_use_an_exact_snapshot_ratio(): void
    {
        $a=$this->branch('hendhys');$b=$this->branch('izzi');$pack=$this->product('PACK',false);$gram=$this->product('gram');$this->stock($a,$pack,20);$this->stock($b,$gram,1000);$sa=$this->user('super_admin');
        $data=$this->data($a,$b,$pack,2);$data['items'][0]+=['to_product_id'=>$gram->id,'conversion_from'=>1,'conversion_to'=>2000];
        $this->as($sa)->post(route('branch-transfers.store'),$data)->assertSessionHasNoErrors()->assertSessionHas('success');$id=DB::table('branch_stock_transfers')->value('id');
        $this->assertSame(18,$this->qty($a,$pack));$this->assertSame(5000000,$this->qty($b,$gram));
        $this->get(route('branch-transfers.show',$id))->assertInertia(fn(Assert $v)=>$v->where('items.0.destination_quantity','4000')->where('items.0.conversion_from','1')->where('items.0.conversion_to','2000')->where('stockChanges.1.unit','gram')->where('stockChanges.1.before','1000')->where('stockChanges.1.after','5000'));
        $reverse=$this->data($b,$a,$gram,2000);$reverse['items'][0]+=['to_product_id'=>$pack->id,'conversion_from'=>2000,'conversion_to'=>1];$this->post(route('branch-transfers.store'),$reverse)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(19,$this->qty($a,$pack));$this->assertSame(3000000,$this->qty($b,$gram));
        $this->get(route('branch-transfers.history',['search'=>$gram->code]))->assertInertia(fn(Assert $v)=>$v->has('transfers.data',2));
    }
    public function test_decimal_izzi_transfers_are_exact_and_do_not_truncate(): void
    {
        $a=$this->branch('izzi');$b=$this->branch('izzi');$p=$this->product('ml');$this->stock($a,$p,10);$data=$this->data($a,$b,$p,'0.125');
        $this->as($this->user('kasir_izzi','izzi',$a))->post(route('branch-transfers.store'),$data)->assertSessionHasNoErrors();
        $this->assertSame(9875,$this->qty($a,$p));$this->assertSame(125,$this->qty($b,$p));$id=DB::table('branch_stock_transfers')->value('id');
        $this->get(route('branch-transfers.show',$id))->assertInertia(fn(Assert $v)=>$v->where('items.0.quantity','0.125')->where('stockChanges.1.delta','0.125'));
        $data['request_key']=(string)Str::uuid();$data['items'][0]['quantity']='0.0001';$this->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items.0.quantity');$this->assertSame(125,$this->qty($b,$p));
    }
    public function test_unsafe_conversion_missing_ingredient_or_wrong_card_roll_back_all_balances(): void
    {
        $a=$this->branch('hendhys');$b=$this->branch('izzi');$pack=$this->product('PACK',false);$gram=$this->product('gram');$this->stock($a,$pack,10);$sa=$this->user('super_admin');$base=$this->data($a,$b,$pack);
        $this->as($sa)->post(route('branch-transfers.store'),$base)->assertSessionHasErrors('items');
        $data=$base;$data['items'][0]['to_product_id']=$gram->id;$this->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items');
        $data['items'][0]+=['conversion_from'=>3,'conversion_to'=>1];$data['items'][0]['quantity']=1;$this->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('branch_stock_transfers',0);$this->assertDatabaseCount('izzi_stock',0);$this->assertSame(10,$this->qty($a,$pack));
        $data['items'][0]['conversion_from']=1;$data['items'][0]['conversion_to']=2000;$this->stock($b,$gram,1);$wrong=$this->product('PCS');DB::table('izzi_stock')->where('branch_id',$b->id)->update(['unit_id'=>$wrong->unit_id]);
        $this->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items');$this->assertSame(10,$this->qty($a,$pack));$this->assertSame(1000,$this->qty($b,$gram));$this->assertDatabaseCount('branch_stock_transfer_receipts',0);
    }
    public function test_izzi_to_jihans_insufficient_stock_precision_and_permissions_are_enforced(): void
    {
        $a=$this->branch('izzi');$b=$this->branch('jihans');$other=$this->branch('izzi');$p=$this->product();$this->stock($a,$p,10);$data=$this->data($a,$b,$p,11);
        $this->as($this->user('kasir_izzi','izzi',$a))->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items');$data['items'][0]['quantity']='0.125';$this->post(route('branch-transfers.store'),$data)->assertSessionHasErrors('items');$this->assertSame(10000,$this->qty($a,$p));
        $this->as($this->user('kasir_izzi','izzi',$other))->post(route('branch-transfers.store'),$data)->assertForbidden();$this->get(route('branch-transfers.source-products',$a))->assertForbidden();
        $this->as($this->user('owner'))->get(route('branch-transfers.index'))->assertForbidden();$this->get(route('branch-transfers.destination-products',$b))->assertForbidden();
    }
    public function test_legacy_partial_receipts_and_cancellation_support_izzi_with_decimal_units(): void
    {
        $a=$this->branch('izzi');$b=$this->branch('izzi');$p=$this->product('gram');$this->stock($a,$p,10);$sender=$this->user('kasir_izzi','izzi',$a);$this->as($sender);$id=app(BranchStockTransfers::class)->send($this->data($a,$b,$p,'1.125'),$sender);
        $item=DB::table('branch_stock_transfer_items')->first();$receiver=$this->user('admin_izzi','izzi',$b);$this->as($receiver);$data=['request_key'=>(string)Str::uuid(),'physical_receiver_name'=>'Penerima Uji','notes'=>'Diterima sebagian sudah diperiksa','confirmed'=>true,'items'=>[['id'=>$item->id,'quantity'=>'0.125','expected_received_quantity'=>0]]];
        $this->post(route('branch-transfers.receive',$id),$data)->assertSessionHasNoErrors();$this->assertSame(125,$this->qty($b,$p));
        $data['request_key']=(string)Str::uuid();$data['items'][0]['quantity']=1;$data['items'][0]['expected_received_quantity']='0.125';$this->post(route('branch-transfers.receive',$id),$data)->assertSessionHasNoErrors();$this->assertSame(1125,$this->qty($b,$p));
        $this->as($sender);$id=app(BranchStockTransfers::class)->send($this->data($a,$b,$p,'0.125'),$sender);$this->post(route('branch-transfers.cancel',$id),['confirmed'=>true,'reason'=>'Salah tujuan sebelum barang diterima'])->assertSessionHasNoErrors();$this->assertSame(8875,$this->qty($a,$p));
    }
    public function test_stock_credit_from_mutation_is_available_for_izzi_recipe_sales(): void
    {
        $a=$this->branch('jihans');$b=$this->branch('izzi');$p=$this->product();$this->stock($a,$p,20);$sa=$this->user('super_admin');$this->as($sa)->post(route('branch-transfers.store'),$this->data($a,$b,$p,4))->assertSessionHasNoErrors();
        $cashier=$this->user('kasir_izzi','izzi',$b);$admin=$this->user('admin_izzi','izzi',$b);$this->as($admin)->post(route('izzi.menus.store'),['code'=>'MENU-QA','name'=>'Menu uji','category'=>'Uji','price'=>1000,'is_active'=>true,'recipe'=>[['product_id'=>$p->id,'quantity'=>1]]])->assertSessionHasNoErrors();$menu=DB::table('izzi_menus')->value('id');
        $this->as($cashier)->post(route('izzi.shifts.open'),['starting_cash'=>0])->assertSessionHasNoErrors();$this->post(route('izzi.checkout'),['checkout_key'=>(string)Str::uuid(),'branch_id'=>$b->id,'payment_type'=>'cash','amount_paid'=>1000,'items'=>[['menu_id'=>$menu,'price'=>1000,'quantity'=>1]]])->assertSessionHasNoErrors();$this->assertSame(3000,$this->qty($b,$p));
    }
    public function test_archiving_or_renaming_a_branch_preserves_the_transfer_audit(): void
    {
        $a=$this->branch('hendhys');$b=$this->branch('jihans');$p=$this->product();$this->stock($a,$p,20);$sa=$this->user('super_admin');$name=$a->name;
        $this->as($sa)->post(route('branch-transfers.store'),$this->data($a,$b,$p))->assertSessionHasNoErrors();$id=DB::table('branch_stock_transfers')->value('id');$a->update(['name'=>'Nama baru','is_active'=>false]);$b->update(['is_active'=>false]);
        $this->get(route('branch-transfers.show',$id))->assertOk()->assertInertia(fn(Assert $v)=>$v->where('transfer.from_branch_name',$name)->where('canCancel',false));
        $this->get(route('branch-transfers.history'))->assertInertia(fn(Assert $v)=>$v->has('transfers.data',1));
        $this->put(route('master.branches.update',$a),['code'=>$a->code,'name'=>$a->name,'type'=>'cabang','entity'=>'jihans','is_active'=>true])->assertSessionHasErrors('entity');
        $this->delete(route('master.branches.destroy',$a))->assertSessionHas('error');$this->assertDatabaseHas('master_branches',['id'=>$a->id]);
    }

}
