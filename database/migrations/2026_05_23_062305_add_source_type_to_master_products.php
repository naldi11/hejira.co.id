<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->enum('source_type', ['produced', 'purchased'])
                  ->default('purchased')
                  ->after('product_type');
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
