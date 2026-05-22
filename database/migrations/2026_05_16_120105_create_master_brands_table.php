<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_brands', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('all');
            $table->string('name', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_brands');
    }
};
