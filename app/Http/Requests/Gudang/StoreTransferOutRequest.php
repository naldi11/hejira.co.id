<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferOutRequest extends FormRequest
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
            'to_entity'          => ['required', 'in:jihans,hendhys'],
            'branch_id'          => ['nullable', 'required_if:to_entity,hendhys', 'exists:master_branches,id'],
            'date'               => ['required', 'date'],
            'request_id'         => ['nullable', 'exists:gudang_transfer_requests,id'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_id'    => ['required', 'exists:master_units,id'],
            'items.*.hpp_price'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
