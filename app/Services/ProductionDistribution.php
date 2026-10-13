<?php

namespace App\Services;

use App\Models\HendhysTransferToBranch;
use App\Models\TransferOut;
use Illuminate\Support\Facades\DB;

class ProductionDistribution
{
    public function history($production, string $entity, ?string $date = null)
    {
        if ($production->type !== 'aktual') return null;
        $source = $entity === 'hendhys' ? 'transfer_to_branch' : 'transfer_out';
        $allocations = DB::table('production_lot_outflows as o')->join('production_lots as l', 'l.id', '=', 'o.lot_id')
            ->where('l.entity', $entity)->where('l.production_id', $production->id)->where('o.source', $source)
            ->select('o.*', 'l.product_id')->get()->groupBy('reference_id');
        $model = $entity === 'hendhys' ? HendhysTransferToBranch::class : TransferOut::class;
        $transfers = $model::query()->whereIn('id', $allocations->keys())->whereIn('status', ['sent', 'received'])
            ->when($date, fn ($q) => $q->whereDate('date', $date))
            ->with(['branch', 'creator', 'details.product', 'details.unit'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15, ['*'], 'distribution_page')->withQueryString();

        return $transfers->through(function ($transfer) use ($allocations) {
            $flows = $allocations->get($transfer->id, collect());
            return [
                'id' => $transfer->id, 'number' => $transfer->transfer_number,
                'destination' => $transfer->branch?->name ?? ($transfer->to_entity === 'jihans' ? 'Outlet Jihan’s (cabang tidak dicatat)' : 'Cabang tidak tersedia'),
                'destination_code' => $transfer->branch?->code,
                'destination_entity' => $transfer->branch?->entity ?? $transfer->to_entity,
                'date' => $transfer->date?->format('Y-m-d'),
                'sent_at' => $transfer->created_at ? $transfer->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Jam belum tercatat',
                'sender' => $transfer->creator?->name ?? 'Pengguna tidak tersedia',
                'status' => $transfer->status, 'notes' => $transfer->notes,
                'items' => $transfer->details->filter(fn ($item) => $flows->where('detail_id', $item->id)->isNotEmpty())
                    ->map(function ($item) use ($flows) {
                        $quantity = (int) $flows->where('detail_id', $item->id)->sum('quantity');
                        return [
                            'product_id' => $item->product_id, 'product' => $item->product?->name ?? 'Produk tidak tersedia',
                            'returned_quantity' => (int) $flows->where('detail_id', $item->id)->sum('restored_quantity'),
                            'quantity' => $quantity, 'document_quantity' => (int) $item->quantity,
                            // Receiving is recorded per product line, not per production lot.
                            'received_quantity' => $quantity === (int) $item->quantity ? $item->received_quantity : null,
                            'received_document_quantity' => $item->received_quantity,
                            'unit' => $item->unit?->abbreviation ?? '-',
                        ];
                    })->values()->all(),
            ];
        });
    }
}
