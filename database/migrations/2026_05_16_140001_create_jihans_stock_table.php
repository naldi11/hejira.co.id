<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('jihans_products');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->foreignId('unit_id')->constrained('jihans_units');
            $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_stock');
    }
};
