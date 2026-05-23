<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->boolean('visible_gudang')->default(false)->after('entity_scope');
            $table->boolean('visible_jihans')->default(false)->after('visible_gudang');
            $table->boolean('visible_hendhys')->default(false)->after('visible_jihans');
        });

        // Migrasi data dari entity_scope lama ke kolom boolean baru
        DB::table('master_products')->where('entity_scope', 'all')->update([
            'visible_gudang' => true, 'visible_jihans' => true, 'visible_hendhys' => true,
        ]);
        DB::table('master_products')->where('entity_scope', 'gudang')->update(['visible_gudang' => true]);
        DB::table('master_products')->where('entity_scope', 'jihans')->update(['visible_jihans' => true]);
        DB::table('master_products')->where('entity_scope', 'hendhys')->update(['visible_hendhys' => true]);
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn(['visible_gudang', 'visible_jihans', 'visible_hendhys']);
        });
    }
};
