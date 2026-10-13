<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_writeoffs', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->string('entity', 16);
            $table->foreignId('lot_id')->constrained('production_lots');
            $table->unsignedBigInteger('production_id')->nullable();
            $table->string('production_number')->nullable();
            $table->date('production_date')->nullable();
            $table->foreignId('product_id')->constrained('master_products');
            $table->foreignId('unit_id')->constrained('master_units');
            $table->string('product_name');
            $table->string('unit_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('remaining_before');
            $table->unsignedInteger('remaining_after');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 32);
            $table->text('notes');
            $table->unsignedBigInteger('movement_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->string('owner_name');
            $table->timestamp('created_at');
            $table->index(['entity', 'production_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_writeoffs');
    }
};
