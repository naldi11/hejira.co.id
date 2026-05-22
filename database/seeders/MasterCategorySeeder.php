<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCategorySeeder extends Seeder
{
    public function run(): void
    {
        

        $categories = [
            // Kategori Hendhys & Umum
            ['name' => 'Bolu', 'entity' => 'all'],
            ['name' => 'Blackforest', 'entity' => 'all'],
            ['name' => 'Roti', 'entity' => 'all'],
            ['name' => 'Frozen Food', 'entity' => 'all'],
            ['name' => 'Snack', 'entity' => 'all'],
            ['name' => 'Selai', 'entity' => 'all'],
            ['name' => 'Minuman', 'entity' => 'all'],
            ['name' => 'Property', 'entity' => 'all'],
            // Kategori Jihans
            ['name' => 'Tortilla', 'entity' => 'all'],
            ['name' => 'Kebab', 'entity' => 'all'],
            // Kategori Gudang / Manufaktur
            ['name' => 'Bahan Baku', 'entity' => 'all'],
            ['name' => 'Packaging', 'entity' => 'all'],
        ];

        foreach ($categories as $category) {
            DB::table('master_product_categories')->updateOrInsert(
                ['name' => $category['name']],
                ['entity' => $category['entity'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
