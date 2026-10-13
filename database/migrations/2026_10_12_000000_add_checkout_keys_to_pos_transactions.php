<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hendhys_transactions', 'jihans_transactions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('checkout_key')->nullable();
                $table->char('checkout_fingerprint', 64)->nullable();
                $table->unique(['created_by', 'checkout_key']);
            });
        }
    }

    public function down(): void
    {
        foreach (['hendhys_transactions', 'jihans_transactions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['created_by', 'checkout_key']);
                $table->dropColumn(['checkout_key', 'checkout_fingerprint']);
            });
        }
    }
};
