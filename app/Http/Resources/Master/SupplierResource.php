<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name'           => $this->name,
            'contact_person' => $this->contact_person,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'address'        => $this->address,
            'notes'          => $this->notes,
            'is_active'      => (bool) $this->is_active,
        ];
    }
}
