<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_material_transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('master_branches');
            $table->string('branch_name')->nullable();
            $table->string('stock_location', 40)->nullable();
            $table->index(['branch_id', 'date', 'id'], 'production_material_branch_history');
        });
    }

    public function down(): void
    {
        Schema::table('production_material_transactions', function (Blueprint $table) {
            $table->dropIndex('production_material_branch_history');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['branch_name', 'stock_location']);
        });
    }
};
