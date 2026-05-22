<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HendhysTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $products = DB::table('master_products')
                      ->whereIn('entity_scope', ['hendhys', 'all'])
                      ->get();

        $branches = DB::table('master_branches')
                      ->where('code', 'like', 'HND-%')
                      ->where('type', 'cabang')
                      ->get();

        foreach ($products as $product) {
            // Stok Pusat
            DB::table('hendhys_stock_pusat')->updateOrInsert(
                ['product_id' => $product->id],
                [
                    'quantity' => rand(20, 100),
                    'unit_id' => $product->unit_id,
                    'last_updated' => now()
                ]
            );

            // Stok Cabang
            foreach ($branches as $branch) {
                DB::table('hendhys_stock_branch')->updateOrInsert(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    [
                        'quantity' => rand(5, 30),
                        'unit_id' => $product->unit_id,
                        'last_updated' => now()
                    ]
                );
            }
        }
    }
}
