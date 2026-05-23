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
        Schema::create('jihans_tortilla_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_number', 30)->unique();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jihans_tortilla_sessions');
    }
};
