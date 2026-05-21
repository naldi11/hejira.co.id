<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('master_branches')->nullOnDelete();
            $table->foreignId('product_id')->constrained('hendhys_products');
            $table->enum('type', ['in', 'out']);
            $table->enum('source', ['transfer_gudang', 'production', 'transfer_to_branch', 'return_from_branch', 'pos_sale', 'adjustment']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_before', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'product_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_stock_movements');
    }
};
