<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_opname_session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('stock_opname_sessions');
            $table->uuid('request_key')->nullable()->unique();
            $table->string('action', 20);
            $table->foreignId('owner_id')->constrained('master_users');
            $table->string('owner_name');
            $table->string('previous_status', 20)->nullable();
            $table->timestamp('previous_expires_at')->nullable();
            $table->timestamp('previous_closed_at')->nullable();
            $table->string('previous_closed_by_name')->nullable();
            $table->unsignedBigInteger('previous_closed_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('stock_opname_session_events'); }
};
