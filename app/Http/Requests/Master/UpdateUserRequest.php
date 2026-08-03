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
                // Role owner terkunci dua arah: tidak bisa diberikan ke orang lain,
                // dan tidak bisa dicabut dari pemegangnya lewat form. Ia hanya
                // ditetapkan oleh seeder.
                function ($attribute, $value, $fail) {
                    $target     = $this->route('user');
                    $isOwnerNow = $target && $target->hasRole(\App\Models\User::ROLE_OWNER);

                    if ($value === \App\Models\User::ROLE_OWNER && ! $isOwnerNow) {
                        $fail('Role owner tidak dapat diberikan lewat form. Role ini hanya bisa ditetapkan lewat seeder.');
                    }

                    if ($isOwnerNow && $value !== \App\Models\User::ROLE_OWNER) {
                        $fail('Role owner tidak dapat diubah lewat form.');
                    }
                },
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
