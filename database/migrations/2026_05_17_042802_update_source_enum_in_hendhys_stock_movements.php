<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL 8.0 support for changing enum is slightly different, 
        // we use a raw statement to be sure.
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

    public function down(): void
    {
        DB::statement("ALTER TABLE hendhys_stock_movements MODIFY COLUMN source ENUM(
            'transfer_gudang', 
            'production', 
            'transfer_to_branch', 
            'return_from_branch', 
            'pos_sale', 
            'adjustment'
        )");
    }
};
