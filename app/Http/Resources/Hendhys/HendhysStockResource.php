<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the Hendhys stock listing: a product joined with its current
 * stock balance (pusat or branch, aliased as `current_stock` by the query).
 */
class HendhysStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $qty = (float) ($this->current_stock ?? 0);

        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'jenis'         => $this->jenis,
            'category'      => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit_id'       => $this->unit_id,
            'unit'          => $this->whenLoaded('unit', fn () => $this->unit?->abbreviation ?? 'PCS'),
            'stock_min'     => (float) $this->stock_min,
            'current_stock' => $qty,
            'return_stock'  => (float) ($this->return_stock ?? 0),
            'is_low'        => $qty <= (float) $this->stock_min,
            'branch_id'     => $this->branch_id ?? null,
        ];
    }
}
