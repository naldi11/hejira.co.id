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
        $entityRoles = [
            'gudang'  => ['admin_gudang'],
            'hendhys' => ['kasir_hendhys', 'admin_hendhys', 'super_admin_hendhys'],
            'jihans'  => ['kasir_jihans', 'admin_jihans', 'super_admin_jihans'],
            'owner'   => ['owner'],
        ];

        return [
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'string', 'email', 'max:100', Rule::unique('master_users', 'email')->ignore($this->route('user')->id)],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            'entity'    => ['required', 'in:gudang,jihans,hendhys,owner,all'],
            'branch_id' => [
                'nullable',
                'exists:master_branches,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $branch = \App\Models\Branch::find($value);
                        if ($branch && $branch->entity !== $this->input('entity')) {
                            $fail("Cabang penempatan tidak sesuai dengan entitas bisnis.");
                        }
                    }
                }
            ],
            'role'      => [
                'required',
                'exists:roles,name',
                function ($attribute, $value, $fail) use ($entityRoles) {
                    $entity = $this->input('entity');
                    if (isset($entityRoles[$entity]) && !in_array($value, $entityRoles[$entity])) {
                        $fail("Role yang dipilih tidak sesuai dengan entitas bisnis.");
                    }
                }
            ],
            'is_active' => ['boolean'],
        ];
    }
}
