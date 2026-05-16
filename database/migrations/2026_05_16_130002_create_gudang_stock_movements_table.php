<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('master_products');
            $table->enum('type', ['in', 'out']);
            $table->enum('source', ['purchase_receiving', 'transfer_out', 'adjustment']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_before', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_stock_movements');
    }
};
