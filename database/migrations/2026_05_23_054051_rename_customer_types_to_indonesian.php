<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update master_customers
        DB::table('master_customers')->where('type', 'individual')->update(['type' => 'Pelanggan Individual']);
        DB::table('master_customers')->where('type', 'retail')->update(['type' => 'Pelanggan Retail']);
        DB::table('master_customers')->where('type', 'agen')->update(['type' => 'Pelanggan Agen']);

        Schema::table('master_customers', function (Blueprint $table) {
            $table->string('type')->default('Pelanggan Individual')->change();
        });

        // 2. Update jihans_transactions
        DB::table('jihans_transactions')->where('customer_type', 'individual')->update(['customer_type' => 'Pelanggan Individual']);
        DB::table('jihans_transactions')->where('customer_type', 'retail')->update(['customer_type' => 'Pelanggan Retail']);
        DB::table('jihans_transactions')->where('customer_type', 'agen')->update(['customer_type' => 'Pelanggan Agen']);

        Schema::table('jihans_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('Pelanggan Individual')->change();
        });

        // 3. Update jihans_pending_transactions
        DB::table('jihans_pending_transactions')->where('customer_type', 'individual')->update(['customer_type' => 'Pelanggan Individual']);
        DB::table('jihans_pending_transactions')->where('customer_type', 'retail')->update(['customer_type' => 'Pelanggan Retail']);
        DB::table('jihans_pending_transactions')->where('customer_type', 'agen')->update(['customer_type' => 'Pelanggan Agen']);

        Schema::table('jihans_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('Pelanggan Individual')->change();
        });

        // 4. Update hendhys_transactions
        DB::table('hendhys_transactions')->where('customer_type', 'individual')->update(['customer_type' => 'Pelanggan Individual']);
        DB::table('hendhys_transactions')->where('customer_type', 'retail')->update(['customer_type' => 'Pelanggan Retail']);
        DB::table('hendhys_transactions')->where('customer_type', 'agen')->update(['customer_type' => 'Pelanggan Agen']);

        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('Pelanggan Individual')->change();
        });

        // 5. Update hendhys_pending_transactions
        DB::table('hendhys_pending_transactions')->where('customer_type', 'individual')->update(['customer_type' => 'Pelanggan Individual']);
        DB::table('hendhys_pending_transactions')->where('customer_type', 'retail')->update(['customer_type' => 'Pelanggan Retail']);
        DB::table('hendhys_pending_transactions')->where('customer_type', 'agen')->update(['customer_type' => 'Pelanggan Agen']);

        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('Pelanggan Individual')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse master_customers
        DB::table('master_customers')->where('type', 'Pelanggan Individual')->update(['type' => 'individual']);
        DB::table('master_customers')->where('type', 'Pelanggan Retail')->update(['type' => 'retail']);
        DB::table('master_customers')->where('type', 'Pelanggan Agen')->update(['type' => 'agen']);

        Schema::table('master_customers', function (Blueprint $table) {
            $table->string('type')->default('individual')->change();
        });

        // Reverse jihans_transactions
        DB::table('jihans_transactions')->where('customer_type', 'Pelanggan Individual')->update(['customer_type' => 'individual']);
        DB::table('jihans_transactions')->where('customer_type', 'Pelanggan Retail')->update(['customer_type' => 'retail']);
        DB::table('jihans_transactions')->where('customer_type', 'Pelanggan Agen')->update(['customer_type' => 'agen']);

        Schema::table('jihans_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        // Reverse jihans_pending_transactions
        DB::table('jihans_pending_transactions')->where('customer_type', 'Pelanggan Individual')->update(['customer_type' => 'individual']);
        DB::table('jihans_pending_transactions')->where('customer_type', 'Pelanggan Retail')->update(['customer_type' => 'retail']);
        DB::table('jihans_pending_transactions')->where('customer_type', 'Pelanggan Agen')->update(['customer_type' => 'agen']);

        Schema::table('jihans_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        // Reverse hendhys_transactions
        DB::table('hendhys_transactions')->where('customer_type', 'Pelanggan Individual')->update(['customer_type' => 'individual']);
        DB::table('hendhys_transactions')->where('customer_type', 'Pelanggan Retail')->update(['customer_type' => 'retail']);
        DB::table('hendhys_transactions')->where('customer_type', 'Pelanggan Agen')->update(['customer_type' => 'agen']);

        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        // Reverse hendhys_pending_transactions
        DB::table('hendhys_pending_transactions')->where('customer_type', 'Pelanggan Individual')->update(['customer_type' => 'individual']);
        DB::table('hendhys_pending_transactions')->where('customer_type', 'Pelanggan Retail')->update(['customer_type' => 'retail']);
        DB::table('hendhys_pending_transactions')->where('customer_type', 'Pelanggan Agen')->update(['customer_type' => 'agen']);

        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });
    }
};
