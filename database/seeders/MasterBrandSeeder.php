<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterBrandSeeder extends Seeder
{
    public function run(): void
    {
        

        $brands = [
            'Tanpa Brand',
            'Hendhys',
            "Jihan's Food",
            'Fiesta',
            'Champ',
            'Belfoods',
            'Cedea',
            'Sari Roti',
            'Pondan'
        ];

        foreach ($brands as $brand) {
            DB::table('master_brands')->updateOrInsert(
                ['name' => $brand],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
