<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_transfer_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('gudang_transfer_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('quantity_approved', 15, 3)->nullable();
            $table->foreignId('unit_id')->constrained('master_units');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_transfer_request_details');
    }
};
