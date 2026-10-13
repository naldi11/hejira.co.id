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
            'date'              => $this->date?->toDateString(),
            'recorded_at'   => $this->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B'),
            'from_entity'       => $this->from_entity,
            'from_entity_label' => $this->from_entity_label,
            'origin_name' => $this->origin_name ?? $this->branch?->name ?? 'Cabang belum tercatat',
            'branch'            => $this->whenLoaded('branch', fn () => $this->origin_name ?? $this->branch?->name),
            'status'            => $this->status,
            'notes'             => $this->notes,
            'details_count'     => $this->whenCounted('details'),

            'creator'           => $this->whenLoaded('creator', fn () => $this->sender_name ?? $this->creator?->name),
            'receiver'          => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'received_at'       => $this->when(isset($this->received_at), fn () => $this->received_at?->format('d M Y H:i')),

            'details'           => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product'           => $d->product_name ?? $d->product?->name ?? '-',
                'unit'              => $d->unit_name ?? $d->unit?->abbreviation ?? 'PCS',
                'quantity'          => (int) $d->quantity,
                'received_quantity' => $d->received_quantity !== null ? (int) $d->received_quantity : null,
                'condition'         => $d->condition,
                'sent_condition' => $d->sent_condition,
                'receiving_notes' => $d->receiving_notes,
                'condition_kind' => \App\Services\ReturnQuarantine::condition($d->sent_condition ?? $d->condition),
                'notes' => $d->notes,
            ])),
        ];
    }
}
