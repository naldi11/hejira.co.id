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
        Schema::table('master_customers', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('province');
            $table->string('district', 100)->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_customers', function (Blueprint $table) {
            $table->dropColumn(['province', 'city', 'district']);
        });
    }
};
