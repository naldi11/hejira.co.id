<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceivingRequest extends FormRequest
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
            'received_by_name'  => ['nullable', 'string', 'max:100'],
            'supplier_rep_name' => ['nullable', 'string', 'max:100'],
            'kendala'           => ['nullable', 'string'],
            'notes'             => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.detail_id' => ['required', 'exists:gudang_receiving_details,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0'],
            'items.*.hpp_price' => ['required', 'numeric', 'min:0'],
            'items.*.kondisi'   => ['nullable', 'in:baik,rusak,kurang'],
            'items.*.notes'     => ['nullable', 'string'],
        ];
    }
}
