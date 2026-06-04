<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'type'            => $this->type,
            'phone'           => $this->phone,
            'email'           => $this->email,
            'province'        => $this->province,
            'city'            => $this->city,
            'district'        => $this->district,
            'address'         => $this->address,
            'notes'           => $this->notes,
            'is_active'       => (bool) $this->is_active,
            'visible_gudang'  => (bool) $this->visible_gudang,
            'visible_jihans'  => (bool) $this->visible_jihans,
            'visible_hendhys' => (bool) $this->visible_hendhys,
        ];
    }
}
