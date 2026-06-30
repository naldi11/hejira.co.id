<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Outbound transfer (delivery order) from the warehouse to a business unit.
 * Detail rows are only present when eager-loaded (show page).
 */
class TransferOutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'transfer_number' => $this->transfer_number,
            'date'            => $this->date,
            'to_entity'       => $this->to_entity,
            'branch'          => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'creator'         => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'notes'           => $this->notes,
            'request'         => $this->whenLoaded('request', fn () => $this->request ? [
                'id'             => $this->request->id,
                'request_number' => $this->request->request_number,
            ] : null),
            'details'         => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'product'           => $d->product?->name ?? '(Produk Dihapus)',
                'quantity'          => (float) $d->quantity,
                'received_quantity' => $d->received_quantity !== null ? (float) $d->received_quantity : null,
                'kondisi'           => $d->kondisi,
                'unit'              => $d->unit?->abbreviation ?? '-',
                'hpp_price'         => (float) $d->hpp_price,
                'total'             => (float) $d->total,
            ])->values()->all()),
            'grand_total'     => $this->whenLoaded('details', fn () => (float) $this->details->sum('total')) ?? 0,
        ];
    }
}
