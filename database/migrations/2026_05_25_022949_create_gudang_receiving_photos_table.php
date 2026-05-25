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
        Schema::create('gudang_receiving_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_id')->constrained('gudang_receivings')->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('caption', 200)->nullable();
            $table->foreignId('uploaded_by')->constrained('master_users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_receiving_photos');
    }
};
