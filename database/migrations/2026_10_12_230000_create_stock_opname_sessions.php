<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permanent mutex rows also serialize opening sessions for shared stock buckets.
        Schema::create('stock_opname_locations', function (Blueprint $table) {
            $table->string('key', 80)->primary();
        });
        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->string('location_key', 80);
            $table->string('stock_key', 80)->index();
            $table->string('location_name');
            $table->string('entity', 20);
            $table->foreignId('branch_id')->nullable()->constrained('master_branches');
            $table->string('status', 20)->default('open');
            $table->text('notes');
            $table->foreignId('opened_by')->constrained('master_users');
            $table->string('opened_by_name');
            $table->timestamp('opened_at');
            $table->timestamp('expires_at');
            $table->foreignId('closed_by')->nullable()->constrained('master_users');
            $table->string('closed_by_name')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
        Schema::create('stock_opname_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('stock_opname_sessions');
            $table->foreignId('product_id')->constrained('master_products');
            $table->foreignId('unit_id')->constrained('master_units');
            $table->string('product_name');
            $table->string('unit_name');
            $table->bigInteger('quantity_before');
            $table->unsignedBigInteger('quantity_after');
            $table->bigInteger('difference');
            $table->text('notes');
            $table->foreignId('counted_by')->constrained('master_users');
            $table->string('counted_by_name');
            $table->timestamp('created_at');
            $table->unique(['session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_counts');
        Schema::dropIfExists('stock_opname_sessions');
        Schema::dropIfExists('stock_opname_locations');
    }
};
