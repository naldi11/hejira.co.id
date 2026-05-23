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
        Schema::create('master_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('jihans');
            $table->string('name', 100);
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_name', 100)->nullable();
            $table->string('image', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_payment_methods');
    }
};
