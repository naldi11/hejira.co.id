<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE jihans_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'receive_from_gudang',
            'pos_sale',
            'adjustment'
        )");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE jihans_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'pos_sale',
            'adjustment'
        )");
    }
};
