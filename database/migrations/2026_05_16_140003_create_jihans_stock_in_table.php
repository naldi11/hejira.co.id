<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jihans_stock_in', function (Blueprint $table) {
            $table->id();
            $table->string('stock_in_number', 30)->unique();
            $table->foreignId('transfer_out_id')->constrained('gudang_transfer_out');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jihans_stock_in');
    }
};
