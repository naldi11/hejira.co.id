<?php

namespace App\Services;

use App\Models\ReceiptConfirmation;
use App\Models\TransferOut;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** One atomic receiving path for the unified BAST and historical Hendhys forms. */
class TransferReceiving
{
    public function __construct(private StockService $stock, private ReturnQuarantine $quarantine, private ActivityLogService $logger) {}

    public static function rules(bool $unified = false): array
    {
        return [
            ...($unified ? ['quantity_bagus' => 'required|array|min:1', 'quantity_bagus.*' => 'required|integer|min:0',
                'quantity_rusak' => 'required|array|min:1', 'quantity_rusak.*' => 'required|integer|min:0']
                : ['received_quantities' => 'required|array|min:1', 'received_quantities.*' => 'required|integer|min:0']),
            'kondisi' => 'nullable|array', 'kondisi.*' => 'nullable|in:baik,rusak,kurang',
            'batch_number' => 'nullable|array', 'batch_number.*' => 'nullable|string|max:50',
            'expired_date' => 'nullable|array', 'expired_date.*' => 'nullable|date',
            'receive_notes' => 'nullable|string|max:2000', 'receive_kendala' => 'nullable|string|max:2000',
            'receive_received_by_name' => 'nullable|string|max:255', 'receive_pengirim_name' => 'nullable|string|max:255',
            'photos' => 'nullable|array|max:10', 'photos.*' => 'image|max:5120',
        ];
    }

    public function authorize(Model $document, User $user, bool $force = false): void
    {
        $warehouse = $document instanceof TransferOut;
        $entity = $warehouse ? $document->to_entity : ($document->branch?->entity ?? 'hendhys');
        $superAdmin = $user->hasRole('super_admin') && in_array($user->entity, [$entity, 'all'], true);
        if ($force) {
            $centreAdmin = $user->entity === 'hendhys' && $user->hasRole('admin_hendhys') && $user->branch?->type === 'pusat';
            $productionSuperAdmin = $user->hasRole('super_admin') && in_array($user->entity, ['hendhys', 'all'], true);
            abort_unless(! $warehouse && ($centreAdmin || $productionSuperAdmin), 403, 'Hanya Pusat atau Super Admin yang dapat melakukan konfirmasi atas nama cabang.');

            return;
        }
        abort_unless($user->entity === $entity || ($user->hasRole('super_admin') && $user->entity === 'all'), 403, 'Entitas penerima tidak sesuai.');
        if ($superAdmin) {
            return;
        }
        if ($entity === 'hendhys' || (! $warehouse && $entity === 'jihans')) {
            $destination = $document->branch_id;
            $same = $destination ? (int) $destination === (int) $user->branch_id : $user->branch?->type === 'pusat';
            abort_unless($same, 403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }
    }

    public function receive(Model $snapshot, array $data, User $user, bool $force = false): Model
    {
        return DB::transaction(function () use ($snapshot, $data, $user, $force) {
            $document = $snapshot->newQuery()->whereKey($snapshot->id)->lockForUpdate()->firstOrFail();
            $this->authorize($document, $user, $force);
            if ($document->status === 'received') {
                return $document;
            }
            if ($document->status !== 'sent') {
                throw ValidationException::withMessages(['received_quantities' => 'Pengiriman ini tidak dapat diterima pada status sekarang.']);
            }
            $warehouse = $document instanceof TransferOut;
            $entity = $warehouse ? $document->to_entity : ($document->branch?->entity ?? 'hendhys');
            $details = $document->details()->orderBy('product_id')->lockForUpdate()->get();
            $document->setRelation('details', $details);
            $unified = array_key_exists('quantity_bagus', $data);
            if (! $force) {
                $expected = $details->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                foreach ($unified ? ['quantity_bagus', 'quantity_rusak'] : ['received_quantities'] as $field) {
                    $ids = array_map('intval', array_keys($data[$field] ?? []));
                    sort($ids);
                    if ($ids !== $expected) {
                        throw ValidationException::withMessages([$field => 'Daftar barang harus tepat sesuai pengiriman ini. Muat ulang halaman.']);
                    }
                }
                foreach (['kondisi', 'batch_number', 'expired_date'] as $field) {
                    if (array_diff(array_map('intval', array_keys($data[$field] ?? [])), $expected)) {
                        throw ValidationException::withMessages([$field => 'Rincian bukan milik pengiriman ini.']);
                    }
                }
            }
            $receipt = ReceiptConfirmation::create([
                'receiptable_type' => $document::class, 'receiptable_id' => $document->id,
                'received_by' => $user->id, 'received_at' => now(), 'status' => 'completed',
                'general_notes' => $data['receive_notes'] ?? ($force ? 'Dikonfirmasi oleh Pusat atas nama Cabang.' : null),
            ]);
            foreach ($details as $detail) {
                $sent = (int) $detail->quantity;
                $condition = $data['kondisi'][$detail->id] ?? 'baik';
                $received = $force ? $sent : (int) ($data['received_quantities'][$detail->id] ?? 0);
                $good = $force ? $sent : ($unified ? (int) $data['quantity_bagus'][$detail->id] : ($condition === 'rusak' ? 0 : $received));
                $damaged = $force ? 0 : ($unified ? (int) $data['quantity_rusak'][$detail->id] : ($condition === 'rusak' ? $received : 0));
                if ($good < 0 || $damaged < 0 || $good + $damaged > $sent) {
                    throw ValidationException::withMessages(['received_quantities' => 'Jumlah baik dan rusak tidak boleh melebihi jumlah yang dikirim.']);
                }
                $missing = $sent - $good - $damaged;
                $detail->update(['received_quantity' => $good, 'kondisi' => $good === 0 && $damaged > 0 ? 'rusak' : ($missing > 0 ? 'kurang' : 'baik')]);
                foreach (['baik' => $good, 'rusak' => $damaged, 'kurang' => $missing] as $kind => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    $receipt->details()->create(['product_id' => $detail->product_id, 'expected_qty' => $sent, 'actual_qty' => $qty,
                        'condition' => $kind, 'batch_number' => $data['batch_number'][$detail->id] ?? null,
                        'expired_date' => $data['expired_date'][$detail->id] ?? null,
                        'notes' => $kind === 'kurang' ? 'Selisih belum diterima. Tidak menambah stok asal maupun tujuan.' : null]);
                }
                if (! $warehouse && $good > 0) {
                    if ($entity === 'jihans') {
                        $this->stock->creditJihansRetail($detail->product_id, $detail->unit_id, $good, 'receive_from_hendhys', $document->id, $user->id);
                    } else {
                        $this->stock->creditHendhys($detail->product_id, $detail->unit_id, $good, $document->branch_id, 'receive_from_pusat', $document->id, $user->id);
                    }
                }
                if ($entity === 'hendhys' && $damaged > 0) {
                    $this->stock->creditHendhysReturn($detail->product_id, $detail->unit_id, $damaged, $document->branch_id, $warehouse ? 'transfer_gudang' : 'receive_from_pusat', $document->id, $user->id);
                }
            }
            if ($warehouse) {
                $this->stock->processTransferReceive($document, $user->id);
            }
            $document->fill(['status' => 'received', 'received_by' => $user->id,
                'receive_notes' => $data['receive_notes'] ?? ($force ? 'Dikonfirmasi oleh Pusat atas nama Cabang.' : null),
                'receive_kendala' => $data['receive_kendala'] ?? null,
                'receive_received_by_name' => $data['receive_received_by_name'] ?? $user->name,
                'receive_pengirim_name' => $data['receive_pengirim_name'] ?? null]);
            if ($warehouse) {
                $document->received_at = now();
            }
            $document->save();
            $this->quarantine->recordTransferReceipt($document, $receipt, $entity, $user);
            foreach ($data['photos'] ?? [] as $file) {
                $path = $file->store('transfer-receipts/'.$document->transfer_number, 'public');
                $document->photos()->create(['path' => $path, 'uploaded_by' => $user->id, 'created_at' => now()]);
                $receipt->photos()->create(['photo_path' => $path]);
            }
            if ($warehouse) {
                $document->request?->update(['status' => 'completed']);
            } else {
                $document->branchRequest?->update(['status' => 'completed']);
            }
            $this->logger->log('receive', 'transfer.receipt', 'Konfirmasi BAST: '.$document->transfer_number, $document);

            return $document;
        }, 3);
    }
}
