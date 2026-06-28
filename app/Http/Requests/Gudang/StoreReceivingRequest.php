<?php

namespace App\Http\Requests\Gudang;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class StoreReceivingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise input before validation:
     *  - derive supplier_id from the chosen PO (the supplier picker is disabled client-side)
     *  - coerce blank batch/expiry strings to null so the `date` rule passes
     */
    protected function prepareForValidation(): void
    {
        dd($this->all());

        if ($this->filled('po_id')) {
            $po = PurchaseOrder::find($this->input('po_id'));
            if ($po) {
                $this->merge(['supplier_id' => $po->supplier_id]);
            }
        }

        if ($this->has('items')) {
            $items = $this->input('items');
            foreach ($items as $k => $item) {
                if (isset($item['expired_date']) && trim((string) $item['expired_date']) === '') {
                    $items[$k]['expired_date'] = null;
                }
                if (isset($item['batch_number']) && trim((string) $item['batch_number']) === '') {
                    $items[$k]['batch_number'] = null;
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id'            => ['required', 'exists:master_suppliers,id'],
            'date'                   => ['required', 'date'],
            'po_id'                  => ['nullable', 'exists:gudang_purchase_orders,id'],
            'notes'                  => ['nullable', 'string'],
            'received_by_name'       => ['nullable', 'string', 'max:100'],
            'supplier_rep_name'      => ['nullable', 'string', 'max:100'],
            'kendala'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:master_products,id'],
            'items.*.quantity_bagus' => ['required', 'numeric', 'min:0'],
            'items.*.quantity_rusak' => ['required', 'numeric', 'min:0'],
            'items.*.unit_id'        => ['required', 'exists:master_units,id'],
            'items.*.hpp_price'      => ['required', 'numeric', 'min:0'],
            'items.*.expired_date'   => ['nullable', 'date'],
            'items.*.batch_number'   => ['nullable', 'string', 'max:50'],
            'items.*.notes'          => ['nullable', 'string'],
            'photos'                 => ['nullable', 'array', 'max:10'],
            'photos.*'               => ['image', 'max:5120'],
            'photo_urls'             => ['nullable', 'array'],
            'photo_urls.*'           => ['string'],
        ];
    }
}
