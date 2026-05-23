<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_production_rates', function (Blueprint $table) {
            $table->foreignId('tb_product_id')->nullable()->after('kribab_rate')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('ts_product_id')->nullable()->after('tb_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('tk_product_id')->nullable()->after('ts_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('tc_product_id')->nullable()->after('tk_product_id')
                  ->constrained('master_products')->nullOnDelete();
            $table->foreignId('kribab_product_id')->nullable()->after('tc_product_id')
                  ->constrained('master_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_production_rates', function (Blueprint $table) {
            $table->dropForeign(['tb_product_id']);
            $table->dropForeign(['ts_product_id']);
            $table->dropForeign(['tk_product_id']);
            $table->dropForeign(['tc_product_id']);
            $table->dropForeign(['kribab_product_id']);
            $table->dropColumn(['tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id']);
        });
    }
};
