<?php

namespace App\Services\Izzi;

use App\Models\{Branch, User};
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Inventory
{
    public function __construct(private Access $access, private ActivityLogService $logger) {}

    public function number(string $scope, string $date): string
    {
        DB::table('izzi_sequences')->insertOrIgnore(['scope' => $scope, 'date' => $date, 'last_number' => 0]);
        $sequence = DB::table('izzi_sequences')->where('scope', $scope)->where('date', $date)->lockForUpdate()->first();
        $next = (int) $sequence->last_number + 1;
        DB::table('izzi_sequences')->where('id', $sequence->id)->update(['last_number' => $next]);
        return ($scope === 'sale' ? 'IZ-POS-' : 'IZ-TERIMA-').str_replace('-', '', $date).'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function stock(Branch $branch, object $product, bool $create = false): object
    {
        if ($create) DB::table('izzi_stock')->insertOrIgnore(['branch_id' => $branch->id, 'product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => 0, 'last_updated' => now()]);
        $stock = DB::table('izzi_stock')->where('branch_id', $branch->id)->where('product_id', $product->id)->lockForUpdate()->first();
        if (!$stock || (int) $stock->unit_id !== (int) $product->unit_id) throw ValidationException::withMessages(['items' => 'Kartu stok '.$product->name.' belum tersedia atau satuannya berubah.']);
        return $stock;
    }

    public function movement(object $stock, int $after, string $source, int $reference, string $notes, User $user, ?int $saleId = null): int
    {
        if ($after < 0 || $after > Quantity::MAX) throw ValidationException::withMessages(['items' => 'Saldo stok di luar batas.']);
        $before = (int) $stock->quantity;
        DB::table('izzi_stock')->where('id', $stock->id)->update(['quantity' => $after, 'last_updated' => now()]);
        return DB::table('izzi_stock_movements')->insertGetId([
            'branch_id' => $stock->branch_id, 'product_id' => $stock->product_id, 'unit_id' => $stock->unit_id,
            'type' => $after < $before ? 'out' : ($after > $before ? 'in' : 'same'), 'source' => $source,
            'reference_id' => $reference, 'sale_id' => $saleId, 'quantity' => abs($after - $before),
            'quantity_before' => $before, 'quantity_after' => $after, 'notes' => $notes,
            'created_by' => $user->id, 'actor_name' => $user->name, 'created_at' => now(),
        ]);
    }

    public function receipt(array $data, User $user): int
    {
        $this->access->manage($user); $branch = $this->access->branch($user, (int) $data['branch_id']);
        $fingerprint = hash('sha256', json_encode([$data, $user->id], JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $user, $branch, $fingerprint) {
            // Serialize this operator before checking the request key, including changed dates.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            // The permanent date sequence row serializes duplicates before any stock is touched.
            DB::table('izzi_sequences')->insertOrIgnore(['scope' => 'receipt', 'date' => $data['date'], 'last_number' => 0]);
            DB::table('izzi_sequences')->where('scope', 'receipt')->where('date', $data['date'])->lockForUpdate()->first();
            $existing = DB::table('izzi_receipts')->where('request_key', $data['request_key'])->first();
            if ($existing) {
                if (!hash_equals($existing->fingerprint, $fingerprint)) throw ValidationException::withMessages(['request_key' => 'Nomor permintaan sudah digunakan untuk data lain.']);
                return (int) $existing->id;
            }
            $number = $this->number('receipt', $data['date']);
            $id = DB::table('izzi_receipts')->insertGetId(['request_key' => $data['request_key'], 'fingerprint' => $fingerprint, 'number' => $number,
                'branch_id' => $branch->id, 'branch_name' => $branch->name, 'origin' => $data['origin'], 'notes' => $data['notes'],
                'evidence_reference' => $data['evidence_reference'] ?? null, 'occurred_at' => Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta'),
                'items' => '[]', 'created_by' => $user->id, 'actor_name' => $user->name, 'created_at' => now()]);
            $snapshots = [];
            foreach (collect($data['items'])->sortBy('product_id') as $item) {
                $p = app(ProductCatalogue::class)->query()->where('p.id', $item['product_id'])->where('p.status', 'active')
                    ->addSelect('u.abbreviation as unit_name')->lockForUpdate()->first();
                if (!$p) throw ValidationException::withMessages(['items' => 'Pilih produk aktif yang sudah ada di master produk.']);
                $amount = Quantity::parse($item['quantity'], 'items');
                $factor = $item['mode'] === 'purchase' ? (int) $p->purchase_factor : Quantity::SCALE;
                $quantity = Quantity::convert($amount, $factor); $stock = $this->stock($branch, $p, true); $after = (int) $stock->quantity + $quantity;
                $this->movement($stock, $after, 'receipt', $id, $number.' · '.$data['origin'].' · '.$data['notes'], $user);
                $snapshots[] = ['product_id' => $p->id, 'product_name' => $p->name, 'product_code' => $p->code, 'unit_name' => $p->unit_name,
                    'input_quantity' => Quantity::display($amount), 'input_unit' => $item['mode'] === 'purchase' ? $p->purchase_unit : $p->unit_name,
                    'factor' => Quantity::display($factor), 'quantity' => Quantity::display($quantity), 'before' => Quantity::display($stock->quantity), 'after' => Quantity::display($after)];
            }
            DB::table('izzi_receipts')->where('id', $id)->update(['items' => json_encode($snapshots, JSON_THROW_ON_ERROR)]);
            $this->logger->log('create', 'izzi_receipt', $number.' · '.$branch->name, null, null, ['items' => $snapshots]);
            return $id;
        }, 3);
    }
}
