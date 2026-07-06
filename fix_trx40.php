<?php
// Script untuk memperbaiki Transaksi 40 yang nyangkut antara Pasar 6 dan Pasar 10
$trxId = 40;
$targetBranchId = 3; // Pasar 10

echo "Memulai perbaikan transaksi ID $trxId...\n";

$trx = \App\Models\HendhysTransaction::find($trxId);
if (!$trx) {
    echo "Transaksi tidak ditemukan!\n";
    exit;
}

if ($trx->branch_id != $targetBranchId) {
    echo "Memindahkan transaksi dari cabang {$trx->branch_id} ke $targetBranchId...\n";
    $trx->branch_id = $targetBranchId;
    $trx->save();
} else {
    echo "Transaksi sudah berada di cabang $targetBranchId.\n";
}

$movements = \App\Models\HendhysStockMovement::where('source', 'pos_sale')->where('reference_id', $trxId)->get();
echo "Ditemukan " . $movements->count() . " pergerakan stok.\n";

$fixedCount = 0;
foreach($movements as $mov) {
    if ($mov->branch_id != $targetBranchId) {
        $oldBranchId = $mov->branch_id;
        $qty = $mov->quantity;
        
        // 1. Kembalikan stok ke cabang yang salah
        $oldStock = \App\Models\HendhysStockBranch::firstOrCreate([
            'branch_id' => $oldBranchId, 
            'product_id' => $mov->product_id
        ]);
        $oldStock->quantity += $qty;
        $oldStock->save();
        
        // 2. Potong stok dari cabang yang benar (Pasar 10)
        $newStock = \App\Models\HendhysStockBranch::firstOrCreate([
            'branch_id' => $targetBranchId, 
            'product_id' => $mov->product_id
        ]);
        $newStock->quantity -= $qty;
        $newStock->save();
        
        // 3. Update branch_id di riwayat pergerakan stok
        $mov->branch_id = $targetBranchId;
        $mov->save();
        
        $fixedCount++;
        echo " - Stok produk {$mov->product_id} dikembalikan ke cabang $oldBranchId dan dipotong dari cabang $targetBranchId.\n";
    }
}

echo "Selesai. $fixedCount pergerakan stok berhasil diperbaiki!\n";
