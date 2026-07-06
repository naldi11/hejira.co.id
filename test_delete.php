<?php
$trxNumbers = ["HTRX-20261378", "HTRX-20261358", "HTRX-20261348", "HTRX-20261362", "HTRX-20261441", "HTRX-20261444", "HTRX-20261446"];
foreach($trxNumbers as $num) {
    $trx = \App\Models\HendhysTransaction::where("transaction_number", $num)->first();
    if($trx) {
        $movs = \App\Models\HendhysStockMovement::where("source", "pos_sale")->where("reference_id", $trx->id)->get();
        foreach($movs as $m) {
            if($m->branch_id) {
                $stock = \App\Models\HendhysStockBranch::where("branch_id", $m->branch_id)->where("product_id", $m->product_id)->first();
                if($stock) {
                    $stock->quantity += $m->quantity;
                    $stock->save();
                }
            } else {
                $stock = \App\Models\HendhysStockPusat::where("product_id", $m->product_id)->first();
                if($stock) {
                    $stock->quantity += $m->quantity;
                    $stock->save();
                }
            }
            $m->delete();
        }
        \App\Models\HendhysTransactionDetail::where("transaction_id", $trx->id)->delete();
        \App\Models\HendhysTransactionPayment::where("transaction_id", $trx->id)->delete();
        $trx->delete();
        echo "Berhasil menghapus dan mengembalikan stok untuk {$num}\n";
    } else {
        echo "Transaksi {$num} tidak ditemukan.\n";
    }
}
