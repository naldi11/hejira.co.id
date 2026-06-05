<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Goods receipt note (GRN). Detail rows, photos and audit fields are only
 * included when eager-loaded (show page).
 */
class ReceivingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'grn_number'        => $this->grn_number,
            'date'              => $this->date?->format('Y-m-d'),
            'status'            => $this->status,
            'is_open'           => $this->status === 'open',
            'supplier'          => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier_rep_name' => $this->supplier_rep_name,
            'received_by_name'  => $this->received_by_name,
            'notes'             => $this->notes,
            'kendala'           => $this->kendala,
            'closed_at'         => $this->when(isset($this->closed_at), fn () => $this->closed_at?->format('d M Y H:i')),
            'closed_by'         => $this->whenLoaded('closedBy', fn () => $this->closedBy?->name),

            'po'                => $this->whenLoaded('po', fn () => $this->po ? [
                'id'        => $this->po->id,
                'po_number' => $this->po->po_number,
            ] : null),

            'details'           => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'               => $d->id,
                'product'          => $d->product?->name ?? '-',
                'quantity_ordered' => $d->quantity_ordered !== null ? (float) $d->quantity_ordered : null,
                'quantity'         => (float) $d->quantity,
                'unit'             => $d->unit?->abbreviation ?? '-',
                'kondisi'          => $d->kondisi,
                'hpp_price'        => (float) $d->hpp_price,
                'total'            => (float) $d->total,
                'notes'            => $d->notes,
            ])),

            'photos'            => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => [
                'id'      => $p->id,
                'url'     => $p->url(),
                'caption' => $p->caption,
            ])),

            // Hanya nilai barang BAIK yang masuk ke stok yang dihitung sebagai total nilai penerimaan.
            // Barang rusak ditampilkan di tabel detail namun tidak masuk ke total nilai.
            'grand_total'       => $this->whenLoaded('details', fn () => (float) $this->details->where('kondisi', 'baik')->sum('total')),
        ];
    }
}
