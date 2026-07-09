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
        DB::statement("ALTER TABLE jihans_retail_stock_movements MODIFY COLUMN source ENUM('transfer_gudang','production','receive_from_gudang','return_gudang','pos_sale','adjustment','receive_from_hendhys','return_to_hendhys') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For down, we shouldn't necessarily delete the new enum values as it would crash if data exists.
        // We'll leave it as is or revert to the previous one (risky if data uses new enums).
        // It's safer to just alter back to original IF no data exists, but doing nothing is safer.
    }
};
