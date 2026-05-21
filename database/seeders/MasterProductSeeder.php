<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterProductSeeder extends Seeder
{
    public function run(): void
    {
        $prefixes = ['gudang', 'jihans', 'hendhys'];
        foreach ($prefixes as $prefix) {

        // Helper to get ID by name
        $getCatId = fn($name) => DB::table($prefix . '_product_categories')->where('name', $name)->value('id');
        $getUnitId = fn($abbr) => DB::table($prefix . '_units')->where('abbreviation', $abbr)->value('id');
        $getBrandId = fn($name) => DB::table($prefix . '_brands')->where('name', $name)->value('id');

        // Categories
        $catSnack = $getCatId('Snack');
        $catBolu = $getCatId('Bolu');
        $catFrozen = $getCatId('Frozen Food');
        $catTortilla = $getCatId('Tortilla');
        $catBahan = $getCatId('Bahan Baku');

        // Units
        $uPcs = $getUnitId('PCS');
        $uPak = $getUnitId('PAK');
        $uKg = $getUnitId('KG');

        // Brands
        $bHendhys = $getBrandId('Hendhys');
        $bJihans = $getBrandId("Jihan's Food");
        $bTanpa = $getBrandId('Tanpa Brand');
        $bFiesta = $getBrandId('Fiesta');

        $products = [
            // Produk Hendhys (Dari Invoice)
            ['code' => 'PRD-001', 'name' => 'Macaroni', 'jenis' => 'snack', 'entity_scope' => 'hendhys', 'category_id' => $catSnack, 'unit_id' => $uPcs, 'brand_id' => $bHendhys, 'hpp' => 1000, 'selling_price' => 2000],
            ['code' => 'PRD-002', 'name' => 'Lapis', 'jenis' => 'bakery', 'entity_scope' => 'hendhys', 'category_id' => $catBolu, 'unit_id' => $uPcs, 'brand_id' => $bHendhys, 'hpp' => 1200, 'selling_price' => 2000],
            ['code' => 'PRD-003', 'name' => 'Dodol Pandan Wijen', 'jenis' => 'snack', 'entity_scope' => 'hendhys', 'category_id' => $catSnack, 'unit_id' => $uPcs, 'brand_id' => $bHendhys, 'hpp' => 1500, 'selling_price' => 2000],
            ['code' => 'PRD-004', 'name' => 'Brownies Kukus Coklat', 'jenis' => 'bakery', 'entity_scope' => 'hendhys', 'category_id' => $catBolu, 'unit_id' => $uPcs, 'brand_id' => $bHendhys, 'hpp' => 25000, 'selling_price' => 45000],

            // Produk Jihan's Food
            ['code' => 'PRD-005', 'name' => 'Tortilla Sedang', 'jenis' => 'tortilla', 'entity_scope' => 'jihans', 'category_id' => $catTortilla, 'unit_id' => $uPak, 'brand_id' => $bJihans, 'hpp' => 15000, 'selling_price' => 22000],
            ['code' => 'PRD-006', 'name' => 'Tortilla Besar', 'jenis' => 'tortilla', 'entity_scope' => 'jihans', 'category_id' => $catTortilla, 'unit_id' => $uPak, 'brand_id' => $bJihans, 'hpp' => 18000, 'selling_price' => 26000],
            ['code' => 'PRD-007', 'name' => 'Daging Kebab 2Kg', 'jenis' => 'frozen', 'entity_scope' => 'jihans', 'category_id' => $catFrozen, 'unit_id' => $uPcs, 'brand_id' => $bTanpa, 'hpp' => 100000, 'selling_price' => 137000],

            // Produk Gudang / Umum (Frozen Food)
            ['code' => 'PRD-008', 'name' => 'Ayam Fillet', 'jenis' => 'frozen', 'entity_scope' => 'gudang', 'category_id' => $catFrozen, 'unit_id' => $uKg, 'brand_id' => $bTanpa, 'hpp' => 38000, 'selling_price' => 45000],
            ['code' => 'PRD-009', 'name' => 'Shifudo Otak Otak Ikan', 'jenis' => 'frozen', 'entity_scope' => 'gudang', 'category_id' => $catFrozen, 'unit_id' => $uPcs, 'brand_id' => $bTanpa, 'hpp' => 18000, 'selling_price' => 24000],
            ['code' => 'PRD-010', 'name' => 'Fiesta Chicken Nugget 500gr', 'jenis' => 'frozen', 'entity_scope' => 'all', 'category_id' => $catFrozen, 'unit_id' => $uPcs, 'brand_id' => $bFiesta, 'hpp' => 45000, 'selling_price' => 52000],
        ];

        foreach ($products as $product) {
            DB::table($prefix . '_products')->updateOrInsert(
                ['code' => $product['code']],
                array_merge($product, ['created_at' => now(), 'updated_at' => now()])
            );
        }
        }
    }
}
