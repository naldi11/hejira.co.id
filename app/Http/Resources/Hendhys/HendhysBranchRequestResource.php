<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A Hendhys branch → pusat stock request. */
class HendhysBranchRequestResource extends JsonResource
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
            'status'         => $this->status,
            'notes'          => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'branch'         => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'creator'        => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'approver'       => $this->whenLoaded('approver', fn () => $this->approver?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                 => $d->id,
                'product'            => $d->product?->name ?? '-',
                'product_code'       => $d->product?->code ?? '-',
                'quantity_requested' => (float) $d->quantity_requested,
                'quantity_approved'  => $d->quantity_approved !== null ? (float) $d->quantity_approved : null,
                'unit'               => $d->unit?->abbreviation ?? 'PCS',
            ])),

            'transfer_outs' => $this->whenLoaded('transferOuts', fn () => $this->transferOuts->map(fn ($t) => [
                'id'              => $t->id,
                'transfer_number' => $t->transfer_number,
                'date'            => $t->date,
                'status'          => $t->status,
            ])),
        ];
    }
}
