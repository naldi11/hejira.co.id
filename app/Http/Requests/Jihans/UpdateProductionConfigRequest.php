<?php

namespace App\Http\Requests\Jihans;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionConfigRequest extends FormRequest
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
            'tb_product_id'     => ['nullable', 'exists:master_products,id'],
            'ts_product_id'     => ['nullable', 'exists:master_products,id'],
            'tk_product_id'     => ['nullable', 'exists:master_products,id'],
            'tc_product_id'     => ['nullable', 'exists:master_products,id'],
            'kribab_product_id' => ['nullable', 'exists:master_products,id'],
        ];
    }
}
