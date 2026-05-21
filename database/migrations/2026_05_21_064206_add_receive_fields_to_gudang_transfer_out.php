<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gudang_transfer_out', function (Blueprint $table) {
            $table->enum('status', ['sent', 'received'])->default('sent')->after('branch_id');
            $table->foreignId('received_by')->nullable()->constrained('master_users')->nullOnDelete()->after('status');
            $table->text('receive_notes')->nullable()->after('notes');
            $table->string('receive_photo', 255)->nullable()->after('receive_notes');
        });

        Schema::table('gudang_transfer_out_details', function (Blueprint $table) {
            $table->decimal('received_quantity', 15, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_transfer_out', function (Blueprint $table) {
            $table->dropColumn(['status', 'received_by', 'receive_notes', 'receive_photo']);
        });

        Schema::table('gudang_transfer_out_details', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }
};
