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
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE hendhys_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang',
            'production',
            'transfer_to_branch',
            'receive_from_pusat',
            'return_from_branch',
            'pos_sale',
            'adjustment'
        )");
    }
};
