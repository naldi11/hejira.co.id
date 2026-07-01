<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierShift extends Model
{
    use HasFactory;

    protected $table = 'master_cashier_shifts';

    protected $fillable = [
        'user_id',
        'branch_id',
        'entity',
        'status',
        'opened_at',
        'closed_at',
        'starting_cash',
        'expected_cash',
        'actual_cash',
        'discrepancy',
        'note',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'discrepancy' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Calculate live expected cash so far for active shift
     */
    public function calculateExpectedCashSoFar(): int
    {
        $entity = $this->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';

        $cashSales = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', $this->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$this->opened_at, now()])
            ->sum(\Illuminate\Support\Facades\DB::raw("CASE
                WHEN pm.type = 'tunai' THEN LEAST(p.amount, t.grand_total)
                WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN LEAST(p.amount, t.grand_total)
                ELSE 0 END"));

        return $this->starting_cash + (int) $cashSales;
    }
}
