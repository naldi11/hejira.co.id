<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Hapus tabel product_jenis
        Schema::dropIfExists('product_jenis');

        // Hapus kolom jenis dari semua tabel produk
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
        Schema::table('jihans_products', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
        Schema::table('hendhys_products', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }

    public function down(): void
    {
        // Kembalikan kolom jenis sebagai string jika di-rollback
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('jenis', 100)->nullable()->after('rack');
        });
        Schema::table('jihans_products', function (Blueprint $table) {
            $table->string('jenis', 100)->nullable()->after('rack');
        });
        Schema::table('hendhys_products', function (Blueprint $table) {
            $table->string('jenis', 100)->nullable()->after('rack');
        });
    }
};
