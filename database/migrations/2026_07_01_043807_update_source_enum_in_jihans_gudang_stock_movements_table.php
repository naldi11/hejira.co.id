<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE jihans_gudang_stock_movements MODIFY COLUMN source ENUM(
            'purchase_receiving',
            'transfer_out',
            'return_receiving',
            'production',
            'adjustment'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Just revert back safely
        DB::statement("ALTER TABLE jihans_gudang_stock_movements MODIFY COLUMN source ENUM(
            'purchase_receiving',
            'transfer_out',
            'return_receiving',
            'adjustment'
        )");
    }
};
