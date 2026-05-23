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
        Schema::create('jihans_tortilla_session_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('jihans_tortilla_sessions')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->nullable()->constrained('master_karyawan')->nullOnDelete();
            $table->unsignedInteger('tb_qty')->default(0);
            $table->unsignedInteger('ts_qty')->default(0);
            $table->unsignedInteger('tk_qty')->default(0);
            $table->unsignedInteger('tc_qty')->default(0);
            $table->unsignedInteger('kribab_qty')->default(0);
            $table->decimal('tb_rate', 15, 2)->default(0);
            $table->decimal('ts_rate', 15, 2)->default(0);
            $table->decimal('tk_rate', 15, 2)->default(0);
            $table->decimal('tc_rate', 15, 2)->default(0);
            $table->decimal('kribab_rate', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['session_id', 'karyawan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jihans_tortilla_session_details');
    }
};
