<?php

namespace App\Services;

use App\Models\{GudangReturn, JihansGudangStockMovement, JihansProductionSession, Receiving, TransferOut};
use Illuminate\Support\Facades\DB;

class OwnerStockMovement
{
    private const SOURCES = [
        'purchase_receiving' => 'Penerimaan dari pemasok', 'receiving' => 'Penerimaan dari pemasok',
        'receiving_edit' => 'Koreksi penerimaan barang', 'transfer_out' => 'Pengiriman dari Gudang Utama',
        'return_receiving' => 'Penerimaan retur', 'return_receive' => 'Penerimaan retur',
        'production' => 'Hasil produksi Jihan’s', 'adjustment' => 'Penyesuaian stok',
    ];

    public function show(JihansGudangStockMovement $movement): array
    {
        $movement->load(['product' => fn ($q) => $q->withTrashed()->with('unit'), 'creator']);
        $document = $this->document($movement);
        return [
            'movement' => [
                'id' => $movement->id, 'product' => $movement->product?->name ?? 'Produk tidak tersedia',
                'code' => $movement->product?->code, 'type' => $movement->type,
                'source' => $document && $document['status'] === 'writeoff_reversed' ? 'Pembatalan penghapusan stok oleh owner'
                    : ($document && in_array($document['status'], ['written_off', 'writeoff_cancelled'], true) ? 'Penghapusan sisa produksi oleh owner'
                    : ($this->isShortfall($movement) ? 'Pengembalian selisih pengiriman' : (self::SOURCES[$movement->source] ?? 'Mutasi stok lainnya'))),
                'quantity' => $movement->quantity, 'before' => $movement->quantity_before,
                'after' => $movement->quantity_after, 'delta' => $movement->quantity_after - $movement->quantity_before,
                'unit' => $movement->product?->unit?->abbreviation ?? '-',
                'recorded_at' => $movement->created_at ? $movement->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Belum tercatat',
                'user' => $movement->creator?->name ?? 'Pengguna tidak tersedia', 'notes' => $movement->notes,
            ],
            'document' => $document,
            'document_message' => $document ? null : ($movement->source === 'adjustment' && !$movement->reference_id
                ? 'Penyesuaian ini dicatat langsung pada stok, sehingga tidak memiliki dokumen transaksi terpisah.'
                : 'Dokumen asal mutasi belum tersedia atau rujukannya tidak dapat dipastikan. Rincian mutasi tetap ditampilkan di atas.'),
        ];
    }

    private function document(JihansGudangStockMovement $movement): ?array
    {
        if (!$movement->reference_id) return null;
        if ($movement->source === 'adjustment') {
            $writeoff = DB::table('production_writeoffs as w')->leftJoin('production_writeoff_reversals as r', 'r.writeoff_id', '=', 'w.id')
                ->select('w.*', 'r.id as reversal_id', 'r.movement_id as reversal_movement_id', 'r.notes as reversal_notes',
                    'r.owner_name as reversal_owner', 'r.created_at as reversed_at')
                ->where('w.id', $movement->reference_id)->where('w.entity', 'jihans')->where('w.product_id', $movement->product_id)
                ->where(fn ($q) => $q->where('w.movement_id', $movement->id)->orWhere('r.movement_id', $movement->id))->first();
            $isReversal = $writeoff && (int) $writeoff->reversal_movement_id === (int) $movement->id;
            if ($writeoff) return [
                'title' => $isReversal ? 'Pembatalan penghapusan sisa produksi' : 'Penghapusan sisa produksi',
                'number' => 'Penghapusan #'.$writeoff->id, 'status' => $isReversal ? 'writeoff_reversed' : ($writeoff->reversal_id ? 'writeoff_cancelled' : 'written_off'),
                'date' => ProductionWriteoffs::time($isReversal ? $writeoff->reversed_at : $writeoff->created_at),
                'notes' => $isReversal ? $writeoff->reversal_notes : $writeoff->notes,
                'fields' => [
                    ['label' => 'Nomor produksi', 'value' => $writeoff->production_number ?? 'Tanpa nomor produksi'],
                    ['label' => 'Owner', 'value' => $isReversal ? $writeoff->reversal_owner : $writeoff->owner_name],
                    ['label' => 'Alasan', 'value' => ProductionWriteoffs::REASONS[$writeoff->reason]],
                    ...($writeoff->reversal_id ? [
                        ['label' => 'Dibatalkan pada', 'value' => ProductionWriteoffs::time($writeoff->reversed_at)],
                        ['label' => 'Alasan pembatalan', 'value' => $writeoff->reversal_notes],
                    ] : []),
                ],
                'items' => [['id' => $writeoff->id, 'product' => $writeoff->product_name, 'unit' => $writeoff->unit_name,
                    'quantity' => (int) $writeoff->quantity, 'related' => true]],
                'links' => [['label' => 'Lihat riwayat penghapusan', 'href' => route('owner.production-writeoffs.index', ['entity' => 'jihans'])]],
            ];
        }
        $record = match ($movement->source) {
            'purchase_receiving', 'receiving', 'receiving_edit' => Receiving::with(['supplier', 'creator', 'po', 'details.product', 'details.unit', 'photos'])->find($movement->reference_id),
            'transfer_out' => TransferOut::with(['branch', 'creator', 'receiver', 'details.product', 'details.unit'])->find($movement->reference_id),
            // Shortfall credits use the legacy return enum, but their reference is a transfer ID.
            // Require both the stored ID and the document number from the recorded shortfall note.
            'return_receiving', 'return_receive' => $this->isShortfall($movement)
                ? TransferOut::with(['branch', 'creator', 'receiver', 'details.product', 'details.unit'])
                    ->where('transfer_number', substr($movement->notes, strlen('Selisih penerimaan dari ')))->find($movement->reference_id)
                : GudangReturn::with(['branch', 'creator', 'receiver', 'details.product' => fn ($q) => $q->withTrashed(), 'details.unit'])->find($movement->reference_id),
            'production' => JihansProductionSession::with(['creator', 'details.product.unit', 'details.karyawan'])->find($movement->reference_id),
            default => null,
        };
        if (!$record || !$record->details->contains('product_id', $movement->product_id)) return null;
        if ($record instanceof Receiving) {
            return $this->header($record, 'Penerimaan barang dari pemasok', $record->grn_number, [
                ['label' => 'Pemasok', 'value' => $record->supplier?->name ?? '-'],
                ['label' => 'Petugas penerima', 'value' => $record->received_by_name ?? $record->creator?->name ?? '-'],
            ]) + [
                'items' => $record->details->map(fn ($d) => $this->item($d, $movement) + [
                    'condition' => $d->kondisi === 'baik' ? 'Baik' : 'Rusak',
                    'price' => (float) $d->hpp_price, 'total' => (float) $d->total,
                ])->values()->all(),
                'links' => $record->po ? [['label' => 'Lihat PO '.$record->po->po_number, 'href' => route('owner.po.show', $record->po_id)]] : [],
                'photos' => $record->photos->map(fn ($p) => ['url' => $p->url(), 'caption' => $p->caption])->values()->all(),
            ];
        }
        if ($record instanceof GudangReturn) {
            return $this->header($record, 'Retur barang ke Gudang Utama', $record->return_number, [
                ['label' => 'Dikirim dari', 'value' => $record->from_entity_label],
                ['label' => 'Pengirim', 'value' => $record->creator?->name ?? '-'],
                ['label' => 'Penerima di gudang', 'value' => $record->receiver?->name ?? '-'],
                ['label' => 'Diterima pada', 'value' => $record->received_at ? $record->received_at->format('d/m/Y H:i:s').' WIB' : null],
            ]) + ['items' => $record->details->map(fn ($d) => $this->item($d, $movement) + [
                'received' => $d->received_quantity === null ? null : (int) $d->received_quantity, 'condition' => $d->condition,
            ])->values()->all()];
        }
        if ($record instanceof TransferOut) {
            $sources = app(ProductionLots::class)->dispatchSources('transfer_out', $record->id);
            return $this->header($record, 'Pengiriman dari Gudang Utama', $record->transfer_number, [
                ['label' => 'Tujuan', 'value' => $record->branch?->name ?? ($record->to_entity === 'jihans' ? 'Outlet Jihan’s (cabang tidak dicatat)' : 'Hendhys')],
                ['label' => 'Pengirim', 'value' => $record->creator?->name ?? '-'],
                ['label' => 'Penerima', 'value' => $record->receiver?->name ?? '-'],
            ]) + ['items' => $record->details->map(fn ($d) => $this->item($d, $movement) + [
                'received' => $d->received_quantity === null ? null : (int) $d->received_quantity,
                'production_lots' => $sources[$d->id] ?? [],
            ])->values()->all()];
        }
        return $this->header($record, 'Produksi Jihan’s', $record->session_number, [
            ['label' => 'Jenis produksi', 'value' => $record->type === 'aktual' ? 'Aktual (sudah diproduksi)' : 'Prediksi'],
        ]) + [
            'items' => $record->details->map(fn ($d) => $this->item($d, $movement) + ['worker' => $d->karyawan?->name])->values()->all(),
            'links' => [['label' => 'Lihat produksi dan sisa stok', 'href' => route('owner.production.show', ['entity' => 'jihans', 'id' => $record->id])]],
        ];
    }

    private function header($record, string $title, string $number, array $fields): array
    {
        return ['title' => $title, 'number' => $number, 'status' => $record->status ?? $record->type,
            'date' => $record->date?->format('d/m/Y'), 'notes' => $record->notes,
            'fields' => [['label' => 'Dibuat oleh', 'value' => $record->creator?->name ?? '-'], ...$fields]];
    }

    private function isShortfall(JihansGudangStockMovement $movement): bool
    {
        return in_array($movement->source, ['return_receiving', 'return_receive'], true)
            && str_starts_with($movement->notes ?? '', 'Selisih penerimaan dari ');
    }

    private function item($detail, JihansGudangStockMovement $movement): array
    {
        return ['id' => $detail->id, 'product' => $detail->product?->name ?? 'Produk tidak tersedia',
            'quantity' => (int) $detail->quantity, 'unit' => $detail->unit?->abbreviation ?? $detail->product?->unit?->abbreviation ?? '-',
            'related' => $detail->product_id === $movement->product_id];
    }
}
