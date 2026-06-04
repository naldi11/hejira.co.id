<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'entity'    => $this->entity,
            'branch_id' => $this->branch_id,
            'branch'    => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'roles'     => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'role'      => $this->whenLoaded('roles', fn () => $this->roles->first()?->name),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
