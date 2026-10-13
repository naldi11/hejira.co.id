<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A return from Hendhys pusat back to Gudang Utama. Reuses GudangReturn model. */
class HendhysGudangReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'return_number' => $this->return_number,
            'date'          => $this->date?->toDateString(),
            'recorded_at'   => $this->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s \W\I\B'),
            'status'        => $this->status,
            'notes'         => $this->notes,
            'from_entity'   => $this->from_entity,
            'branch'        => $this->whenLoaded('branch', fn () => $this->origin_name ?? $this->branch?->name),
            'creator'       => $this->whenLoaded('creator', fn () => $this->sender_name ?? $this->creator?->name),
            'receiver'      => $this->whenLoaded('receiver', fn () => $this->receiver?->name),

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'        => $d->id,
                'product'   => $d->product_name ?? $d->product?->name ?? '-',
                'product_code' => $d->product_code ?? $d->product?->code ?? '-',
                'quantity'  => (int) $d->quantity,
                'received_quantity' => $d->received_quantity !== null ? (int) $d->received_quantity : null,
                'unit'      => $d->unit_name ?? $d->unit?->abbreviation ?? 'PCS',
                'condition' => $d->condition,
                'sent_condition' => $d->sent_condition,
                'receiving_notes' => $d->receiving_notes,
                'condition_kind' => \App\Services\ReturnQuarantine::condition($d->sent_condition ?? $d->condition),
                'notes'     => $d->notes,
            ])),
        ];
    }
}
