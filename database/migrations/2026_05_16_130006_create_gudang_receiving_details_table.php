<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_receiving_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_id')->constrained('gudang_receivings')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->decimal('hpp_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_receiving_details');
    }
};
