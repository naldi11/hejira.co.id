<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom konfigurasi varian (tarif + mapping produk) ke tabel sesi tortilla.
     * Kolom ini menyimpan tarif dan produk yang terkait per sesi produksi,
     * menggantikan peran tabel master_production_rates yang telah dihapus.
     */
    public function up(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            // Tarif borongan per varian (disimpan di level sesi agar bisa berbeda tiap sesi)
            $table->decimal('tb_rate', 12, 2)->default(0)->after('notes');
            $table->decimal('ts_rate', 12, 2)->default(0)->after('tb_rate');
            $table->decimal('tk_rate', 12, 2)->default(0)->after('ts_rate');
            $table->decimal('tc_rate', 12, 2)->default(0)->after('tk_rate');
            $table->decimal('kribab_rate', 12, 2)->default(0)->after('tc_rate');

            // Mapping varian → produk (untuk update stok Jihans otomatis)
            $table->unsignedBigInteger('tb_product_id')->nullable()->after('kribab_rate');
            $table->unsignedBigInteger('ts_product_id')->nullable()->after('tb_product_id');
            $table->unsignedBigInteger('tk_product_id')->nullable()->after('ts_product_id');
            $table->unsignedBigInteger('tc_product_id')->nullable()->after('tk_product_id');
            $table->unsignedBigInteger('kribab_product_id')->nullable()->after('tc_product_id');

            $table->foreign('tb_product_id')->references('id')->on('master_products')->nullOnDelete();
            $table->foreign('ts_product_id')->references('id')->on('master_products')->nullOnDelete();
            $table->foreign('tk_product_id')->references('id')->on('master_products')->nullOnDelete();
            $table->foreign('tc_product_id')->references('id')->on('master_products')->nullOnDelete();
            $table->foreign('kribab_product_id')->references('id')->on('master_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->dropForeign(['tb_product_id']);
            $table->dropForeign(['ts_product_id']);
            $table->dropForeign(['tk_product_id']);
            $table->dropForeign(['tc_product_id']);
            $table->dropForeign(['kribab_product_id']);
            $table->dropColumn([
                'tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate',
                'tb_product_id', 'ts_product_id', 'tk_product_id', 'tc_product_id', 'kribab_product_id',
            ]);
        });
    }
};
