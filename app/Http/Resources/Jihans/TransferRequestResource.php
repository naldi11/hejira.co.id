<?php

namespace App\Http\Resources\Jihans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A Jihans → Gudang stock request. Details/shipments only when eager-loaded. */
class TransferRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'request_number' => $this->request_number,
            'date'           => $this->date?->format('Y-m-d'),
            'status'         => $this->status,
            'notes'          => $this->notes,
            'creator'        => $this->whenLoaded('creator', fn () => $this->creator?->name),

            'details'        => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'product'            => $d->product?->name ?? '-',
                'product_code'       => $d->product?->code ?? '-',
                'quantity_requested' => (float) $d->quantity_requested,
                'quantity_approved'  => $d->quantity_approved !== null ? (float) $d->quantity_approved : null,
                'unit'               => $d->unit?->abbreviation ?? 'PCS',
            ])),

            'transfer_outs'  => $this->whenLoaded('transferOuts', fn () => $this->transferOuts->map(fn ($t) => [
                'id'              => $t->id,
                'transfer_number' => $t->transfer_number,
                'date'            => $t->date?->format('d/m/Y'),
                'status'          => $t->status,
            ])),
        ];
    }
}
