<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_pending_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('pending_number', 30)->unique();
            $table->date('date');
            $table->foreignId('customer_id')->nullable()->constrained('jihans_customers')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->enum('customer_type', ['retail', 'agen'])->default('retail');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_pending_transactions');
    }
};
