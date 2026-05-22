<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GudangTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $products = DB::table('master_products')
                      ->whereIn('entity_scope', ['gudang', 'all'])
                      ->get();

        foreach ($products as $product) {
            DB::table('gudang_stock')->updateOrInsert(
                ['product_id' => $product->id],
                [
                    'quantity' => rand(50, 200),
                    'unit_id' => $product->unit_id,
                    'last_updated' => now()
                ]
            );
        }
    }
}
