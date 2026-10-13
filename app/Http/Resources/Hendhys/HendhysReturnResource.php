<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A return from branch to pusat. */
class HendhysReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'return_number' => $this->return_number,
            'date'          => $this->date,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'branch'        => $this->whenLoaded('branch', fn () => $this->origin_name ?? $this->branch?->name),
            'creator'       => $this->whenLoaded('creator', fn () => $this->sender_name ?? $this->creator?->name),
            'receiver'      => $this->whenLoaded('receiver', fn () => $this->receiver?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'        => $d->id,
                'product'   => $d->product_name ?? $d->product?->name ?? '-',
                'product_code' => $d->product_code ?? $d->product?->code ?? '-',
                'quantity'  => (int) $d->quantity,
                'unit'      => $d->unit_name ?? $d->unit?->abbreviation ?? 'PCS',
                'condition' => $d->condition,
                'notes' => $d->notes,
            ])),
        ];
    }
}
