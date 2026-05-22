<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_stock_in_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained('jihans_stock_in')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->decimal('hpp_price', 15, 2);
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_stock_in_details');
    }
};
