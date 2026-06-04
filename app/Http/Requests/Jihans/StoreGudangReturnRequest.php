<?php

namespace App\Http\Requests\Jihans;

use Illuminate\Foundation\Http\FormRequest;

class StoreGudangReturnRequest extends FormRequest
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
            'date'               => ['required', 'date'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_id'    => ['required', 'exists:master_units,id'],
            'items.*.condition'  => ['required', 'string', 'max:100'],
            'items.*.notes'      => ['nullable', 'string'],
        ];
    }
}
