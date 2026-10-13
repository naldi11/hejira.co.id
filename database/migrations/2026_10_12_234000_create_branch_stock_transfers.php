<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_transfer_actions', function (Blueprint $table) {
            $table->uuid('request_key')->primary();
            $table->string('payload_hash', 64);
        });
        Schema::create('branch_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->string('number', 40)->nullable()->unique();
            $table->foreignId('from_branch_id')->constrained('master_branches');
            $table->foreignId('to_branch_id')->constrained('master_branches');
            $table->string('from_branch_name');
            $table->string('to_branch_name');
            $table->string('from_entity', 20);
            $table->date('date');
            $table->string('status', 20)->default('sent');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('courier_name')->nullable();
            $table->foreignId('sent_by')->constrained('master_users');
            $table->string('sent_by_name');
            $table->timestamp('sent_at');
            $table->foreignId('cancelled_by')->nullable()->constrained('master_users');
            $table->string('cancelled_by_name')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->index(['from_branch_id', 'status']);
            $table->index(['to_branch_id', 'status']);
        });
        Schema::create('branch_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('branch_stock_transfers');
            $table->foreignId('product_id')->constrained('master_products');
            $table->foreignId('unit_id')->constrained('master_units');
            $table->string('product_name');
            $table->string('product_code');
            $table->string('unit_name');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('received_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->unique(['transfer_id', 'product_id']);
        });
        Schema::create('branch_stock_transfer_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_key')->unique();
            $table->foreignId('transfer_id')->constrained('branch_stock_transfers');
            $table->foreignId('received_by')->constrained('master_users');
            $table->string('received_by_name');
            $table->string('physical_receiver_name');
            $table->text('notes');
            $table->timestamp('received_at');
        });
        Schema::create('branch_stock_transfer_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('branch_stock_transfer_receipts');
            $table->foreignId('transfer_item_id')->constrained('branch_stock_transfer_items');
            $table->unsignedBigInteger('quantity');
            $table->unique(['receipt_id', 'transfer_item_id'], 'bst_receipt_item_unique');
        });
        foreach (['hendhys_stock_movements', 'jihans_retail_stock_movements'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                // A separate FK prevents collisions with existing numeric document references.
                $table->foreignId('branch_transfer_id')->nullable()->constrained('branch_stock_transfers');
            });
        }
    }

    public function down(): void
    {
        foreach (['hendhys_stock_movements', 'jihans_retail_stock_movements'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('branch_transfer_id'));
        }
        Schema::dropIfExists('branch_stock_transfer_receipt_items');
        Schema::dropIfExists('branch_stock_transfer_receipts');
        Schema::dropIfExists('branch_stock_transfer_items');
        Schema::dropIfExists('branch_stock_transfers');
        Schema::dropIfExists('branch_transfer_actions');
    }
};
