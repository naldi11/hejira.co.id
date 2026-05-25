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
        Schema::table('master_payment_methods', function (Blueprint $table) {
            $table->enum('type', ['tunai', 'kredit', 'kartu_debit', 'kartu_kredit'])
                  ->default('tunai')
                  ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_payment_methods', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
