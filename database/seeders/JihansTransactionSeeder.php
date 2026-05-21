<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JihansTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $products = DB::table('jihans_products')
                      ->whereIn('entity_scope', ['jihans', 'all'])
                      ->get();

        foreach ($products as $product) {
            DB::table('jihans_stock')->updateOrInsert(
                ['product_id' => $product->id],
                [
                    'quantity' => rand(10, 50),
                    'unit_id' => $product->unit_id,
                    'last_updated' => now()
                ]
            );
        }
    }
}
