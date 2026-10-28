<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'to_entity'          => ['required', 'in:jihans,hendhys,izzi'],
            'request_key'        => ['required_if:to_entity,izzi', 'nullable', 'uuid'],
            'branch_id'          => ['nullable', 'required_if:to_entity,hendhys,izzi', Rule::exists('master_branches', 'id')->where('entity', $this->input('to_entity'))->where('is_active', true)],
            'date'               => ['required', 'date'],
            'request_id'         => ['nullable', 'prohibited_if:to_entity,izzi', 'exists:gudang_transfer_requests,id'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999999999'],
            'items.*.unit_id'    => ['required', 'exists:master_units,id'],
            'items.*.production_lot_id' => ['nullable', 'integer', 'exists:production_lots,id'],
            'items.*.hpp_price'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
