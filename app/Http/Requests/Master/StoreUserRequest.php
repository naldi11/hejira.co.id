<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            // super_admin mengawasi Gudang Utama + seluruh unit, jadi ia ditempatkan
            // pada entity 'all' (dan 'gudang' tetap diterima untuk kompatibilitas).
            'all'     => ['super_admin', 'owner'],
            'gudang'  => ['super_admin'],
            'hendhys' => ['kasir_hendhys', 'admin_hendhys'],
            'jihans'  => ['kasir_jihans', 'admin_jihans'],
            'owner'   => ['owner'],
        ];

        return [
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'string', 'email', 'max:100', 'unique:master_users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
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
