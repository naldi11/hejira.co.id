<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_product_tiered_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('master_products')->cascadeOnDelete();
            $table->decimal('min_qty', 15, 3);
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_product_tiered_prices');
    }
};
