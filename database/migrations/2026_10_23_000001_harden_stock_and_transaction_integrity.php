<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hendhys_transactions', 'jihans_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->foreignId('shift_id')->nullable()->constrained('master_cashier_shifts')->restrictOnDelete());
        }
        foreach (['hendhys_productions', 'jihans_production_sessions'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->char('actualization_hash', 64)->nullable());
        }
        Schema::create('production_submissions', function (Blueprint $t) {
            $t->uuid('request_key')->primary();
            $t->char('payload_hash', 64);
            $t->string('entity', 20);
            $t->unsignedBigInteger('production_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_submissions');
        foreach (['hendhys_productions', 'jihans_production_sessions'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropColumn('actualization_hash'));
        }
        foreach (['hendhys_transactions', 'jihans_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropConstrainedForeignId('shift_id'));
        }
    }
};
