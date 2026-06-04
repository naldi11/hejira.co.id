<?php

namespace App\Http\Requests\Jihans;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTransactionRequest extends FormRequest
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
            'transaction_date'   => ['nullable', 'date'],
            'customer_id'        => ['nullable', 'exists:master_customers,id'],
            'customer_name'      => ['nullable', 'string', 'max:150'],
            'customer_type'      => ['nullable', 'string'],
            'ppn_type'           => ['required', 'in:none,include,exclude'],
            'ppn_rate'           => ['required', 'numeric', 'min:0'],
            'subtotal'           => ['required', 'numeric', 'min:0'],
            'discount_amount'    => ['required', 'numeric', 'min:0'],
            'extra_discount'     => ['nullable', 'numeric', 'min:0'],
            'tax_amount'         => ['required', 'numeric', 'min:0'],
            'other_costs'        => ['required', 'numeric', 'min:0'],
            'grand_total'        => ['required', 'numeric', 'min:0'],
            'amount_paid'        => ['required', 'numeric', 'min:0'],
            'reference_number'   => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
            'items.*.discount'   => ['nullable', 'numeric', 'min:0'],
            'items.*.total'      => ['required', 'numeric', 'min:0'],
        ];
    }
}
