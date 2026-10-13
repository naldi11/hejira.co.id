<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_transfer_daily_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::table('branch_stock_transfers', function (Blueprint $table) {
            $table->string('recording_mode', 20)->default('in_transit');
            $table->string('physical_sender_name')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->index(['date', 'id'], 'bst_date_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('branch_stock_transfers', function (Blueprint $table) {
            $table->dropIndex('bst_date_id_index');
            $table->dropColumn(['recording_mode', 'physical_sender_name', 'recorded_at']);
        });
        Schema::dropIfExists('branch_transfer_daily_sequences');
    }
};
