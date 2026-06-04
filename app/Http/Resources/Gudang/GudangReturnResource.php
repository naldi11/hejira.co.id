<?php

namespace App\Http\Resources\Gudang;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Incoming return from a business unit (Jihans/Hendhys) back to the main warehouse.
 * Detail rows + audit fields are only present when eager-loaded (show page).
 */
class GudangReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'return_number'     => $this->return_number,
            'date'              => $this->date,
            'from_entity'       => $this->from_entity,
            'from_entity_label' => $this->from_entity_label,
            'branch'            => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'status'            => $this->status,
            'notes'             => $this->notes,
            'details_count'     => $this->whenCounted('details'),

            'creator'           => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'receiver'          => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'received_at'       => $this->when(isset($this->received_at), fn () => $this->received_at?->format('d M Y H:i')),

            'details'           => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product'           => $d->product?->name ?? '-',
                'unit'              => $d->unit?->abbreviation ?? 'PCS',
                'quantity'          => (float) $d->quantity,
                'received_quantity' => $d->received_quantity !== null ? (float) $d->received_quantity : null,
                'condition'         => $d->condition,
            ])),
        ];
    }
}
