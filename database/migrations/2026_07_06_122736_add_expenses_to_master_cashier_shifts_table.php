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
        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->integer('total_expenses')->default(0)->after('actual_cash');
            $table->json('expenses_detail')->nullable()->after('total_expenses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['total_expenses', 'expenses_detail']);
        });
    }
};
