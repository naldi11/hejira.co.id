<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_stock_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('master_branches');
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_stock_branch');
    }
};
