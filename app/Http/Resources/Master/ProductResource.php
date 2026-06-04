<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'barcode'       => $this->barcode,
            'category'      => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit'          => $this->whenLoaded('unit', fn () => $this->unit?->abbreviation),
            'unit_name'     => $this->whenLoaded('unit', fn () => $this->unit?->name),
            'brand'         => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'rack'          => $this->rack,
            'hpp'           => (float) $this->hpp,
            'selling_price' => (float) $this->selling_price,
            'stock_min'     => (int) $this->stock_min,
            'ppn_type'      => $this->ppn_type,
            'ppn_rate'      => (float) $this->ppn_rate,
            'product_type'  => $this->product_type,
            'source_type'   => $this->source_type,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'image_url'     => $this->image ? Storage::url($this->image) : null,
            'visible_gudang'  => (bool) $this->visible_gudang,
            'visible_jihans'  => (bool) $this->visible_jihans,
            'visible_hendhys' => (bool) $this->visible_hendhys,
            'tiered_prices' => $this->whenLoaded('tieredPrices', fn () => $this->tieredPrices->map(fn ($t) => [
                'min_qty' => (float) $t->min_qty,
                'price'   => (float) $t->price,
            ])->values()),
        ];
    }
}
