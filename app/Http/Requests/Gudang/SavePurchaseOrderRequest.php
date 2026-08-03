<?php

namespace App\Http\Requests\Gudang;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for creating and updating a Purchase Order.
 */
class SavePurchaseOrderRequest extends FormRequest
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
            'supplier_id'        => ['required', 'exists:master_suppliers,id'],
            'date'               => ['required', 'date'],
            'expected_date'      => ['nullable', 'date', 'after_or_equal:date'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            // 'distinct' mencegah satu produk ditulis di dua baris PO. Produk ganda
            // membuat alokasi penerimaan jadi ambigu dan pernah mengunci 24 PO di
            // status 'partial' selamanya karena baris keduanya tak pernah terpenuhi.
            'items.*.product_id' => ['required', 'exists:master_products,id', 'distinct'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_id'    => ['required', 'exists:master_units,id'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditulis lebih dari satu baris. Gabungkan jumlahnya menjadi satu baris.',
        ];
    }
}
