<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

        // 1. Gudang stock movements source enum
        DB::statement("ALTER TABLE gudang_stock_movements MODIFY COLUMN source ENUM(
            'purchase_receiving',
            'transfer_out',
            'return_receiving',
            'adjustment'
        )");

        // 2. Hendhys stock movements source enum
        DB::statement("ALTER TABLE hendhys_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'transfer_to_branch',
            'receive_from_pusat',
            'return_from_branch',
            'return_to_pusat',
            'return_gudang',
            'pos_sale',
            'adjustment'
        )");

        // 3. Jihans stock movements source enum
        DB::statement("ALTER TABLE jihans_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'receive_from_gudang',
            'return_gudang',
            'pos_sale',
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
        DB::statement("ALTER TABLE gudang_stock_movements MODIFY COLUMN source ENUM(
            'purchase_receiving',
            'transfer_out',
            'adjustment'
        )");

        DB::statement("ALTER TABLE hendhys_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'transfer_to_branch',
            'receive_from_pusat',
            'return_from_branch',
            'return_to_pusat',
            'pos_sale',
            'adjustment'
        )");

        DB::statement("ALTER TABLE jihans_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'receive_from_gudang',
            'pos_sale',
            'adjustment'
        )");
    }
};
