<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_material_sequences', function (Blueprint $t) {
            $t->date('date')->primary(); $t->unsignedInteger('last_number')->default(0);
        });
        Schema::create('production_material_transactions', function (Blueprint $t) {
            $t->id(); $t->uuid('request_key')->unique(); $t->string('payload_hash', 64);
            $t->string('number', 40)->nullable()->unique(); $t->string('kind', 24);
            $t->string('status', 20); $t->date('date')->index(); $t->timestamp('occurred_at');
            $t->foreignId('production_id')->nullable()->constrained('hendhys_productions');
            $t->string('production_number')->nullable(); $t->string('production_type', 16)->nullable();
            $t->string('sender_name'); $t->string('receiver_name'); $t->text('reason');
            $t->text('notes')->nullable(); $t->string('evidence_reference')->nullable();
            $t->foreignId('created_by')->constrained('master_users'); $t->string('created_by_name');
            $t->timestamp('created_at'); $t->timestamp('cancelled_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('master_users');
            $t->string('cancelled_by_name')->nullable(); $t->text('cancel_reason')->nullable();
        });
        Schema::create('production_material_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('transaction_id')->constrained('production_material_transactions');
            $t->foreignId('product_id')->constrained('master_products'); $t->foreignId('unit_id')->constrained('master_units');
            $t->string('product_name'); $t->string('product_code'); $t->string('unit_name');
            $t->unsignedInteger('quantity'); $t->unsignedBigInteger('source_before'); $t->unsignedBigInteger('source_after');
            $t->unsignedBigInteger('destination_before')->nullable(); $t->unsignedBigInteger('destination_after')->nullable();
            $t->foreignId('destination_lot_id')->nullable()->constrained('production_lots'); $t->text('notes')->nullable();
            $t->unique(['transaction_id', 'product_id'], 'production_material_item_unique');
        });
        Schema::create('production_material_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('transaction_id')->constrained('production_material_transactions');
            $t->string('action', 20); $t->foreignId('created_by')->constrained('master_users');
            $t->string('actor_name'); $t->text('reason'); $t->json('stock_changes'); $t->timestamp('created_at');
            $t->unique(['transaction_id', 'action'], 'production_material_event_unique');
        });
        foreach (['jihans_gudang_stock_movements', 'hendhys_stock_movements'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->foreignId('production_material_id')->nullable()->constrained('production_material_transactions'));
        }
    }

    public function down(): void
    {
        foreach (['jihans_gudang_stock_movements', 'hendhys_stock_movements'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('production_material_id'));
        }
        Schema::dropIfExists('production_material_events'); Schema::dropIfExists('production_material_items');
        Schema::dropIfExists('production_material_transactions'); Schema::dropIfExists('production_material_sequences');
    }
};
