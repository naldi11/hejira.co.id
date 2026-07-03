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
        Schema::table('hendhys_productions', function (Blueprint $table) {
            $table->enum('type', ['prediksi', 'aktual'])->default('aktual')->after('production_number')->nullable(false);
            $table->timestamp('overridden_at')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hendhys_productions', function (Blueprint $table) {
            $table->dropColumn(['type', 'overridden_at']);
        });
    }
};
