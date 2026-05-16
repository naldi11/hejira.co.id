<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_po_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('gudang_purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity_ordered', 15, 3);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_po_details');
    }
};
