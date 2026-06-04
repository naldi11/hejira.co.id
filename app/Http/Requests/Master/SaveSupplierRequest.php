<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

/** Shared validation for creating/updating a supplier (all entity scopes). */
class SaveSupplierRequest extends FormRequest
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
            'name'           => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'email'          => ['nullable', 'email', 'max:100'],
            'address'        => ['nullable', 'string'],
            'notes'          => ['nullable', 'string'],
            'is_active'      => ['boolean'],
            'entity_scope'   => ['nullable', 'in:gudang,jihans,hendhys,all'],
        ];
    }
}
