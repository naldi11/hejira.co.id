<?php

use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\HendhysStockBranch;
use App\Models\HendhysStockPusat;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MEMULAI KOREKSI DATA STOK HENDHYS ===\n";

DB::transaction(function() {
    // 1. Cari branch pusat
    $pusatBranch = Branch::where('type', 'pusat')->first();
    if (!$pusatBranch) {
        echo "Error: Branch bertipe 'pusat' tidak ditemukan.\n";
        return;
    }
    
    echo "Ditemukan Branch Pusat: {$pusatBranch->name} (ID: {$pusatBranch->id})\n";

    // 2. Ambil stok salah dari hendhys_stock_branch
    $wrongStocks = HendhysStockBranch::where('branch_id', $pusatBranch->id)->get();
    
    if ($wrongStocks->isEmpty()) {
        echo "Tidak ada data stok salah di hendhys_stock_branch untuk branch pusat.\n";
    } else {
        echo "Ditemukan " . $wrongStocks->count() . " item stok salah.\n";
        
        foreach ($wrongStocks as $ws) {
            $product = $ws->product;
            $productName = $product ? $product->name : "Product ID: {$ws->product_id}";
            echo "Memproses {$productName}: qty {$ws->quantity}...\n";
            
            // Pindahkan/tambahkan ke hendhys_stock_pusat
            $pusatStock = HendhysStockPusat::firstOrCreate(
                ['product_id' => $ws->product_id],
                ['quantity' => 0, 'unit_id' => $ws->unit_id, 'last_updated' => now()]
            );
            
            $before = $pusatStock->quantity;
            $after = $before + $ws->quantity;
            $pusatStock->update([
                'quantity' => $after,
                'last_updated' => now()
            ]);
            
            echo " - Stok Pusat diperbarui: {$before} -> {$after}\n";
            
            // Hapus dari hendhys_stock_branch
            $ws->delete();
            echo " - Stok salah di hendhys_stock_branch dihapus.\n";
        }
    }

    // 3. Perbaiki stock movements
    $affectedMovements = DB::table('hendhys_stock_movements')
        ->where('branch_id', $pusatBranch->id)
        ->update(['branch_id' => null]);
        
    echo "Diperbarui {$affectedMovements} riwayat pergerakan stok (branch_id diset ke NULL).\n";
});

echo "=== SELESAI ===\n";
