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
        Schema::create('gudang_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->enum('from_entity', ['jihans', 'hendhys']);
            $table->foreignId('branch_id')->nullable()->constrained('master_branches')->nullOnDelete();
            $table->date('date');
            $table->enum('status', ['sent', 'received'])->default('sent');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->foreignId('received_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['from_entity', 'status']);
            $table->index('date');
        });

        Schema::create('gudang_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('gudang_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('quantity', 15, 3);
            $table->foreignId('unit_id')->constrained('master_units');
            $table->decimal('received_quantity', 15, 3)->nullable();
            $table->string('condition', 100)->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_return_details');
        Schema::dropIfExists('gudang_returns');
    }
};
