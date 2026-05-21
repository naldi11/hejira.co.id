<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_pending_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_id')->constrained('hendhys_pending_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('hendhys_products');
            $table->string('product_name', 200);
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('hendhys_units');
            $table->decimal('price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_pending_details');
    }
};
