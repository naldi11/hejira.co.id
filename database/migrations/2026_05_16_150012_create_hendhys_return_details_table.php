<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('hendhys_returns_from_branch')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->string('condition', 100)->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_return_details');
    }
};
