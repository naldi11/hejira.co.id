<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_transfer_out', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('request_id')->nullable()->constrained('gudang_transfer_requests')->nullOnDelete();
            $table->enum('to_entity', ['jihans', 'hendhys']);
            $table->foreignId('branch_id')->nullable()->constrained('master_branches')->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();

            $table->index(['to_entity', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_transfer_out');
    }
};
