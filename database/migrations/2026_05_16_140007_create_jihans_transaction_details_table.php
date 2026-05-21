<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('jihans_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('jihans_products');
            $table->string('product_name', 200);
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('jihans_units');
            $table->decimal('price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_transaction_details');
    }
};
