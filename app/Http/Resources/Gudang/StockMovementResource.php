<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row in the warehouse stock-movement ledger (Kartu Stok). */
class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'created_at'   => $this->created_at ? \Illuminate\Support\Carbon::parse($this->created_at)->format('d M Y, H:i') : null,
            'product'      => $this->whenLoaded('product', fn () => $this->product?->name ?? 'Produk Dihapus'),
            'type'         => $this->type,
            'quantity'     => (float) $this->quantity,
            'source'       => $this->source,
            'reference_id' => $this->reference_id,
            'doc_number'   => $this->document_number,
            'notes'        => $this->notes,
            'operator'     => $this->whenLoaded('creator', fn () => $this->creator?->name ?? 'Sistem'),
        ];
    }
}
