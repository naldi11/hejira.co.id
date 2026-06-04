<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'string', 'email', 'max:100', Rule::unique('master_users', 'email')->ignore($this->route('user')->id)],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            'entity'    => ['required', 'in:gudang,jihans,hendhys,owner,all'],
            'branch_id' => ['nullable', 'exists:master_branches,id'],
            'role'      => ['required', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ];
    }
}
