<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 30)->unique();
            $table->foreignId('po_id')->nullable()->constrained('gudang_purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('master_suppliers');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_receivings');
    }
};
