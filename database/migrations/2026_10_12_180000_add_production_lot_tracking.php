<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_lots', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 16);
            $table->unsignedBigInteger('production_id')->nullable();
            $table->foreignId('product_id')->constrained('master_products');
            $table->foreignId('unit_id')->constrained('master_units');
            $table->date('production_date')->nullable();
            $table->string('production_number')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('remaining');
            $table->timestamps();
            $table->index(['entity', 'product_id', 'remaining']);
            $table->unique(['entity', 'production_id', 'product_id']);
        });
        Schema::create('production_lot_outflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('production_lots');
            $table->string('source', 64);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('restored_quantity')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at');
            $table->index(['source', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lot_outflows');
        Schema::dropIfExists('production_lots');
    }
};
