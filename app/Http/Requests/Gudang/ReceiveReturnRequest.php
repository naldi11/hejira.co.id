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
            'items.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.condition'         => ['required', 'string', 'max:100'],
        ];
    }
}
