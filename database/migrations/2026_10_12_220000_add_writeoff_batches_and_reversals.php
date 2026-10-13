<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_writeoff_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->string('payload_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('created_at');
        });
        Schema::table('production_writeoffs', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->constrained('production_writeoff_batches');
        });
        Schema::create('production_writeoff_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('writeoff_id')->unique()->constrained('production_writeoffs');
            $table->uuid('request_key')->unique();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('remaining_before');
            $table->unsignedInteger('remaining_after');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->text('notes');
            $table->unsignedBigInteger('movement_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->string('owner_name');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_writeoff_reversals');
        Schema::table('production_writeoffs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::dropIfExists('production_writeoff_batches');
    }
};
