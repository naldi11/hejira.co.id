<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('gudang_stock', 'jihans_gudang_stock');
        Schema::rename('gudang_stock_movements', 'jihans_gudang_stock_movements');
        Schema::rename('jihans_stock', 'jihans_retail_stock');
        Schema::rename('jihans_stock_movements', 'jihans_retail_stock_movements');
        Schema::rename('jihans_stock_in', 'jihans_retail_stock_in');
        Schema::rename('jihans_stock_in_details', 'jihans_retail_stock_in_details');
    }

    public function down(): void
    {
        Schema::rename('jihans_gudang_stock', 'gudang_stock');
        Schema::rename('jihans_gudang_stock_movements', 'gudang_stock_movements');
        Schema::rename('jihans_retail_stock', 'jihans_stock');
        Schema::rename('jihans_retail_stock_movements', 'jihans_stock_movements');
        Schema::rename('jihans_retail_stock_in', 'jihans_stock_in');
        Schema::rename('jihans_retail_stock_in_details', 'jihans_stock_in_details');
    }
};
