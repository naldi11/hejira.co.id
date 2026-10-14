<?php

namespace App\Http\Requests\Master;

use App\Models\User;
use App\Rules\UserBranch;
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
        $entityRoles = config('user_access.entity_roles');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:master_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'entity' => ['required', 'in:gudang,jihans,hendhys,izzi,all'],
            'branch_id' => [
                'bail',
                'nullable',
                'integer',
                new UserBranch($this->input('entity')),
            ],
            'role' => [
                'required',
                'exists:roles,name',
                // Owner hanya boleh lahir dari seeder. Penjagaan ini di sisi server,
                // bukan sekadar menyembunyikan pilihannya di form — supaya request
                // yang dikirim langsung pun tetap ditolak.
                function ($attribute, $value, $fail) {
                    if ($value === User::ROLE_OWNER) {
                        $fail('Akun owner tidak dapat dibuat dari sini. Role ini hanya bisa ditetapkan lewat seeder.');
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
