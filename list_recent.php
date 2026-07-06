<?php
$trxs = \App\Models\HendhysTransaction::orderBy('id', 'desc')->limit(20)->get();
foreach($trxs as $t) {
    echo "Trx {$t->id} | Branch: {$t->branch_id} | User: {$t->created_by} | Time: {$t->created_at}\n";
}
