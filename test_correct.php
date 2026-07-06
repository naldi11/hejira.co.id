<?php
$trxs = \App\Models\HendhysTransaction::all();
foreach($trxs as $t) {
    // Correct logic to find active shift at transaction time
    $shift = \App\Models\CashierShift::where("user_id", $t->created_by)
        ->where("opened_at", "<=", $t->created_at)
        ->orderBy("opened_at", "desc")
        ->first();
        
    if($shift && $shift->branch_id && $t->branch_id != $shift->branch_id) {
        echo "Trx {$t->id} (User {$t->created_by}) should be moved from {$t->branch_id} to {$shift->branch_id}\n";
    }
}
