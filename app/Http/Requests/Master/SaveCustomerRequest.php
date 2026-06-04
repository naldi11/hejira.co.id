<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

/** Shared validation for creating/updating a customer (all entity scopes). */
class SaveCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:150'],
            'type'            => ['nullable', 'string'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:100'],
            'province'        => ['nullable', 'string', 'max:100'],
            'city'            => ['nullable', 'string', 'max:100'],
            'district'        => ['nullable', 'string', 'max:100'],
            'address'         => ['nullable', 'string'],
            'notes'           => ['nullable', 'string'],
            'is_active'       => ['boolean'],
            'entity_scope'    => ['nullable', 'in:all,gudang,jihans,hendhys'],
            'visible_gudang'  => ['boolean'],
            'visible_jihans'  => ['boolean'],
            'visible_hendhys' => ['boolean'],
        ];
    }
}
