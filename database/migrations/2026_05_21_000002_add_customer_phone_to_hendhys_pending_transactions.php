<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
        });
    }
    public function down(): void {
        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->dropColumn('customer_phone');
        });
    }
};
