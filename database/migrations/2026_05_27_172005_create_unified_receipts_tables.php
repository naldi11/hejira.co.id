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
        Schema::create('receipt_confirmations', function (Blueprint $table) {
            $table->id();
            $table->morphs('receiptable'); // e.g. GudangReceiving, TransferOut, TransferToBranch
            $table->foreignId('received_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->text('general_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('receipt_confirmation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_confirmation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('master_products');
            $table->decimal('expected_qty', 15, 2);
            $table->decimal('actual_qty', 15, 2);
            $table->enum('condition', ['baik', 'rusak', 'kurang', 'hilang'])->default('baik');
            $table->date('expired_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('receipt_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_confirmation_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_photos');
        Schema::dropIfExists('receipt_confirmation_details');
        Schema::dropIfExists('receipt_confirmations');
    }
};
