<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['hendhys_productions', 'jihans_production_sessions'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->json('prediction_snapshot')->nullable());
        }
        Schema::create('production_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->date('deadline_date');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['deadline_date', 'product_id']);
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stock_alerts');
        foreach (['hendhys_productions', 'jihans_production_sessions'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('prediction_snapshot'));
        }
    }
};
