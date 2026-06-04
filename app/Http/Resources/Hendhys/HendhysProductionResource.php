<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One Hendhys production batch with details when loaded. */
class HendhysProductionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'production_number' => $this->production_number,
            'date'              => $this->date,
            'notes'             => $this->notes,
            'creator'           => $this->whenLoaded('creator', fn () => $this->creator?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product'           => $d->product?->name ?? '-',
                'product_code'      => $d->product?->code ?? '-',
                'quantity_produced' => (float) $d->quantity_produced,
                'unit'              => $d->unit?->abbreviation ?? 'PCS',
            ])),

            'total_items' => $this->whenLoaded('details', fn () => $this->details->count()),
        ];
    }
}
