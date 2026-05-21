<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_transfer_out_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('gudang_transfer_out')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('gudang_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('gudang_units');
            $table->decimal('hpp_price', 15, 2);
            $table->decimal('total', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_transfer_out_details');
    }
};
