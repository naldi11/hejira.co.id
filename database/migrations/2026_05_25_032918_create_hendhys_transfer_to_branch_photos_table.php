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
        Schema::create('hendhys_transfer_to_branch_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('hendhys_transfer_to_branch')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('caption', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('master_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hendhys_transfer_to_branch_photos');
    }
};
