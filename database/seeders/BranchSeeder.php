<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code'       => 'HND-PST',
                'name'       => 'Hendhys Pusat',
                'type'       => 'pusat',
                'address'    => null,
                'phone'      => null,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code'       => 'HND-CB1',
                'name'       => 'Hendhys Cabang 1',
                'type'       => 'cabang',
                'address'    => null,
                'phone'      => null,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code'       => 'HND-CB2',
                'name'       => 'Hendhys Cabang 2',
                'type'       => 'cabang',
                'address'    => null,
                'phone'      => null,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('master_branches')->insertOrIgnore($branches);
    }
}
