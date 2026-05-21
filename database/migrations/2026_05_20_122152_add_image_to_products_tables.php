<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
        Schema::table('jihans_products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
        Schema::table('hendhys_products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
        Schema::table('jihans_products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
        Schema::table('hendhys_products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
