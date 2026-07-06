<?php
$trx = \App\Models\HendhysTransaction::find(40);
echo "Transaction 40:\n";
echo "Branch ID: " . $trx->branch_id . "\n";
echo "Created By: " . $trx->created_by . "\n";
echo "Total: " . $trx->grand_total . "\n";

$movements = \App\Models\HendhysStockMovement::where('source', 'pos_sale')->where('reference_id', 40)->get();
echo "\nStock Movements for Trx 40:\n";
foreach($movements as $m) {
    echo "ID: {$m->id}, Product: {$m->product_id}, Branch: {$m->branch_id}, Qty: {$m->quantity}\n";
}
