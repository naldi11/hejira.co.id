<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jihans_production_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tb_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('ts_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('tk_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('tc_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('kribab_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jihans_production_config');
    }
};
