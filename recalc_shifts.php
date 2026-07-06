<?php
$shifts = \App\Models\CashierShift::where("status", "closed")->get();
foreach($shifts as $shift) {
    $entity = $shift->entity;
    $previousShift = \App\Models\CashierShift::where('user_id', $shift->user_id)
        ->where('branch_id', $shift->branch_id)
        ->where('id', '<', $shift->id)
        ->orderBy('id', 'desc')
        ->first();
    $startAt = $previousShift ? $previousShift->closed_at : \Carbon\Carbon::parse($shift->opened_at)->startOfDay();
    
    $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
    $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';
    
    $cashSales = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
        ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
        ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
        ->where('t.created_by', $shift->user_id)
        ->where('t.status', '!=', 'cancelled')
        ->whereBetween('t.created_at', [$startAt, $shift->closed_at])
        ->sum(\Illuminate\Support\Facades\DB::raw("CASE
            WHEN pm.type = 'tunai' THEN LEAST(p.amount, t.grand_total)
            WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN LEAST(p.amount, t.grand_total)
            ELSE 0 END"));
            
    $expectedCash = $shift->starting_cash + (int)$cashSales - $shift->total_expenses;
    $discrepancy = $shift->actual_cash - $expectedCash;
    
    if ($shift->expected_cash != $expectedCash || $shift->discrepancy != $discrepancy) {
        echo "Shift {$shift->id} ({$shift->user->name}): Expected {$shift->expected_cash} -> {$expectedCash}, Discrepancy {$shift->discrepancy} -> {$discrepancy}\n";
        $shift->update([
            'expected_cash' => $expectedCash,
            'discrepancy' => $discrepancy
        ]);
    }
}
echo "Recalculation complete.\n";
