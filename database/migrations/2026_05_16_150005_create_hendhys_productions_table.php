<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hendhys_productions', function (Blueprint $table) {
            $table->id();
            $table->string('production_number', 30)->unique();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('master_users');
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hendhys_productions');
    }
};
