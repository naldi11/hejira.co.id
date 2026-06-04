<?php

namespace App\Http\Resources\Jihans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A sellable product for the Jihans POS (with current Jihans stock + tiered prices). */
class PosProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'barcode'       => $this->barcode,
            'name'          => $this->name,
            'selling_price' => (float) $this->selling_price,
            'current_stock' => (float) ($this->current_stock ?? 0),
            'unit'          => $this->whenLoaded('unit', fn () => $this->unit?->abbreviation ?? 'PCS'),
            'tiered_prices' => $this->whenLoaded('tieredPrices', fn () => $this->tieredPrices->map(fn ($t) => [
                'min_qty' => (float) $t->min_qty,
                'price'   => (float) $t->price,
            ])->values()),
        ];
    }
}
