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
            'type'              => $this->type,
            'is_prediksi'       => $this->isPrediksi(),
            'date'              => $this->date,
            'notes'             => $this->notes,
            'creator'           => $this->whenLoaded('creator', fn () => $this->creator?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product_id'        => $d->product_id,
                'product'           => $d->product?->name ?? '-',
                'product_code'      => $d->product?->code ?? '-',
                'quantity_produced' => (float) $d->quantity_produced,
                'unit_id'           => $d->unit_id,
                'unit'              => $d->unit?->abbreviation ?? 'PCS',
            ])),

            'total_items' => $this->whenLoaded('details', fn () => $this->details->count()),
        ];
    }
}
