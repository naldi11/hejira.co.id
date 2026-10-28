<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_transfer_out', function (Blueprint $t) {
            $t->enum('to_entity', ['jihans', 'hendhys', 'izzi'])->change();
            $t->uuid('request_key')->nullable()->unique();
            $t->char('payload_hash', 64)->nullable();
            $t->string('destination_name')->nullable();
            $t->string('sender_name')->nullable();
        });
        Schema::table('gudang_transfer_out_details', function (Blueprint $t) {
            $t->string('product_name')->nullable();
            $t->string('product_code')->nullable();
            $t->string('unit_name')->nullable();
        });
        Schema::create('warehouse_dispatch_sequences', function (Blueprint $t) {
            $t->unsignedSmallInteger('year')->primary();
            $t->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        if (DB::table('gudang_transfer_out')->where('to_entity', 'izzi')->exists()) {
            throw new RuntimeException('Riwayat pengiriman Izzi harus dipertahankan. Migrasi ini tidak boleh dibatalkan setelah digunakan.');
        }
        Schema::dropIfExists('warehouse_dispatch_sequences');
        Schema::table('gudang_transfer_out_details', fn (Blueprint $t) => $t->dropColumn(['product_name', 'product_code', 'unit_name']));
        Schema::table('gudang_transfer_out', function (Blueprint $t) {
            $t->dropUnique(['request_key']);
            $t->dropColumn(['request_key', 'payload_hash', 'destination_name', 'sender_name']);
            $t->enum('to_entity', ['jihans', 'hendhys'])->change();
        });
    }
};
