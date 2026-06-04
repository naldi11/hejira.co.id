<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTransferRequest extends FormRequest
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
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.id'                => ['required', 'integer', 'exists:gudang_transfer_request_details,id'],
            'items.*.quantity_approved' => ['required', 'numeric', 'min:0.001'],
            'notes'                     => ['nullable', 'string'],
        ];
    }
}
