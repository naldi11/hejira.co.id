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
        Schema::table('gudang_receiving_details', function (Blueprint $table) {
            $table->decimal('quantity_ordered', 15, 3)->nullable()->after('receiving_id');
            $table->enum('kondisi', ['baik', 'rusak', 'kurang'])->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('gudang_receiving_details', function (Blueprint $table) {
            $table->dropColumn(['quantity_ordered', 'kondisi']);
        });
    }
};
