<?php

namespace App\Http\Requests\Master;

use App\Models\User;
use App\Rules\UserBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $entityRoles = config('user_access.entity_roles');
        $entityRoles['all'][] = 'owner';
        $entityRoles['owner'] = ['owner'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('master_users', 'email')->ignore($this->route('user')->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'entity' => ['required', 'in:gudang,jihans,hendhys,izzi,owner,all'],
            'branch_id' => [
                'bail',
                'nullable',
                'integer',
                new UserBranch($this->input('entity')),
            ],
            'role' => [
                'required',
                'exists:roles,name',
                // Role owner terkunci dua arah: tidak bisa diberikan ke orang lain,
                // dan tidak bisa dicabut dari pemegangnya lewat form. Ia hanya
                // ditetapkan oleh seeder.
                function ($attribute, $value, $fail) {
                    $target = $this->route('user');
                    $isOwnerNow = $target && $target->hasRole(User::ROLE_OWNER);

                    if ($value === User::ROLE_OWNER && ! $isOwnerNow) {
                        $fail('Role owner tidak dapat diberikan lewat form. Role ini hanya bisa ditetapkan lewat seeder.');
                    }

                    if ($isOwnerNow && $value !== User::ROLE_OWNER) {
                        $fail('Role owner tidak dapat diubah lewat form.');
                    }
                },
                function ($attribute, $value, $fail) use ($entityRoles) {
                    $entity = $this->input('entity');
                    if (is_string($entity) && isset($entityRoles[$entity]) && ! in_array($value, $entityRoles[$entity], true)) {
                        $fail('Pilih peran yang sesuai dengan unit usaha pengguna.');
                    }
                },
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Isi nama lengkap pengguna.',
            'email.required' => 'Isi email yang akan digunakan untuk masuk.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.unique' => 'Email ini sudah digunakan pengguna lain.',
            'password.required' => 'Buat kata sandi untuk pengguna.',
            'password.min' => 'Kata sandi harus terdiri dari minimal 8 karakter.',
            'password.confirmed' => 'Kedua kata sandi harus sama.',
            'entity.required' => 'Pilih unit usaha terlebih dahulu.',
            'entity.in' => 'Pilih unit usaha yang tersedia.',
            'role.required' => 'Pilih peran pengguna.',
            'role.exists' => 'Peran ini tidak tersedia. Pilih peran lain.',
        ];
    }
}
