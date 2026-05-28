<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->dropColumn(['tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate']);
        });

        Schema::table('jihans_tortilla_session_details', function (Blueprint $table) {
            $table->dropColumn(['tb_rate', 'ts_rate', 'tk_rate', 'tc_rate', 'kribab_rate', 'total_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->decimal('tb_rate', 12, 2)->default(0)->after('notes');
            $table->decimal('ts_rate', 12, 2)->default(0)->after('tb_rate');
            $table->decimal('tk_rate', 12, 2)->default(0)->after('ts_rate');
            $table->decimal('tc_rate', 12, 2)->default(0)->after('tk_rate');
            $table->decimal('kribab_rate', 12, 2)->default(0)->after('tc_rate');
        });

        Schema::table('jihans_tortilla_session_details', function (Blueprint $table) {
            $table->decimal('tb_rate', 15, 2)->default(0);
            $table->decimal('ts_rate', 15, 2)->default(0);
            $table->decimal('tk_rate', 15, 2)->default(0);
            $table->decimal('tc_rate', 15, 2)->default(0);
            $table->decimal('kribab_rate', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
        });
    }
};
