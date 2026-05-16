<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_branch_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('hendhys_branch_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('quantity_approved', 15, 3)->nullable();
            $table->foreignId('unit_id')->constrained('master_units');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_branch_request_details');
    }
};
