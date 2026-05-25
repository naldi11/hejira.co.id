<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->enum('jenis', ['frozen', 'tortilla', 'bakery', 'bahan_baku', 'aksesoris', 'minuman', 'snack', 'selai', 'property', 'lainnya'])
                  ->after('rack')
                  ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
