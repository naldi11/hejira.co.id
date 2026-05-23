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
        Schema::create('master_production_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_scope', ['gudang', 'jihans', 'hendhys', 'all'])->default('jihans');
            $table->decimal('tb_rate', 15, 2)->default(0);
            $table->decimal('ts_rate', 15, 2)->default(0);
            $table->decimal('tk_rate', 15, 2)->default(0);
            $table->decimal('tc_rate', 15, 2)->default(0);
            $table->decimal('kribab_rate', 15, 2)->default(0);
            $table->index('entity_scope');
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('master_users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_production_rates');
    }
};
