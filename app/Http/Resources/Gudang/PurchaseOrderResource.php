<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase order to a supplier. Line items, receipt history and audit fields
 * are only included when eager-loaded (show page).
 */
class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'po_number'     => $this->po_number,
            'date'          => $this->date?->format('Y-m-d'),
            'expected_date' => $this->expected_date?->format('Y-m-d'),
            'status'        => $this->status,
            'notes'         => $this->notes,
            'total_amount'  => (float) $this->total_amount,
            'supplier'      => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'supplier_id'   => $this->supplier_id,
            'creator'       => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at'    => $this->when(isset($this->created_at), fn () => $this->created_at?->format('d M Y, H:i')),

            'details'       => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'product'           => $d->product?->name ?? '-',
                'product_id'        => $d->product_id,
                'notes'             => $d->notes,
                'quantity_ordered'  => (int) $d->quantity_ordered,
                'quantity_received' => (int) $d->quantity_received,
                'unit'              => $d->unit?->abbreviation ?? '-',
                'unit_id'           => $d->unit_id,
                'price'             => (float) $d->price,
                'total'             => (float) $d->total,
            ])),

            'receivings'    => $this->whenLoaded('receivings', fn () => $this->receivings->map(fn ($grn) => [
                'id'            => $grn->id,
                'grn_number'    => $grn->grn_number,
                'date'          => $grn->date?->format('d M Y'),
                'details_count' => $grn->details->count(),
            ])),
        ];
    }
}
