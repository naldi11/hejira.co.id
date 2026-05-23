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
        Schema::table('master_customers', function (Blueprint $table) {
            $table->string('type')->default('individual')->change();
        });

        Schema::table('jihans_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        Schema::table('jihans_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });

        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->string('customer_type')->default('individual')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_customers', function (Blueprint $table) {
            $table->enum('type', ['retail', 'agen'])->default('retail')->change();
        });

        Schema::table('jihans_transactions', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'agen'])->default('retail')->change();
        });

        Schema::table('jihans_pending_transactions', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'agen'])->default('retail')->change();
        });

        Schema::table('hendhys_transactions', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'agen'])->default('retail')->change();
        });

        Schema::table('hendhys_pending_transactions', function (Blueprint $table) {
            $table->enum('customer_type', ['retail', 'agen'])->default('retail')->change();
        });
    }
};
