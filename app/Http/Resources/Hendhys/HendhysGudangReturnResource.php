<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A return from Hendhys pusat back to Gudang Utama. Reuses GudangReturn model. */
class HendhysGudangReturnResource extends JsonResource
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
            'from_entity'   => $this->from_entity,
            'branch'        => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'creator'       => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'receiver'      => $this->whenLoaded('receiver', fn () => $this->receiver?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'        => $d->id,
                'product'   => $d->product?->name ?? '-',
                'product_code' => $d->product?->code ?? '-',
                'quantity'  => (float) $d->quantity,
                'unit'      => $d->unit?->abbreviation ?? 'PCS',
                'condition' => $d->condition,
                'notes'     => $d->notes,
            ])),
        ];
    }
}
