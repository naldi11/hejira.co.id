<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row in the Hendhys pending (held) transactions list. */
class HendhysPendingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'pending_number' => $this->pending_number,
            'date'           => $this->date,
            'customer_name'  => $this->customer_name ?? 'Pelanggan Umum',
            'customer_phone' => $this->customer_phone,
            'notes'          => $this->notes,
            'creator'        => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'customer'       => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'details_count'  => $this->whenCounted('details'),
        ];
    }
}
