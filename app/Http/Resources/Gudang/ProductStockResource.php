<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the Gudang stock listing: a product joined with its current
 * warehouse balance (aliased as `current_stock` by the query).
 */
class ProductStockResource extends JsonResource
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
            'is_low'        => $qty <= (float) $this->stock_min,
            'gudang_stock'  => (float) ($this->gudang_stock ?? 0),
            'returned_defect_stock' => (float) ($this->returned_defect_stock ?? 0),
            'returned_expired_stock' => (float) ($this->returned_expired_stock ?? 0),
        ];
    }
}
