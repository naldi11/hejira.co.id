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
            'notes'              => ['required', 'string', 'min:5', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_id'    => ['required', 'exists:master_units,id'],
            'items.*.condition'  => ['required', 'in:baik,rusak,kadaluarsa,kadaluwarsa,Rusak,Bagus (Siap Jual),Rusak (Defect),Kadaluwarsa'],
            'items.*.notes'      => ['nullable', 'string'],
        ];
    }
}
