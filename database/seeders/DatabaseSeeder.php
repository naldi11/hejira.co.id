<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BranchSeeder::class,
            UnitSeeder::class,
            UserSeeder::class,
            //DummyDataSeeder::class,
            ProductionRateSeeder::class,
        ]);
    }
}
