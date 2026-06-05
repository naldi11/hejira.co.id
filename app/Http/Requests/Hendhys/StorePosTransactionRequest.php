<?php

namespace App\Http\Requests\Hendhys;

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
            'customer_type'      => ['nullable', 'string'],
            'customer_phone'     => ['nullable', 'string', 'max:20'],
            'payment_method_id'  => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !\Illuminate\Support\Facades\DB::table('master_payment_methods')->where('id', $value)->exists()) {
                        $fail('The selected payment method id is invalid.');
                    }
                }
            ],
            'amount_paid'        => ['required', 'numeric', 'min:0'],
            'ppn_type'           => ['nullable', 'string', 'in:none,include,exclude'],
            'tax_amount'         => ['nullable', 'numeric', 'min:0'],
            'other_costs'        => ['nullable', 'numeric', 'min:0'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
