<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique();
            $table->enum('from_entity', ['jihans', 'hendhys']);
            $table->foreignId('branch_id')->nullable()->constrained('master_branches')->nullOnDelete();
            $table->date('date');
            $table->date('needed_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'partial', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('requested_by')->constrained('master_users');
            $table->foreignId('approved_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['from_entity', 'status']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_transfer_requests');
    }
};
