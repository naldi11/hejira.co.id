<?php

namespace App\Services;

use App\Models\{Branch, JihansGudangStock, Product, TransferOut, TransferRequest, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A delivery document debits the warehouse once; receiving is a separate transaction. */
class WarehouseDispatch
{
    public function __construct(private StockService $stock, private ActivityLogService $logger) {}

    public function send(array $data, User $user): TransferOut
    {
        abort_unless($user->hasRole('super_admin') && !$user->hasRole('owner') && in_array($user->entity, ['gudang', 'all'], true), 403);
        if ($data['to_entity'] === 'izzi' && empty($data['request_key'])) {
            throw ValidationException::withMessages(['request_key' => 'Muat ulang formulir sebelum mengirim ke Izzi.']);
        }
        $items = collect($data['items'])->map(fn ($item) => [
            'product_id' => (int) $item['product_id'], 'unit_id' => (int) $item['unit_id'],
            'quantity' => (int) $item['quantity'], 'hpp_price' => number_format((float) $item['hpp_price'], 2, '.', ''),
            'production_lot_id' => empty($item['production_lot_id']) ? null : (int) $item['production_lot_id'],
        ])->sortBy('product_id')->values();
        $payload = ['actor' => $user->id, 'entity' => $data['to_entity'],
            'branch' => $data['to_entity'] === 'jihans' ? null : (int) ($data['branch_id'] ?? 0),
            'request' => empty($data['request_id']) ? null : (int) $data['request_id'],
            'date' => $data['date'], 'notes' => trim($data['notes'] ?? ''), 'items' => $items->all()];
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($data, $user, $items, $payload, $hash) {
            $year = (int) now()->format('Y');
            DB::table('warehouse_dispatch_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);
            $sequence = DB::table('warehouse_dispatch_sequences')->where('year', $year)->lockForUpdate()->first();
            if (!empty($data['request_key'])) {
                $old = TransferOut::where('request_key', $data['request_key'])->lockForUpdate()->first();
                if ($old) {
                    if (!hash_equals($old->payload_hash, $hash)) {
                        throw ValidationException::withMessages(['request_key' => 'Permintaan ini sudah dipakai untuk pengiriman berbeda. Muat ulang formulir.']);
                    }
                    return $old;
                }
            }
            $branch = $payload['branch'] ? Branch::whereKey($payload['branch'])->lockForUpdate()->first() : null;
            if (in_array($payload['entity'], ['hendhys', 'izzi'], true)
                && (!$branch || !$branch->is_active || $branch->entity !== $payload['entity'] || ($payload['entity'] === 'izzi' && $branch->type !== 'cabang'))) {
                throw ValidationException::withMessages(['branch_id' => 'Pilih cabang aktif yang sesuai dengan unit tujuan.']);
            }
            if ($payload['request']) {
                $request = TransferRequest::whereKey($payload['request'])->lockForUpdate()->firstOrFail();
                if (!in_array($request->status, ['approved', 'partial'], true) || $request->from_entity !== $payload['entity']
                    || ($payload['entity'] === 'hendhys' && (int) $request->branch_id !== (int) $branch?->id)) {
                    throw ValidationException::withMessages(['request_id' => 'Permintaan barang tidak sesuai tujuan atau tidak dapat dikirim.']);
                }
            }
            $products = Product::whereIn('id', $items->pluck('product_id'))->visibleInGudang()->with('unit')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($items->pluck('product_id')->unique()->count() !== $items->count()) {
                throw ValidationException::withMessages(['items' => 'Produk yang sama cukup ditulis satu kali.']);
            }
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (!$product || $product->status !== 'active' || $product->product_type !== 'INV' || (int) $product->unit_id !== $item['unit_id']) {
                    throw ValidationException::withMessages(['items' => 'Gunakan produk persediaan aktif dan satuan master yang sesuai.']);
                }
                if ($product->source_type === 'produced' && !DB::table('production_lots')->where('entity', 'jihans')->where('product_id', $product->id)->whereNotNull('production_id')->exists()) {
                    throw ValidationException::withMessages(['items' => 'Produk produksi sendiri harus memiliki hasil produksi Gudang yang tercatat.']);
                }
                $card = JihansGudangStock::where('product_id', $product->id)->lockForUpdate()->first();
                if (!$card || (int) $card->unit_id !== $item['unit_id'] || $item['quantity'] < 1 || $item['quantity'] > (int) $card->quantity) {
                    throw ValidationException::withMessages(['items' => 'Stok atau satuan '.$product->name.' di Gudang tidak mencukupi/tidak sesuai. Tidak ada pengiriman yang disimpan.']);
                }
            }
            // Include documents made before this sequence existed, preserving the established numbering.
            $prefix = 'GDG-TRF-'.$year;
            $last = TransferOut::where('transfer_number', 'like', $prefix.'%')->orderByRaw('LENGTH(transfer_number) DESC')->orderByDesc('transfer_number')->value('transfer_number');
            $next = max((int) $sequence->last_number, $last ? (int) substr($last, strlen($prefix)) : 0) + 1;
            DB::table('warehouse_dispatch_sequences')->where('year', $year)->update(['last_number' => $next]);
            $document = TransferOut::create([
                'transfer_number' => $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
                'request_key' => $data['request_key'] ?? null, 'payload_hash' => $hash,
                'request_id' => $payload['request'], 'to_entity' => $payload['entity'], 'branch_id' => $branch?->id,
                'destination_name' => $branch?->name ?? 'Jihan’s — Stok Produksi', 'sender_name' => $user->name,
                'date' => $payload['date'], 'notes' => $payload['notes'], 'created_by' => $user->id, 'status' => 'sent',
            ]);
            $details = $items->map(function ($item) use ($document, $products) {
                $product = $products[$item['product_id']];
                $detail = $document->details()->create([
                    'product_id' => $product->id, 'unit_id' => $product->unit_id, 'quantity' => $item['quantity'],
                    'hpp_price' => $item['hpp_price'], 'total' => $item['quantity'] * (float) $item['hpp_price'],
                    'product_name' => $product->name, 'product_code' => $product->code, 'unit_name' => $product->unit?->abbreviation,
                ]);
                $detail->production_lot_id = $item['production_lot_id'];
                return $detail;
            });
            $document->setRelation('details', $details);
            $this->stock->processTransferOut($document);
            $this->logger->log('create', 'gudang.transfer_out', 'Pengiriman: '.$document->transfer_number.' ke '.$document->destination_name, $document);

            return $document;
        }, 3);
    }
}
