<?php

namespace App\Services;

use App\Models\{Branch, Product, User};
use App\Services\Izzi\Quantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class BranchStockTransfers
{
    public function __construct(private ActivityLogService $logger) {}

    public function branches()
    {
        return Branch::where('is_active', true)->where('type', 'cabang')->whereIn('entity', ['hendhys', 'jihans', 'izzi'])->orderBy('name')->get();
    }

    public function canOperate(User $user, Branch $branch): bool
    {
        if ($user->hasRole('owner') || !$branch->is_active || $branch->type !== 'cabang' || !in_array($branch->entity, ['hendhys','jihans','izzi'], true)) return false;
        if ($user->hasRole('super_admin')) return in_array($user->entity, [$branch->entity, 'all'], true);
        return $user->entity === $branch->entity
            && $user->hasAnyRole(['admin_'.$branch->entity, 'kasir_'.$branch->entity])
            && (int) (session('active_branch_id') ?: $user->branch_id) === (int) $branch->id;
    }

    public function sourceBranches(User $user)
    {
        return $this->branches()->filter(fn ($b) => $this->canOperate($user, $b))->values();
    }

    public function visibleQuery(User $user)
    {
        $query = DB::table('branch_stock_transfers');
        if ($user->hasRole('owner')) return $query->whereRaw('1 = 0');
        if ($user->hasRole('super_admin')) {
            if ($user->entity === 'all') return $query;
            return $query->where(fn ($q) => $q->where('from_entity', $user->entity)->orWhere('to_entity', $user->entity));
        }
        $ids = $this->sourceBranches($user)->pluck('id');
        return $query->where(fn ($q) => $q
            ->where(fn ($w) => $w->whereIn('from_branch_id', $ids)->where('from_entity', $user->entity))
            ->orWhere(fn ($w) => $w->whereIn('to_branch_id', $ids)->where('to_entity', $user->entity)));
    }

    public function pendingIncomingCount(User $user): int
    {
        return DB::table('branch_stock_transfers')->whereIn('status', ['sent', 'partial'])
            ->whereIn('to_branch_id', $this->sourceBranches($user)->pluck('id'))->count();
    }

    public function findVisible(int $id, User $user): object
    {
        $t = DB::table('branch_stock_transfers')->where('id', $id)->first();
        abort_unless($t, 404);
        abort_unless($this->visibleQuery($user)->where('id', $id)->exists(), 403);
        return $t;
    }

    private function action(string $key, array $payload): void
    {
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        DB::table('branch_transfer_actions')->insertOrIgnore(['request_key' => $key, 'payload_hash' => $hash]);
        $saved = DB::table('branch_transfer_actions')->where('request_key', $key)->lockForUpdate()->first();
        if ($saved->payload_hash !== $hash) throw ValidationException::withMessages(['request_key' => 'Permintaan ini sudah dipakai untuk data berbeda. Muat ulang halaman.']);
    }

    public function stockTable(string $entity): string
    {
        return match ($entity) { 'jihans' => 'jihans_retail_stock', 'izzi' => 'izzi_stock', 'hendhys' => 'hendhys_stock_branch', default => abort(403) };
    }
    public function movementTable(string $entity): string
    {
        return match ($entity) { 'jihans' => 'jihans_retail_stock_movements', 'izzi' => 'izzi_stock_movements', 'hendhys' => 'hendhys_stock_movements', default => abort(403) };
    }
    public function scale(string $entity): int { return $entity === 'izzi' ? 1000 : 1; }
    public function display(int|string $quantity, int $scale = 1): int|string { return $scale === 1000 ? Quantity::display($quantity) : (int) $quantity; }
    public function parse(mixed $value, int $scale, bool $zero = false): int
    {
        $milli = Quantity::parse($value, 'items', $zero);
        if ($scale === 1 && $milli % 1000) throw ValidationException::withMessages(['items' => 'Stok Jihan’s dan Hendhys harus dalam jumlah bulat sesuai satuan master. Gunakan jumlah atau konversi yang menghasilkan satuan utuh.']);
        return $scale === 1000 ? $milli : intdiv($milli, 1000);
    }
    public function converted(int $quantity, object $item): int
    {
        $amount = $quantity * intdiv(1000, (int) ($item->quantity_scale ?? 1));
        $denominator = (int) ($item->conversion_from ?? 1000); $numerator = (int) ($item->conversion_to ?? 1000);
        $gcd = function (int $a, int $b): int { while ($b) { [$a,$b] = [$b,$a % $b]; } return $a; };
        $g = $gcd((int) $amount, $denominator); $amount = intdiv((int) $amount, $g); $denominator = intdiv($denominator, $g);
        $g = $gcd($numerator, $denominator); $numerator = intdiv($numerator, $g); $denominator = intdiv($denominator, $g);
        if ($denominator !== 1 || $amount > intdiv(Quantity::MAX, $numerator)) throw ValidationException::withMessages(['items' => 'Konversi harus tepat sampai 3 desimal dan dalam batas saldo yang diizinkan.']);
        $converted = $amount * $numerator;
        if ((int) ($item->to_quantity_scale ?? 1) === 1 && $converted % 1000) throw ValidationException::withMessages(['items' => 'Hasil penerimaan Jihan’s/Hendhys harus bulat sesuai satuan tujuan. Periksa jumlah dan konversinya.']);
        return (int) ($item->to_quantity_scale ?? 1) === 1000 ? $converted : intdiv($converted, 1000);
    }
    public function stockQuery(Branch $branch, ?int $product = null)
    {
        return DB::table($this->stockTable($branch->entity))
            ->when($branch->entity !== 'jihans', fn ($q) => $q->where('branch_id', $branch->id))
            ->when($product !== null, fn ($q) => $q->where('product_id', $product));
    }
    private function move(Branch $branch, object $item, int $delta, object $transfer, User $user, string $event, string $side = 'from', ?int $receipt = null): void
    {
        $destination = $side === 'to';
        $productId = $destination ? ($item->to_product_id ?? $item->product_id) : $item->product_id;
        $unitId = $destination ? ($item->to_unit_id ?? $item->unit_id) : $item->unit_id;
        $productName = $destination ? ($item->to_product_name ?? $item->product_name) : $item->product_name;
        $unitName = $destination ? ($item->to_unit_name ?? $item->unit_name) : $item->unit_name;
        $table = $this->stockTable($branch->entity);
        $row = ['product_id' => $productId, 'unit_id' => $unitId, 'quantity' => 0, 'last_updated' => now()];
        if ($branch->entity !== 'jihans') $row['branch_id'] = $branch->id;
        if ($delta > 0) DB::table($table)->insertOrIgnore($row);
        $q = $this->stockQuery($branch, $productId); $stock = $q->lockForUpdate()->first();
        if (!$stock) throw ValidationException::withMessages(['items' => 'Kartu stok '.$productName.' belum tersedia di '.$branch->name.'. Mutasi tidak disimpan; saldo stok tidak berubah.']);
        if ((int) $stock->unit_id !== (int) $unitId) {
            $stockUnit = DB::table('master_units')->where('id', $stock->unit_id)->value('abbreviation') ?? 'tidak diketahui';
            throw ValidationException::withMessages(['items' => 'Satuan '.$productName.' di '.$branch->name.' adalah '.$stockUnit.', sedangkan satuan mutasi adalah '.$unitName.'. Periksa master produk dan kartu stok. Mutasi tidak disimpan; saldo stok tidak berubah.']);
        }
        $before = $branch->entity === 'izzi' ? (int) $stock->quantity : $this->parse((string) (0 + $stock->quantity), 1, true);
        $after = $before + $delta; $max = $branch->entity === 'izzi' ? Quantity::MAX : 999999999;
        if ($after < 0) throw ValidationException::withMessages(['items' => 'Stok '.$productName.' tidak cukup. Tersedia '.$this->display($before, $this->scale($branch->entity)).' '.$unitName.'.']);
        if ($after > $max) throw ValidationException::withMessages(['items' => 'Saldo tujuan melebihi batas yang diizinkan.']);
        $q->update(['quantity' => $after, 'last_updated' => now()]);
        $movement = ['product_id' => $productId, 'source' => 'adjustment', 'reference_id' => $receipt ?? $transfer->id,
            'branch_transfer_id' => $transfer->id, 'branch_transfer_item_id' => $item->id, 'branch_transfer_side' => $side,
            'type' => $delta < 0 ? 'out' : 'in', 'quantity' => abs($delta), 'quantity_before' => $before, 'quantity_after' => $after,
            'notes' => "{$event} {$transfer->number}: {$transfer->from_branch_name} → {$transfer->to_branch_name}", 'created_by' => $user->id, 'created_at' => now()];
        if ($branch->entity !== 'jihans') $movement['branch_id'] = $branch->id;
        if ($branch->entity === 'izzi') { $movement['unit_id'] = $unitId; $movement['actor_name'] = $user->name; }
        DB::table($this->movementTable($branch->entity))->insert($movement);
    }
    public function serializeItem(object $item): array
    {
        $scale = (int) ($item->quantity_scale ?? 1); $toScale = (int) ($item->to_quantity_scale ?? 1);
        return [...(array) $item, 'quantity' => $this->display($item->quantity, $scale), 'received_quantity' => $this->display($item->received_quantity, $scale),
            'remaining' => $this->display($item->quantity - $item->received_quantity, $scale),
            'to_product_name' => $item->to_product_name ?? $item->product_name, 'to_product_code' => $item->to_product_code ?? $item->product_code,
            'to_unit_name' => $item->to_unit_name ?? $item->unit_name,
            'destination_quantity' => $this->display($item->destination_quantity ?? $item->quantity, $toScale),
            'received_destination_quantity' => $this->display($item->to_product_id ? $item->received_destination_quantity : $item->received_quantity, $toScale),
            'conversion_from' => Quantity::display($item->conversion_from ?? 1000), 'conversion_to' => Quantity::display($item->conversion_to ?? 1000)];
    }

    private function nextNumber(string $date): string
    {
        DB::table('branch_transfer_daily_sequences')->insertOrIgnore(['date' => $date, 'last_number' => 0]);
        $sequence = DB::table('branch_transfer_daily_sequences')->where('date', $date)->lockForUpdate()->first();
        $next = (int) $sequence->last_number + 1;
        DB::table('branch_transfer_daily_sequences')->where('date', $date)->update(['last_number' => $next]);
        return 'MUT-'.Carbon::parse($date)->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function recordReceived(array $data, User $user): int
    {
        return DB::transaction(function () use ($data, $user) {
            $id = $this->send($data, $user, true);
            $details = DB::table('branch_stock_transfer_items')->where('transfer_id', $id)->get();
            $this->receiveItems($id, [
                'request_key' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'hejira:branch-receipt:'.$data['request_key'])->toString(),
                'physical_receiver_name' => $data['physical_receiver_name'],
                'notes' => trim($data['notes'] ?? '') ?: $data['reason'],
                'items' => $details->map(fn ($d) => ['id' => $d->id, 'quantity' => $this->display($d->quantity, $d->quantity_scale), 'expected_received_quantity' => 0])->all(),
            ], $user, true);
            return $id;
        }, 3);
    }

    // Retained for processing historical documents that were entered before receipt.
    public function send(array $data, User $user, bool $afterReceipt = false): int
    {
        $from = Branch::findOrFail($data['from_branch_id']);
        $to = Branch::findOrFail($data['to_branch_id']);
        abort_unless($this->canOperate($user, $from), 403, 'Anda hanya boleh mengirim dari cabang yang Anda kelola.');
        if (!$to->is_active || $to->type !== 'cabang' || !in_array($to->entity, ['hendhys','jihans','izzi'], true) || $to->id === $from->id) {
            throw ValidationException::withMessages(['to_branch_id' => 'Pilih cabang aktif Jihan’s, Hendhys, atau Izzi yang berbeda dari asal.']);
        }
        $items = collect($data['items'])->map(fn ($i) => [
            'product_id' => (int) $i['product_id'], 'quantity' => $this->parse($i['quantity'], $this->scale($from->entity)), 'notes' => trim($i['notes'] ?? ''),
            'to_product_id' => (int) ($i['to_product_id'] ?? $i['product_id']),
            'explicit_conversion' => isset($i['conversion_from'], $i['conversion_to']), 'conversion_from' => Quantity::parse($i['conversion_from'] ?? 1, 'items'), 'conversion_to' => Quantity::parse($i['conversion_to'] ?? 1, 'items'),
        ])->sortBy('product_id')->values()->all();
        if (count(array_unique(array_column($items, 'product_id'))) !== count($items)) throw ValidationException::withMessages(['items' => 'Produk yang sama cukup ditulis satu kali.']);
        $payload = ['action' => 'send', 'user' => $user->id, 'from' => $from->id, 'to' => $to->id, 'date' => $data['date'],
            'reason' => trim($data['reason']), 'notes' => trim($data['notes'] ?? ''), 'courier_name' => trim($data['courier_name'] ?? ''), 'items' => $items];
        if ($afterReceipt) $payload += ['mode' => 'after_receipt', 'sender' => trim($data['physical_sender_name']),
            'receiver' => trim($data['physical_receiver_name']), 'time' => $data['occurred_time']];
        return DB::transaction(function () use ($data, $payload, $items, $user, $from, $to, $afterReceipt) {
            $this->action($data['request_key'], $payload);
            $old = DB::table('branch_stock_transfers')->where('request_key', $data['request_key'])->first();
            if ($old) return $old->id;
            $number = $this->nextNumber($payload['date']);
            $id = DB::table('branch_stock_transfers')->insertGetId([
                'number' => $number,
                'request_key' => $data['request_key'], 'from_branch_id' => $from->id, 'to_branch_id' => $to->id,
                'from_branch_name' => $from->name, 'to_branch_name' => $to->name, 'from_entity' => $from->entity, 'to_entity' => $to->entity,
                'date' => $payload['date'], 'reason' => $payload['reason'], 'notes' => $payload['notes'], 'courier_name' => $payload['courier_name'],
                'status' => 'sent', 'sent_by' => $user->id, 'sent_by_name' => $user->name,
                'sent_at' => $afterReceipt ? Carbon::parse($payload['date'].' '.$data['occurred_time'], 'Asia/Jakarta') : now(),
                'recording_mode' => $afterReceipt ? 'after_receipt' : 'in_transit',
                'physical_sender_name' => $afterReceipt ? $payload['sender'] : null, 'recorded_at' => now(),
            ]);
            $t = DB::table('branch_stock_transfers')->find($id);
            foreach ($items as $i) {
                $product = Product::with('unit')->where('status', 'active')->findOrFail($i['product_id']);
                $destination = Product::with('unit')->where('status', 'active')->findOrFail($i['to_product_id']);
                if ($to->entity === 'izzi' && !DB::table('izzi_ingredients')->where('product_id', $destination->id)->exists()) throw ValidationException::withMessages(['items' => 'Pilih bahan tujuan yang terdaftar pada Bahan Izzi Kebab. Barang asal tetap memakai master dan satuan asalnya.']);
                if ($from->entity === 'izzi' && !DB::table('izzi_ingredients')->where('product_id', $product->id)->exists()) throw ValidationException::withMessages(['items' => 'Bahan asal belum terdaftar di Izzi.']);
                if ((int) $destination->unit_id !== (int) $product->unit_id && !$i['explicit_conversion']) throw ValidationException::withMessages(['items' => 'Satuan asal dan tujuan berbeda. Isi jumlah acuan asal dan jumlah setara tujuan secara jelas.']);
                if ($destination->id === $product->id && $i['conversion_from'] !== $i['conversion_to']) throw ValidationException::withMessages(['items' => 'Produk yang sama memiliki satuan master yang sama. Gunakan konversi 1 : 1.']);
                $item = ['transfer_id' => $id, 'product_id' => $product->id, 'unit_id' => $product->unit_id,
                    'product_name' => $product->name, 'product_code' => $product->code, 'unit_name' => $product->unit?->abbreviation ?? '-',
                    'quantity' => $i['quantity'], 'quantity_scale' => $this->scale($from->entity), 'notes' => $i['notes'],
                    'to_product_id' => $destination->id, 'to_unit_id' => $destination->unit_id, 'to_product_name' => $destination->name,
                    'to_product_code' => $destination->code, 'to_unit_name' => $destination->unit?->abbreviation ?? '-',
                    'to_quantity_scale' => $this->scale($to->entity), 'conversion_from' => $i['conversion_from'], 'conversion_to' => $i['conversion_to']];
                $item['destination_quantity'] = $this->converted($i['quantity'], (object) $item);
                $item['id'] = DB::table('branch_stock_transfer_items')->insertGetId($item);
                $this->move($from, (object) $item, -$i['quantity'], $t, $user, 'Pengiriman mutasi');
            }
            $description = $afterReceipt
                ? "{$number}: {$from->name} → {$to->name}, pengirim barang {$payload['sender']}, penerima {$payload['receiver']}, dicatat {$user->name}"
                : "{$number}: {$from->name} → {$to->name}, dikirim {$user->name}";
            $this->logger->log('create', 'branch_stock_transfer', $description);
            return $id;
        });
    }

    public function receive(int $id, array $data, User $user): int
    {
        return $this->receiveItems($id, $data, $user);
    }

    private function receiveItems(int $id, array $data, User $user, bool $afterReceipt = false): int
    {
        $transferItems = DB::table('branch_stock_transfer_items')->where('transfer_id', $id)->get()->keyBy('id');
        $items = collect($data['items'])->map(fn ($i) => ['id' => (int) $i['id'], 'quantity' => $this->parse($i['quantity'], $transferItems->get($i['id'])?->quantity_scale ?? 1, true), 'expected_received_quantity' => $this->parse($i['expected_received_quantity'], $transferItems->get($i['id'])?->quantity_scale ?? 1, true)])->sortBy('id')->values()->all();
        if (count(array_unique(array_column($items, 'id'))) !== count($items)) throw ValidationException::withMessages(['items' => 'Barang penerimaan tidak boleh berulang.']);
        if (array_sum(array_column($items, 'quantity')) < 1) throw ValidationException::withMessages(['items' => 'Isi sedikitnya satu barang yang benar-benar diterima.']);
        return DB::transaction(function () use ($id, $data, $items, $user, $afterReceipt) {
            $this->action($data['request_key'], ['action' => 'receive', 'user' => $user->id, 'transfer' => $id,
                'receiver' => trim($data['physical_receiver_name']), 'notes' => trim($data['notes']), 'items' => $items]);
            $t = DB::table('branch_stock_transfers')->where('id', $id)->lockForUpdate()->first();
            abort_unless($t, 404);
            $to = Branch::findOrFail($t->to_branch_id);
            $canRecord = $afterReceipt && $t->recording_mode === 'after_receipt' && (int) $t->sent_by === (int) $user->id
                && $this->canOperate($user, Branch::findOrFail($t->from_branch_id));
            abort_unless($to->entity === $t->to_entity && $to->is_active && $to->type === 'cabang'
                && ($canRecord || (!$afterReceipt && $this->canOperate($user, $to))), 403, 'Hanya petugas berwenang yang boleh mencatat penerimaan.');
            $old = DB::table('branch_stock_transfer_receipts')->where('request_key', $data['request_key'])->first();
            if ($old) return $old->id;
            if (!in_array($t->status, ['sent', 'partial'], true)) throw ValidationException::withMessages(['items' => 'Mutasi sudah selesai atau dibatalkan.']);
            $details = DB::table('branch_stock_transfer_items')->where('transfer_id', $id)->orderBy('product_id')->lockForUpdate()->get();
            if ($details->count() !== count($items) || $details->pluck('id')->sort()->values()->all() !== collect($items)->pluck('id')->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => 'Rincian penerimaan harus tepat sesuai dokumen mutasi.']);
            }
            $quantities = collect($items)->pluck('quantity', 'id');
            $expected = collect($items)->pluck('expected_received_quantity', 'id');
            foreach ($details as $d) if ($expected[$d->id] !== (int) $d->received_quantity) throw ValidationException::withMessages(['items' => 'Penerimaan sudah berubah sejak halaman dibuka. Muat ulang dan periksa barang sebelum menyimpan.']);
            foreach ($details as $d) if ($quantities[$d->id] > $d->quantity - $d->received_quantity) throw ValidationException::withMessages(['items' => 'Jumlah penerimaan '.$d->product_name.' melebihi sisa kiriman.']);
            $receipt = DB::table('branch_stock_transfer_receipts')->insertGetId([
                'request_key' => $data['request_key'], 'transfer_id' => $id, 'received_by' => $user->id, 'received_by_name' => $user->name,
                'physical_receiver_name' => trim($data['physical_receiver_name']), 'notes' => trim($data['notes']),
                'received_at' => $afterReceipt ? $t->sent_at : now(),
            ]);
            $remaining = 0;
            foreach ($details as $d) {
                $qty = $quantities[$d->id];
                if ($qty > 0) {
                    $destinationQty = $this->converted($qty, $d);
                    $this->move($to, $d, $destinationQty, $t, $user, 'Penerimaan mutasi', 'to', $receipt);
                    DB::table('branch_stock_transfer_items')->where('id', $d->id)->increment('received_destination_quantity', $destinationQty);
                    DB::table('branch_stock_transfer_items')->where('id', $d->id)->increment('received_quantity', $qty);
                    DB::table('branch_stock_transfer_receipt_items')->insert(['receipt_id' => $receipt, 'transfer_item_id' => $d->id, 'quantity' => $qty, 'destination_quantity' => $destinationQty]);
                }
                $remaining += $d->quantity - $d->received_quantity - $qty;
            }
            DB::table('branch_stock_transfers')->where('id', $id)->update(['status' => $remaining === 0 ? 'received' : 'partial']);
            $this->logger->log('update', 'branch_stock_transfer', "Penerimaan {$t->number} #{$receipt} oleh {$user->name}, sisa perjalanan {$remaining}");
            return $receipt;
        });
    }

    public function cancel(int $id, string $reason, User $user): void
    {
        DB::transaction(function () use ($id, $reason, $user) {
            $t = DB::table('branch_stock_transfers')->where('id', $id)->lockForUpdate()->first();
            abort_unless($t, 404);
            $from = Branch::findOrFail($t->from_branch_id);
            abort_unless($from->entity === $t->from_entity && $this->canOperate($user, $from), 403);
            if ($t->status === 'cancelled') return;
            if ($t->status !== 'sent' || DB::table('branch_stock_transfer_receipts')->where('transfer_id', $id)->exists()) {
                throw ValidationException::withMessages(['reason' => 'Mutasi yang sudah diterima sebagian atau seluruhnya tidak dapat dibatalkan.']);
            }
            foreach (DB::table('branch_stock_transfer_items')->where('transfer_id', $id)->orderBy('product_id')->get() as $item) {
                $this->move($from, $item, $item->quantity, $t, $user, 'Pembatalan mutasi');
            }
            DB::table('branch_stock_transfers')->where('id', $id)->update(['status' => 'cancelled', 'cancel_reason' => trim($reason),
                'cancelled_by' => $user->id, 'cancelled_by_name' => $user->name, 'cancelled_at' => now()]);
            $this->logger->log('update', 'branch_stock_transfer', "Pembatalan {$t->number} oleh {$user->name}: ".trim($reason));
        });
    }

    public function serialize(object $t): array
    {
        $row = (array) $t;
        $row['status_label'] = ['sent' => 'Dalam perjalanan', 'partial' => 'Diterima sebagian', 'received' => 'Selesai diterima', 'cancelled' => 'Dibatalkan'][$t->status];
        $row['date'] = Carbon::parse($t->date)->format('d/m/Y');
        $row['date_iso'] = $t->date;
        $row['recorded_at'] = Carbon::parse($t->recorded_at ?? $t->sent_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB';
        foreach (['sent_at', 'cancelled_at'] as $k) $row[$k] = $t->$k ? Carbon::parse($t->$k)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : null;
        return $row;
    }
}
