<?php

namespace App\Http\Resources\Jihans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'transaction_number' => $this->transaction_number,
            'customer_name'      => $this->customer_name,
            'customer_type'      => $this->customer_type,
            'grand_total'        => (float) $this->grand_total,
            'status'             => $this->status,
            'created_at'         => $this->created_at?->toIso8601String(),
            'creator'            => $this->whenLoaded('creator', fn () => $this->creator?->name),
        ];
    }
}
