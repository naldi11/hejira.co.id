<?php

namespace App\Services\Izzi;

use App\Models\{CashierShift, IzziSale, User};
use App\Services\{ActivityLogService, CheckoutService};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Sales
{
    public function __construct(private Access $access, private Inventory $inventory, private CheckoutService $checkout, private ActivityLogService $logger) {}

    private function shift(User $user, int $branchId): CashierShift
    {
        $shift = CashierShift::where('user_id', $user->id)->where('entity', 'izzi')->where('branch_id', $branchId)->where('status', 'open')->lockForUpdate()->first();
        if (!$shift) throw ValidationException::withMessages(['shift' => 'Buka laci kasir cabang aktif terlebih dahulu.']);
        return $shift;
    }

    public function record(array $data, User $user): IzziSale
    {
        $this->access->cashier($user); $branch = $this->access->branch($user, (int) $data['branch_id']);
        return $this->checkout->run(IzziSale::class, $data, function () use ($data, $user, $branch) {
            $shift = $this->shift($user, $branch->id);
            $menus = DB::table('izzi_menus')->whereIn('id', collect($data['items'])->pluck('menu_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $required = []; $total = 0;
            foreach ($data['items'] as $item) {
                $menu = $menus->get($item['menu_id']);
                if (!$menu || !$menu->is_active) throw ValidationException::withMessages(['items' => 'Menu tidak aktif.']);
                if ((int) $item['price'] !== (int) $menu->price) throw ValidationException::withMessages(['items' => 'Harga menu berubah. Muat ulang katalog sebelum membayar.']);
                $recipe = json_decode($menu->recipe, true, 512, JSON_THROW_ON_ERROR);
                if (empty($recipe)) throw ValidationException::withMessages(['items' => 'Resep menu belum diisi.']);
                $total += (int) $menu->price * $item['quantity'];
                foreach ($recipe as $ingredient) $required[$ingredient['product_id']] = ($required[$ingredient['product_id']] ?? 0) + $ingredient['quantity'] * $item['quantity'];
            }
            if ((int) $data['amount_paid'] < $total || ($data['payment_type'] !== 'cash' && (int) $data['amount_paid'] !== $total)) throw ValidationException::withMessages(['amount_paid' => 'Pembayaran belum sesuai total. Pembayaran non-tunai harus tepat sesuai total.']);
            $products = app(ProductCatalogue::class)->query()->whereIn('p.id', array_keys($required))->where('p.status', 'active')
                ->addSelect('u.abbreviation as unit_name')->lockForUpdate()->get()->keyBy('id');
            ksort($required); $stocks = [];
            foreach ($required as $id => $quantity) {
                $p = $products->get($id);
                if (!$p) throw ValidationException::withMessages(['items' => 'Bahan dalam resep sudah tidak aktif. Periksa resep menu.']);
                $stocks[$id] = $this->inventory->stock($branch, $p);
                if ((int) $stocks[$id]->quantity < $quantity) throw ValidationException::withMessages(['items' => 'Stok '.$p->name.' tidak cukup. Dibutuhkan '.Quantity::display($quantity).' '.$p->unit_name.', tersedia '.Quantity::display($stocks[$id]->quantity).'.']);
            }
            $sale = IzziSale::create(['checkout_key' => $data['checkout_key'], 'checkout_fingerprint' => '',
                'number' => $this->inventory->number('sale', now('Asia/Jakarta')->toDateString()), 'branch_id' => $branch->id, 'branch_name' => $branch->name,
                'shift_id' => $shift->id, 'total' => $total, 'net_total' => $total, 'status' => 'completed', 'customer_name' => $data['customer_name'] ?? null,
                'payment_type' => $data['payment_type'], 'amount_paid' => $data['amount_paid'], 'payment_reference' => $data['payment_reference'] ?? null,
                'created_by' => $user->id, 'actor_name' => $user->name, 'created_at' => now()]);
            foreach ($data['items'] as $item) {
                $menu = $menus[$item['menu_id']];
                $lineId = DB::table('izzi_sale_lines')->insertGetId(['sale_id' => $sale->id, 'menu_id' => $menu->id, 'menu_name' => $menu->name, 'menu_code' => $menu->code,
                    'recipe_version' => $menu->recipe_version, 'quantity' => $item['quantity'], 'price' => $menu->price]);
                foreach (json_decode($menu->recipe, true) as $ingredient) {
                    $p = $products[$ingredient['product_id']];
                    DB::table('izzi_sale_ingredients')->insert(['line_id' => $lineId, 'product_id' => $p->id, 'unit_id' => $p->unit_id, 'product_name' => $p->name,
                        'product_code' => $p->code, 'unit_name' => $p->unit_name, 'per_menu' => $ingredient['quantity'], 'quantity' => $ingredient['quantity'] * $item['quantity']]);
                }
            }
            foreach ($required as $id => $quantity) $this->inventory->movement($stocks[$id], (int) $stocks[$id]->quantity - $quantity, 'sale_consumption', $sale->id, $sale->number.' · Bahan untuk penjualan', $user, $sale->id);
            $this->logger->log('create', 'izzi_pos', $sale->number.' · '.$branch->name, $sale, null, ['total' => $total, 'ingredients' => $required]);
            return $sale;
        });
    }

    public function cancel(int $saleId, array $data, User $user): int
    {
        $this->access->manage($user); $branch = $this->access->branch($user, (int) $data['branch_id']);
        $hash = hash('sha256', json_encode([$saleId, $data, $user->id], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($saleId, $data, $user, $branch, $hash) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $sale = DB::table('izzi_sales')->where('id', $saleId)->first(); abort_unless($sale, 404);
            $shift = CashierShift::whereKey($sale->shift_id)->lockForUpdate()->first();
            $sale = DB::table('izzi_sales')->where('id', $saleId)->lockForUpdate()->first();
            abort_unless((int) $sale->branch_id === (int) $branch->id, 403);
            $previous = DB::table('izzi_cancellations')->where('request_key', $data['request_key'])->first();
            if ($previous) {
                if (!hash_equals($previous->fingerprint, $hash)) throw ValidationException::withMessages(['request_key' => 'Permintaan pembatalan digunakan untuk data berbeda.']);
                return (int) $previous->id;
            }
            if (!$shift || $shift->status !== 'open') throw ValidationException::withMessages(['reason' => 'Shift penjualan sudah ditutup. Pembatalan tidak boleh mengubah rekap yang sudah ditutup.']);
            $lines = DB::table('izzi_sale_lines')->where('sale_id', $saleId)->lockForUpdate()->get()->keyBy('id');
            $ingredients = DB::table('izzi_sale_ingredients')->whereIn('line_id', $lines->keys())->get()->groupBy('line_id');
            $restore = []; $waste = []; $refund = 0; $details = [];
            foreach ($data['items'] as $input) {
                $line = $lines->get($input['line_id']);
                $unused = (int) $input['unused']; $used = (int) $input['wasted']; $cancelled = $unused + $used;
                if (!$line || $cancelled < 1 || $cancelled > $line->quantity - $line->cancelled_quantity) throw ValidationException::withMessages(['items' => 'Jumlah pembatalan melebihi sisa menu yang belum dibatalkan.']);
                $refund += $cancelled * $line->price;
                foreach ($ingredients->get($line->id, collect()) as $ingredient) {
                    $restore[$ingredient->product_id] = ($restore[$ingredient->product_id] ?? 0) + $unused * $ingredient->per_menu;
                    $waste[$ingredient->product_id] = ($waste[$ingredient->product_id] ?? 0) + $used * $ingredient->per_menu;
                    DB::table('izzi_sale_ingredients')->where('id', $ingredient->id)->increment('restored_quantity', $unused * $ingredient->per_menu);
                    DB::table('izzi_sale_ingredients')->where('id', $ingredient->id)->increment('wasted_quantity', $used * $ingredient->per_menu);
                }
                DB::table('izzi_sale_lines')->where('id', $line->id)->update(['cancelled_quantity' => $line->cancelled_quantity + $cancelled,
                    'unused_quantity' => $line->unused_quantity + $unused, 'wasted_quantity' => $line->wasted_quantity + $used]);
                $details[] = ['line_id' => $line->id, 'menu_name' => $line->menu_name, 'unused' => $unused, 'wasted' => $used, 'refund' => $cancelled * $line->price];
            }
            $cancelId = DB::table('izzi_cancellations')->insertGetId(['request_key' => $data['request_key'], 'fingerprint' => $hash, 'sale_id' => $saleId,
                'reason' => $data['reason'], 'items' => json_encode($details, JSON_THROW_ON_ERROR), 'refund_amount' => $refund,
                'created_by' => $user->id, 'actor_name' => $user->name, 'created_at' => now()]);
            $remaining = (int) $sale->net_total - $refund;
            $stillSold = DB::table('izzi_sale_lines')->where('sale_id', $saleId)->whereColumn('quantity', '>', 'cancelled_quantity')->exists();
            DB::table('izzi_sales')->where('id', $saleId)->update(['net_total' => $remaining, 'status' => $stillSold ? 'part_cancelled' : 'cancelled']);
            $ids = array_keys($restore); sort($ids);
            $snapshots = $ingredients->flatten(1)->keyBy('product_id');
            foreach ($ids as $id) {
                $snapshot = $snapshots[$id]; $p = (object) ['id' => $id, 'name' => $snapshot->product_name, 'unit_id' => $snapshot->unit_id];
                $stock = $this->inventory->stock($branch, $p);
                if ($restore[$id] > 0) $this->inventory->movement($stock, (int) $stock->quantity + $restore[$id], 'cancellation_restore', $cancelId, $sale->number.' · '.$data['reason'], $user, $saleId);
                if ($waste[$id] > 0) {
                    $stock->quantity = (int) $stock->quantity + $restore[$id];
                    $this->inventory->movement($stock, (int) $stock->quantity, 'cancellation_waste', $cancelId,
                        $sale->number.' · Makanan terbuang: '.Quantity::display($waste[$id]).' '.$snapshot->unit_name.' sudah dipotong saat penjualan; tidak dipotong lagi. '.$data['reason'], $user, $saleId);
                }
            }
            $this->logger->log('void', 'izzi_pos', $sale->number.' · '.$data['reason'], null, null, ['refund' => $refund, 'restored' => $restore, 'wasted' => $waste]);
            return $cancelId;
        }, 3);
    }

    public function shiftSummary(CashierShift $shift): array
    {
        $payments = DB::table('izzi_sales')->where('shift_id', $shift->id)->selectRaw('payment_type, SUM(net_total) as total')->groupBy('payment_type')->pluck('total', 'payment_type');
        return ['tunai' => (int) ($payments['cash'] ?? 0), 'transfer' => (int) ($payments['transfer'] ?? 0), 'kartu_debit' => 0, 'kartu_kredit' => 0, 'kredit' => 0];
    }
}
