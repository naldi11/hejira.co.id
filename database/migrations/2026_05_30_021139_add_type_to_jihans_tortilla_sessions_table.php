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
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->enum('type', ['prediksi', 'aktual'])->default('aktual')->after('session_number');
            $table->timestamp('overridden_at')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->dropColumn(['type', 'overridden_at']);
        });
    }
};
