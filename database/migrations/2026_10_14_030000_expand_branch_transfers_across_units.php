<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('branch_stock_transfers', 'to_entity')) Schema::table('branch_stock_transfers', fn (Blueprint $t) => $t->string('to_entity', 20)->default('hendhys'));
        $columns = [
            'quantity_scale' => fn ($t) => $t->unsignedInteger('quantity_scale')->default(1),
            'to_quantity_scale' => fn ($t) => $t->unsignedInteger('to_quantity_scale')->default(1),
            'to_product_id' => fn ($t) => $t->foreignId('to_product_id')->nullable()->constrained('master_products'),
            'to_unit_id' => fn ($t) => $t->foreignId('to_unit_id')->nullable()->constrained('master_units'),
            'to_product_name' => fn ($t) => $t->string('to_product_name')->nullable(),
            'to_product_code' => fn ($t) => $t->string('to_product_code')->nullable(),
            'to_unit_name' => fn ($t) => $t->string('to_unit_name')->nullable(),
            'conversion_from' => fn ($t) => $t->unsignedBigInteger('conversion_from')->default(1000),
            'conversion_to' => fn ($t) => $t->unsignedBigInteger('conversion_to')->default(1000),
            'destination_quantity' => fn ($t) => $t->unsignedBigInteger('destination_quantity')->nullable(),
            'received_destination_quantity' => fn ($t) => $t->unsignedBigInteger('received_destination_quantity')->default(0),
        ];
        foreach ($columns as $name => $add) if (!Schema::hasColumn('branch_stock_transfer_items', $name)) Schema::table('branch_stock_transfer_items', $add);
        if (!Schema::hasColumn('branch_stock_transfer_receipt_items', 'destination_quantity')) Schema::table('branch_stock_transfer_receipt_items', fn ($t) => $t->unsignedBigInteger('destination_quantity')->nullable());
        foreach (['hendhys_stock_movements','jihans_retail_stock_movements','izzi_stock_movements'] as $name) {
            if (!Schema::hasColumn($name, 'branch_transfer_id')) Schema::table($name, fn ($t) => $t->foreignId('branch_transfer_id')->nullable()->constrained('branch_stock_transfers'));
            if (!Schema::hasColumn($name, 'branch_transfer_item_id')) Schema::table($name, fn ($t) => $t->foreignId('branch_transfer_item_id')->nullable()->constrained('branch_stock_transfer_items'));
            if (!Schema::hasColumn($name, 'branch_transfer_side')) Schema::table($name, fn ($t) => $t->string('branch_transfer_side', 8)->nullable());
        }
    }
    public function down(): void
    {
        foreach (['hendhys_stock_movements','jihans_retail_stock_movements','izzi_stock_movements'] as $name) Schema::table($name, function ($t) use ($name) {
            $t->dropConstrainedForeignId('branch_transfer_item_id'); $t->dropColumn('branch_transfer_side');
            if ($name === 'izzi_stock_movements') $t->dropConstrainedForeignId('branch_transfer_id');
        });
        Schema::table('branch_stock_transfer_receipt_items', fn ($t) => $t->dropColumn('destination_quantity'));
        Schema::table('branch_stock_transfer_items', function ($t) {
            $t->dropConstrainedForeignId('to_product_id'); $t->dropConstrainedForeignId('to_unit_id');
            $t->dropColumn(['quantity_scale','to_quantity_scale','to_product_name','to_product_code','to_unit_name','conversion_from','conversion_to','destination_quantity','received_destination_quantity']);
        });
        Schema::table('branch_stock_transfers', fn ($t) => $t->dropColumn('to_entity'));
    }
};
