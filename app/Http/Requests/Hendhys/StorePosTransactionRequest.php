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
            // Bila penjualan berasal dari transaksi tertahan, pendingnya dihapus
            // di dalam transaksi DB yang sama — bukan lebih dulu di frontend.
            'pending_id'         => ['nullable', 'integer', 'exists:hendhys_pending_transactions,id'],
            'customer_name'      => ['nullable', 'string', 'max:150'],
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
            'payment_type'       => ['nullable', 'string', 'in:tunai,transfer,kartu_debit,kartu_kredit'],
            'reference_number'   => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string'],

            // Nilai uang wajib divalidasi: sebelumnya subtotal/grand_total/discount
            // ditulis langsung ke tabel transaksi tanpa aturan apa pun, sehingga
            // payload yang dimanipulasi bisa menyimpan total yang tidak masuk akal.
            'subtotal'           => ['required', 'numeric', 'min:0'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'grand_total'        => ['required', 'numeric', 'min:0'],
            'amount_paid'        => ['required', 'numeric', 'min:0'],
            'ppn_type'           => ['nullable', 'string', 'in:none,include,exclude'],
            'tax_amount'         => ['nullable', 'numeric', 'min:0'],
            'other_costs'        => ['nullable', 'numeric', 'min:0'],

            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:master_products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
            'items.*.discount'   => ['nullable', 'numeric', 'min:0'],
            'items.*.total'      => ['required', 'numeric', 'min:0'],
        ];
    }
}
