<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL commits each DDL statement. Resume after interruption without
        // dropping existing columns, snapshots, or quarantine receipts.
        foreach (['gudang_returns', 'hendhys_returns_from_branch'] as $name) {
            foreach (['origin_name', 'sender_name'] as $column) {
                if (!Schema::hasColumn($name, $column)) Schema::table($name, fn (Blueprint $t) => $t->string($column)->nullable());
            }
        }
        if (!Schema::hasColumn('hendhys_returns_from_branch', 'received_at')) {
            Schema::table('hendhys_returns_from_branch', fn (Blueprint $t) => $t->timestamp('received_at')->nullable());
        }
        foreach (['gudang_return_details', 'hendhys_return_details'] as $name) {
            foreach (['product_name', 'product_code', 'unit_name'] as $column) {
                if (!Schema::hasColumn($name, $column)) Schema::table($name, fn (Blueprint $t) => $t->string($column)->nullable());
            }
        }
        if (!Schema::hasColumn('gudang_return_details', 'sent_condition')) {
            Schema::table('gudang_return_details', fn (Blueprint $t) => $t->string('sent_condition', 100)->nullable());
        }
        if (!Schema::hasColumn('gudang_return_details', 'receiving_notes')) {
            Schema::table('gudang_return_details', fn (Blueprint $t) => $t->text('receiving_notes')->nullable());
        }
        if (!Schema::hasTable('return_quarantine_entries')) Schema::create('return_quarantine_entries', function (Blueprint $t) {
            $t->id(); $t->string('source_type', 20); $t->unsignedBigInteger('return_id'); $t->unsignedBigInteger('detail_id');
            $t->string('return_number'); $t->string('destination_key', 30); $t->string('origin_entity', 20)->nullable();
            $t->unsignedBigInteger('origin_branch_id')->nullable(); $t->string('origin_name');
            $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('unit_id');
            $t->string('product_name'); $t->string('product_code')->nullable(); $t->string('unit_name');
            $t->decimal('quantity', 15, 3); $t->string('condition', 20); $t->string('sent_condition', 100)->nullable(); $t->string('received_condition', 100)->nullable();
            $t->text('reason')->nullable(); $t->text('receiving_notes')->nullable(); $t->date('return_date');
            $t->unsignedBigInteger('sent_by')->nullable(); $t->string('sent_by_name')->nullable();
            $t->unsignedBigInteger('received_by')->nullable(); $t->string('received_by_name')->nullable();
            $t->timestamp('received_at')->nullable(); $t->timestamp('recorded_at'); $t->boolean('legacy')->default(false);
        });
        // Index creation can be interrupted after CREATE TABLE on MySQL too.
        // Restore the uniqueness guard before the backfill is allowed to run.
        if (!Schema::hasIndex('return_quarantine_entries', ['source_type', 'detail_id'], 'unique')) {
            Schema::table('return_quarantine_entries', fn (Blueprint $t) => $t->unique(['source_type', 'detail_id']));
        }
        foreach ([['destination_key', 'product_id'], ['origin_branch_id']] as $columns) {
            if (!Schema::hasIndex('return_quarantine_entries', $columns)) {
                Schema::table('return_quarantine_entries', fn (Blueprint $t) => $t->index($columns));
            }
        }
        // The unique source/detail key and insertOrIgnore in record() also let
        // a partially completed backfill resume without duplicate receipts.
        // Never credit stock a second time.
        foreach (['gudang', 'hendhys'] as $source) {
            $model = $source === 'gudang' ? \App\Models\GudangReturn::class : \App\Models\HendhysReturnFromBranch::class;
            $model::where('status', 'received')->with(['branch', 'creator', 'receiver', 'details.product', 'details.unit'])->chunkById(100, function ($returns) use ($source) {
                foreach ($returns as $return) app(\App\Services\ReturnQuarantine::class)->record($source, $return, true);
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('return_quarantine_entries');
        foreach (['gudang_returns', 'hendhys_returns_from_branch'] as $name) Schema::table($name, fn (Blueprint $t) => $t->dropColumn(['origin_name', 'sender_name']));
        Schema::table('hendhys_returns_from_branch', fn (Blueprint $t) => $t->dropColumn('received_at'));
        foreach (['gudang_return_details', 'hendhys_return_details'] as $name) Schema::table($name, fn (Blueprint $t) => $t->dropColumn(['product_name', 'product_code', 'unit_name']));
        Schema::table('gudang_return_details', fn (Blueprint $t) => $t->dropColumn(['sent_condition', 'receiving_notes']));
    }
};
