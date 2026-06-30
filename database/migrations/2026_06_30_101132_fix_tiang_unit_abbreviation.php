<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fixes = [
            'Gram'  => 'GRAM',
            'Pack'  => 'PACK',
            'Tiang' => 'TIANG',
        ];

        foreach ($fixes as $name => $abbreviation) {
            DB::table('master_units')
                ->where('name', $name)
                ->update(['abbreviation' => $abbreviation]);
        }
    }

    public function down(): void
    {
        $originals = [
            'Gram'  => 'GRA',
            'Pack'  => 'PAC',
            'Tiang' => 'TIA',
        ];

        foreach ($originals as $name => $abbreviation) {
            DB::table('master_units')
                ->where('name', $name)
                ->update(['abbreviation' => $abbreviation]);
        }
    }
};
