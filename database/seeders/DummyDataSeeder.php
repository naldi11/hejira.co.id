<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterCategorySeeder::class,
            MasterBrandSeeder::class,
            MasterSupplierCustomerSeeder::class,
            MasterProductSeeder::class,
            GudangTransactionSeeder::class,
            JihansTransactionSeeder::class,
            HendhysTransactionSeeder::class,
        ]);
    }
}
