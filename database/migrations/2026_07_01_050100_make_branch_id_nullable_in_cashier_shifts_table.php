<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('master_branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::table('master_cashier_shifts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('master_branches')->cascadeOnDelete();
        });
    }
};
