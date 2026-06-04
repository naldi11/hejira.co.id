<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One row in the Hendhys transaction (sales) list. */
class HendhysTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'transaction_number' => $this->transaction_number,
            'date'               => $this->date,
            'time'               => $this->time,
            'customer_name'      => $this->customer_name ?? 'Pelanggan Umum',
            'grand_total'        => (float) $this->grand_total,
            'status'             => $this->status,
            'creator'            => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'customer'           => $this->whenLoaded('customer', fn () => $this->customer?->name),
        ];
    }
}
