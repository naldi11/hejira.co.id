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
        Schema::table('gudang_receivings', function (Blueprint $table) {
            $table->enum('status', ['open', 'closed'])->default('open')->after('notes');
            $table->string('received_by_name', 100)->nullable()->after('status');
            $table->string('supplier_rep_name', 100)->nullable()->after('received_by_name');
            $table->text('kendala')->nullable()->after('supplier_rep_name');
            $table->timestamp('closed_at')->nullable()->after('kendala');
            $table->foreignId('closed_by')->nullable()->after('closed_at')
                  ->constrained('master_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gudang_receivings', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['status', 'received_by_name', 'supplier_rep_name', 'kendala', 'closed_at', 'closed_by']);
        });
    }
};
