<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('hendhys_transactions')->cascadeOnDelete();
            // Nullable: superseded by payment_method_id (FK). A later migration makes
            // this nullable on MySQL, but that widener is sqlite-guarded — so the base
            // must already allow null for the sqlite test DB.
            $table->enum('payment_method', ['cash', 'transfer'])->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('reference_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_transaction_payments');
    }
};
