<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Shared validation for creating/updating a branch (outlet). */
class SaveBranchRequest extends FormRequest
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
        $branchId = $this->route('branch')?->id;

        return [
            'code'      => ['required', 'string', 'max:20', Rule::unique('master_branches', 'code')->ignore($branchId)],
            'name'      => ['required', 'string', 'max:100'],
            'type'      => ['required', 'in:pusat,cabang'],
            'entity'    => ['required', 'in:hendhys,jihans,gudang,izzi', function ($attribute, $value, $fail) {
                $branch = $this->route('branch');
                if (!$branch || $branch->entity === $value) return;
                if (\App\Models\TransferOut::where('branch_id', $branch->id)->exists()) {
                    $fail('Unit cabang tidak dapat diganti karena sudah memiliki riwayat pengiriman Gudang. Buat cabang baru agar dokumen dan penerimaan tetap sesuai.'); return;
                }
                if (\Illuminate\Support\Facades\DB::table('branch_stock_transfers')->where(fn ($q) => $q->where('from_branch_id', $branch->id)->orWhere('to_branch_id', $branch->id))->exists()) {
                    $fail('Unit cabang tidak dapat diganti karena sudah memiliki riwayat mutasi. Buat cabang baru agar saldo dan jejak barang tetap benar.'); return;
                }
                if (!in_array('izzi', [$branch->entity, $value], true)) return;
                $used = $branch->users()->exists() || \App\Models\CashierShift::where('branch_id', $branch->id)->exists();
                foreach (['izzi_stock','izzi_sales','izzi_receipts','hendhys_stock_branch','hendhys_transactions'] as $table) {
                    if (\Illuminate\Support\Facades\DB::table($table)->where('branch_id', $branch->id)->exists()) $used = true;
                }
                if ($used) $fail('Cabang yang sudah memiliki pengguna, stok, atau riwayat tidak dapat dipindah ke unit lain. Buat cabang baru untuk Izzi Kebab.');
            }],
            'address'   => ['nullable', 'string'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
