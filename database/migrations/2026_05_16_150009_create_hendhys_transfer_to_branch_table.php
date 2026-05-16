<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('request_id')->nullable()->constrained('hendhys_branch_requests')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('master_branches');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();

            $table->index(['branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_transfer_to_branch');
    }
};
