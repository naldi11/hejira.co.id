<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionRateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('master_production_rates')->updateOrInsert(
            ['entity_scope' => 'jihans'],
            [
                'tb_rate'     => 0,
                'ts_rate'     => 0,
                'tk_rate'     => 0,
                'tc_rate'     => 0,
                'kribab_rate' => 0,
                'notes'       => 'Default rate — harap diisi oleh admin.',
                'updated_by'  => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }
}
