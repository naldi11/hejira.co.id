<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row in the Hendhys stock-movement ledger (Kartu Stok). */
class HendhysStockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'created_at'   => $this->created_at?->format('d M Y, H:i'),
            'product'      => $this->whenLoaded('product', fn () => $this->product?->name ?? 'Produk Dihapus'),
            'type'         => $this->type,
            'quantity'     => (float) $this->quantity,
            'source'       => $this->source,
            'reference_id' => $this->reference_id,
            'notes'        => $this->notes,
            'branch_id'    => $this->branch_id,
            'operator'     => $this->whenLoaded('creator', fn () => $this->creator?->name ?? 'Sistem'),
        ];
    }
}
