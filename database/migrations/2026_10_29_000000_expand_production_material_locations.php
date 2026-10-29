<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_material_items', function (Blueprint $table) {
            $table->unsignedBigInteger('quantity')->change();
            $table->unsignedInteger('quantity_scale')->default(1);
        });
        foreach (['jihans_retail_stock_movements', 'izzi_stock_movements'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('production_material_id')->nullable()->constrained('production_material_transactions'));
        }
    }

    public function down(): void
    {
        foreach (['jihans_retail_stock_movements', 'izzi_stock_movements'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('production_material_id'));
        }
        Schema::table('production_material_items', function (Blueprint $table) {
            $table->dropColumn('quantity_scale');
            // Keep the widened quantity column so historical scaled amounts remain valid.
        });
    }
};
