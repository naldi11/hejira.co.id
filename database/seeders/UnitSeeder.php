<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pak',       'abbreviation' => 'PAK'],
            ['name' => 'Pieces',    'abbreviation' => 'PCS'],
            ['name' => 'Kilogram',  'abbreviation' => 'KG'],
            ['name' => 'Gram',      'abbreviation' => 'GR'],
            ['name' => 'Liter',     'abbreviation' => 'LTR'],
            ['name' => 'Mililiter', 'abbreviation' => 'ML'],
            ['name' => 'Lusin',     'abbreviation' => 'LSN'],
            ['name' => 'Dus',       'abbreviation' => 'DUS'],
            ['name' => 'Box',       'abbreviation' => 'BOX'],
            ['name' => 'Loyang',    'abbreviation' => 'LYG'],
            ['name' => 'Lembar',    'abbreviation' => 'LBR'],
            ['name' => 'Botol',     'abbreviation' => 'BTL'],
            ['name' => 'Karung',    'abbreviation' => 'KRG'],
            ['name' => 'Sachet',    'abbreviation' => 'SCT'],
        ];

        foreach ($units as $unit) {
            DB::table('master_units')->insertOrIgnore(array_merge($unit, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
