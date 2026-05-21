<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 30)->unique();
            $table->date('date');
            $table->time('time');
            $table->foreignId('customer_id')->nullable()->constrained('jihans_customers')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->enum('customer_type', ['retail', 'agen'])->default('retail');
            $table->enum('ppn_type', ['none', 'include', 'exclude'])->default('none');
            $table->decimal('ppn_rate', 5, 2)->default(11.00);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('other_costs', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('status', ['paid', 'pending', 'cancelled'])->default('paid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();

            $table->index(['date', 'status']);
            $table->index('customer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_transactions');
    }
};
