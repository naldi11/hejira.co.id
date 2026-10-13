<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Items are keyed by return-detail id: items[<id>][received_quantity|condition].
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
            'items.*.receiving_notes' => ['nullable', 'string', 'max:1000'],
            'items.*.condition'         => ['required', 'in:Bagus (Siap Jual),Rusak (Defect),Kadaluwarsa'],
        ];
    }
}
