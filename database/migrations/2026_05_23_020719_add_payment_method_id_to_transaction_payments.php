<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jihans_transaction_payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('transaction_id')
                  ->constrained('master_payment_methods')->nullOnDelete();
        });

        // Change payment_method to nullable (using DB::statement since doctrine/dbal is not installed)
        DB::statement("ALTER TABLE `jihans_transaction_payments` MODIFY `payment_method` ENUM('cash','transfer') NULL");

        Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('transaction_id')
                  ->constrained('master_payment_methods')->nullOnDelete();
        });

        DB::statement("ALTER TABLE `hendhys_transaction_payments` MODIFY `payment_method` ENUM('cash','transfer') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jihans_transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });

        DB::statement("ALTER TABLE `jihans_transaction_payments` MODIFY `payment_method` ENUM('cash','transfer') NOT NULL");

        Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });

        DB::statement("ALTER TABLE `hendhys_transaction_payments` MODIFY `payment_method` ENUM('cash','transfer') NOT NULL");
    }
};
