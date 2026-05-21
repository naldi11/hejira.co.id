<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_transfer_to_branch_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('hendhys_transfer_to_branch')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('hendhys_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('hendhys_units');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_transfer_to_branch_details');
    }
};
