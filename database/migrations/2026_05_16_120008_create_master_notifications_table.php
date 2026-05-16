<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->enum('from_entity', ['gudang', 'jihans', 'hendhys', 'system']);
            $table->enum('to_entity', ['gudang', 'jihans', 'hendhys', 'owner', 'all']);
            $table->string('to_role', 50)->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('title', 200);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('to_user_id');
            $table->index(['to_entity', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_notifications');
    }
};
