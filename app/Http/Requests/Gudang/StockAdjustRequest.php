<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:master_products,id'],
            'unit_id'    => ['required', 'integer', 'exists:master_units,id'],
            'quantity'   => ['required', 'integer', 'min:0'],
            'notes'      => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'Stok fisik tidak boleh negatif.',
            'notes.required' => 'Alasan penyesuaian wajib diisi.',
        ];
    }
}
