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
            'address'   => ['nullable', 'string'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
