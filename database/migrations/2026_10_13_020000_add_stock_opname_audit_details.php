<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_counts', function (Blueprint $table) {
            // Older records stay null: current master data cannot recreate historical snapshots.
            $table->string('product_code')->nullable();
            $table->string('counted_by_role', 100)->nullable();
            $table->string('stock_movement_table', 80)->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->index(['created_at', 'id'], 'so_counts_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_counts', function (Blueprint $table) {
            $table->dropIndex('so_counts_time_index');
            $table->dropColumn(['product_code', 'counted_by_role', 'stock_movement_table', 'stock_movement_id']);
        });
    }
};
