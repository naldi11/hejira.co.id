<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hendhys_stock_pusat', function (Blueprint $table) {
            $table->decimal('quantity_return', 15, 3)->default(0.000)->after('quantity');
        });

        Schema::table('hendhys_stock_branch', function (Blueprint $table) {
            $table->decimal('quantity_return', 15, 3)->default(0.000)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('hendhys_stock_pusat', function (Blueprint $table) {
            $table->dropColumn('quantity_return');
        });

        Schema::table('hendhys_stock_branch', function (Blueprint $table) {
            $table->dropColumn('quantity_return');
        });
    }
};
