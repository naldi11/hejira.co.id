<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'master_product_categories',
        'master_units',
        'master_brands',
        'master_customers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('visible_gudang')->default(false)->after('entity_scope');
                $t->boolean('visible_jihans')->default(false)->after('visible_gudang');
                $t->boolean('visible_hendhys')->default(false)->after('visible_jihans');
            });

            // Migrasi data dari entity_scope ke boolean
            DB::table($table)->where('entity_scope', 'all')->update([
                'visible_gudang' => true, 'visible_jihans' => true, 'visible_hendhys' => true,
            ]);
            DB::table($table)->where('entity_scope', 'gudang')->update(['visible_gudang' => true]);
            DB::table($table)->where('entity_scope', 'jihans')->update(['visible_jihans' => true]);
            DB::table($table)->where('entity_scope', 'hendhys')->update(['visible_hendhys' => true]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['visible_gudang', 'visible_jihans', 'visible_hendhys']);
            });
        }
    }
};
