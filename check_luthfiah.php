<?php
$user = \App\Models\User::where('name', 'like', '%luthfiah%')->first();
if($user) {
    echo "Luthfiah ID: " . $user->id . "\n";
    $shifts = \App\Models\CashierShift::where('user_id', $user->id)->get();
    foreach($shifts as $s) {
        echo "Shift {$s->id}: Branch {$s->branch_id}, Opened: {$s->opened_at}\n";
    }
}
