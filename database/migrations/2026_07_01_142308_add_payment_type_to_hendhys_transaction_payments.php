<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
            // payment_type: tunai | transfer | kartu_debit | kartu_kredit
            $table->string('payment_type', 50)->nullable()->after('payment_method');
        });

        // Backfill: jika payment_method = 'cash' → tunai, 'transfer' → transfer
        DB::statement("UPDATE `hendhys_transaction_payments` SET `payment_type` = 'tunai' WHERE `payment_method` = 'cash' AND `payment_type` IS NULL");
        DB::statement("UPDATE `hendhys_transaction_payments` SET `payment_type` = 'transfer' WHERE `payment_method` = 'transfer' AND `payment_type` IS NULL");
    }

    public function down(): void
    {
        Schema::table('hendhys_transaction_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
