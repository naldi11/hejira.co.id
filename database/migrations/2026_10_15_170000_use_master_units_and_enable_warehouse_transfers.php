<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jihans_gudang_stock_movements', 'branch_transfer_id')) {
            Schema::table('jihans_gudang_stock_movements', fn (Blueprint $table) => $table->foreignId('branch_transfer_id')->nullable()->constrained('branch_stock_transfers'));
        }
        if (! Schema::hasColumn('jihans_gudang_stock_movements', 'branch_transfer_item_id')) {
            Schema::table('jihans_gudang_stock_movements', fn (Blueprint $table) => $table->foreignId('branch_transfer_item_id')->nullable()->constrained('branch_stock_transfer_items'));
        }
        if (! Schema::hasColumn('jihans_gudang_stock_movements', 'branch_transfer_side')) {
            Schema::table('jihans_gudang_stock_movements', fn (Blueprint $table) => $table->string('branch_transfer_side', 8)->nullable());
        }

        if (! DB::table('master_branches')->where('entity', 'gudang')->exists()) {
            $code = 'GD-MUTASI';
            for ($suffix = 1; DB::table('master_branches')->where('code', $code)->exists(); $suffix++) $code = 'GD-MUTASI-'.$suffix;
            DB::table('master_branches')->insert([
                'code' => $code, 'name' => 'Gudang Utama', 'type' => 'pusat', 'entity' => 'gudang', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('master_products')->select('id', 'unit_id')->whereNotNull('unit_id')->orderBy('id')->chunkById(200, function ($products) {
            foreach ($products as $product) app(\App\Services\ProductStockUnits::class)->synchronize($product->id, $product->unit_id);
        });
    }

    public function down(): void
    {
        Schema::table('jihans_gudang_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_transfer_item_id');
            $table->dropConstrainedForeignId('branch_transfer_id');
            $table->dropColumn('branch_transfer_side');
        });
        // Keep the warehouse identity, current master units, and all transaction history.
    }
};
