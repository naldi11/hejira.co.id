<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('master_users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('master_branches')->cascadeOnDelete();
            $table->string('entity', 20); // 'jihans' or 'hendhys'
            $table->string('status', 20)->default('open'); // 'open' or 'closed'
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            
            $table->integer('starting_cash')->default(0);
            $table->integer('expected_cash')->nullable();
            $table->integer('actual_cash')->nullable();
            $table->integer('discrepancy')->nullable();
            $table->text('note')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_cashier_shifts');
    }
};
