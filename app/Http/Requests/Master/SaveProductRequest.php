<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating/updating a product.
 *
 * category_id / unit_id / brand_id accept either a numeric id or a free-text
 * name (the controller's resolveRelations() firstOrCreates by name), so they
 * are validated as strings.
 */
class SaveProductRequest extends FormRequest
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
            'name'                    => ['required', 'string', 'max:200'],
            'barcode'                 => ['nullable', 'string', 'max:50', Rule::unique('master_products', 'barcode')->ignore($this->route('product'))],
            'category_id'             => ['required', 'string'],
            'unit_id'                 => ['required', 'string'],
            'brand_id'                => ['nullable', 'string'],
            'rack'                    => ['nullable', 'string', 'max:20'],
            'hpp'                     => ['required', 'numeric', 'min:0'],
            'selling_price'           => ['required', 'numeric', 'min:0'],
            'stock_min'               => ['required', 'integer', 'min:0'],
            'ppn_type'                => ['required', 'in:none,include,exclude'],
            'ppn_rate'                => ['required', 'numeric', 'min:0', 'max:100'],
            'product_type'            => ['required', 'in:INV,NON'],
            'source_type'             => ['required', 'in:produced,purchased'],
            'status'                  => ['required', 'in:active,discontinued'],
            'visible_gudang'          => ['boolean'],
            'visible_jihans'          => ['boolean'],
            'visible_hendhys'         => ['boolean'],
            'notes'                   => ['nullable', 'string'],
            'image'                   => ['nullable', 'image', 'max:2048'],
            'tiered_prices'           => ['nullable', 'array'],
            'tiered_prices.*.min_qty' => ['required_with:tiered_prices', 'numeric', 'min:1'],
            'tiered_prices.*.price'   => ['required_with:tiered_prices', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'barcode.unique' => 'Barcode sudah terdaftar dan digunakan oleh produk lain.',
        ];
    }
}
