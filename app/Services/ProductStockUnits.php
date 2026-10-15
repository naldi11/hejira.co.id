<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Current balances use the product master unit; quantities and history are preserved. */
class ProductStockUnits
{
    public const TABLES = ['jihans_gudang_stock', 'jihans_retail_stock', 'hendhys_stock_pusat', 'hendhys_stock_branch', 'izzi_stock'];

    public function synchronize(int $productId, int $unitId): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('product_id', $productId)->where('unit_id', '!=', $unitId)->update(['unit_id' => $unitId]);
            }
        }
        if (Schema::hasTable('production_lots')) {
            DB::table('production_lots')->where('product_id', $productId)->where('remaining', '>', 0)
                ->where('unit_id', '!=', $unitId)->update(['unit_id' => $unitId]);
        }
    }
}
