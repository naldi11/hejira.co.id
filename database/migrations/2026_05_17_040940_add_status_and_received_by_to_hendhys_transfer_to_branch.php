<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->enum('status', ['sent', 'received', 'cancelled'])->default('sent')->after('date');
            $table->foreignId('received_by')->nullable()->constrained('master_users')->nullOnDelete()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('hendhys_transfer_to_branch', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['status', 'received_by']);
        });
    }
};
