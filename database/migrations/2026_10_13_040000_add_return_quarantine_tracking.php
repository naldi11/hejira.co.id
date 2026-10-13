<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        foreach (['gudang_returns', 'hendhys_returns_from_branch'] as $name) Schema::table($name, function (Blueprint $t) { $t->string('origin_name')->nullable(); $t->string('sender_name')->nullable(); });
        Schema::table('hendhys_returns_from_branch', fn (Blueprint $t) => $t->timestamp('received_at')->nullable());
        foreach (['gudang_return_details', 'hendhys_return_details'] as $name) Schema::table($name, function (Blueprint $t) { $t->string('product_name')->nullable(); $t->string('product_code')->nullable(); $t->string('unit_name')->nullable(); });
        Schema::table('gudang_return_details', function (Blueprint $t) { $t->string('sent_condition', 100)->nullable(); $t->text('receiving_notes')->nullable(); });
        Schema::create('return_quarantine_entries', function (Blueprint $t) {
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
            $t->unique(['source_type', 'detail_id']); $t->index(['destination_key', 'product_id']); $t->index('origin_branch_id');
        });
        // Read existing receipts into the separate register; never credit stock a second time.
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
