<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = DB::table('master_branches')->where('code', 'HND-PST')->value('id');
        $cb1   = DB::table('master_branches')->where('code', 'HND-CB1')->value('id');
        $cb2   = DB::table('master_branches')->where('code', 'HND-CB2')->value('id');

        $users = [
            [
                'name'     => 'Owner',
                'email'    => 'owner@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'all',
                'branch_id'=> null,
                'role'     => 'owner',
            ],
            [
                'name'     => 'Admin Gudang',
                'email'    => 'admin.gudang@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'gudang',
                'branch_id'=> null,
                'role'     => 'admin_gudang',
            ],
            [
                'name'     => 'Kasir Jihan\'s',
                'email'    => 'kasir.jihans@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'jihans',
                'branch_id'=> null,
                'role'     => 'kasir_jihans',
            ],
            [
                'name'     => 'Admin Jihan\'s',
                'email'    => 'admin.jihans@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'jihans',
                'branch_id'=> null,
                'role'     => 'admin_jihans',
            ],
            [
                'name'     => 'Super Admin Jihan\'s',
                'email'    => 'super.admin.jihans@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'jihans',
                'branch_id'=> null,
                'role'     => 'super_admin_jihans',
            ],
            [
                'name'     => 'Kasir Hendhys Pusat',
                'email'    => 'kasir.hendhys.pusat@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'hendhys',
                'branch_id'=> $pusat,
                'role'     => 'kasir_hendhys',
            ],
            [
                'name'     => 'Admin Hendhys',
                'email'    => 'admin.hendhys@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'hendhys',
                'branch_id'=> $pusat,
                'role'     => 'admin_hendhys',
            ],
            [
                'name'     => 'Super Admin Hendhys',
                'email'    => 'super.admin.hendhys@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'hendhys',
                'branch_id'=> $pusat,
                'role'     => 'super_admin_hendhys',
            ],
            [
                'name'     => 'Kasir Hendhys Cabang 1',
                'email'    => 'kasir.hendhys.cb1@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'hendhys',
                'branch_id'=> $cb1,
                'role'     => 'kasir_hendhys',
            ],
            [
                'name'     => 'Kasir Hendhys Cabang 2',
                'email'    => 'kasir.hendhys.cb2@gmail.com',
                'password' => Hash::make('password'),
                'entity'   => 'hendhys',
                'branch_id'=> $cb2,
                'role'     => 'kasir_hendhys',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );

            $user->syncRoles([$role]);
        }
    }
}
