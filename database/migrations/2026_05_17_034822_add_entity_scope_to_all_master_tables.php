<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'gudang_units', 'jihans_units', 'hendhys_units',
            'gudang_brands', 'jihans_brands', 'hendhys_brands',
            'gudang_suppliers', 'jihans_suppliers', 'hendhys_suppliers',
            'gudang_customers', 'jihans_customers', 'hendhys_customers',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('all')->after('id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'gudang_units', 'jihans_units', 'hendhys_units',
            'gudang_brands', 'jihans_brands', 'hendhys_brands',
            'gudang_suppliers', 'jihans_suppliers', 'hendhys_suppliers',
            'gudang_customers', 'jihans_customers', 'hendhys_customers',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('entity_scope');
            });
        }
    }
};
