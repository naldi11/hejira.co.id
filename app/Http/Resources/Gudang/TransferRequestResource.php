<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transfer request from a business unit (Jihans/Hendhys) awaiting Gudang review.
 * Details + audit fields are only included when eager-loaded (show page).
 */
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
            'date'           => $this->date,
            'from_entity'    => $this->from_entity,
            'branch'         => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'status'         => $this->status,
            'notes'          => $this->notes,
            'requester'      => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'created_at'     => $this->created_at?->format('d/m/Y H:i'),

            'approver'       => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at'    => $this->when(isset($this->approved_at), fn () => $this->approved_at?->format('d/m/Y H:i')),

            'details'        => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product'           => $d->product?->name ?? '-',
                'product_code'      => $d->product?->code ?? '-',
                'unit'              => $d->unit?->abbreviation ?? 'PCS',
                'quantity_requested' => (float) $d->quantity_requested,
                'quantity_approved' => $d->quantity_approved !== null ? (float) $d->quantity_approved : null,
                // Attached in the controller to avoid an N+1 stock lookup per row.
                'warehouse_stock'   => (float) ($d->warehouse_stock ?? 0),
            ])),
        ];
    }
}
