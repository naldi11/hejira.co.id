<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity', 'entity_scope');
        });
        Schema::table('jihans_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity', 'entity_scope');
        });
        Schema::table('hendhys_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity', 'entity_scope');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity_scope', 'entity');
        });
        Schema::table('jihans_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity_scope', 'entity');
        });
        Schema::table('hendhys_product_categories', function (Blueprint $table) {
            $table->renameColumn('entity_scope', 'entity');
        });
    }
};
