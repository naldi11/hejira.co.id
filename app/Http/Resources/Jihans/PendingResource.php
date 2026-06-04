<?php

namespace App\Http\Resources\Jihans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingResource extends JsonResource
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
            'customer_name'  => $this->customer_name,
            'customer_type'  => $this->customer_type,
            'notes'          => $this->notes,
            'creator'        => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'items_count'    => $this->whenCounted('details'),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
