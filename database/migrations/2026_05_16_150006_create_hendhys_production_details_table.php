<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_production_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('hendhys_productions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('hendhys_products');
            $table->decimal('quantity_produced', 15, 3);
            $table->foreignId('unit_id')->constrained('hendhys_units');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_production_details');
    }
};
