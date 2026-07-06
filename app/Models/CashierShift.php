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
        'total_expenses',
        'expenses_detail',
        'discrepancy',
        'note',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'total_expenses' => 'integer',
        'expenses_detail' => 'array',
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

    /**
     * Get complete payment summary for the shift
     */
    public function calculatePaymentSummary(): array
    {
        $entity = $this->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';
        $hasPtypeCol = ($entity === 'hendhys');

        $closedAt = $this->closed_at ?? now();
        
        $previousShift = self::where('user_id', $this->user_id)
            ->where('branch_id', $this->branch_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();

        $startAt = $previousShift ? $previousShift->closed_at : \Carbon\Carbon::parse($this->opened_at)->startOfDay();

        $summary = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', $this->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$startAt, $closedAt])
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN pm.type = 'tunai' THEN LEAST(p.amount, t.grand_total)
                    WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN LEAST(p.amount, t.grand_total)
                    " . ($hasPtypeCol ? "WHEN p.payment_method_id IS NULL AND p.payment_type = 'tunai' THEN LEAST(p.amount, t.grand_total)" : "") . "
                    ELSE 0 END), 0) as tunai,
                COALESCE(SUM(CASE
                    WHEN pm.type = 'transfer' THEN p.amount
                    " . ($hasPtypeCol ? "WHEN p.payment_method_id IS NULL AND p.payment_type = 'transfer' THEN p.amount" : "") . "
                    WHEN p.payment_method_id IS NULL AND p.payment_method = 'transfer' AND " . ($hasPtypeCol ? "p.payment_type IS NULL" : "1") . " THEN p.amount
                    ELSE 0 END), 0) as transfer,
                COALESCE(SUM(CASE
                    WHEN pm.type = 'kartu_debit' THEN p.amount
                    " . ($hasPtypeCol ? "WHEN p.payment_method_id IS NULL AND p.payment_type = 'kartu_debit' THEN p.amount" : "") . "
                    ELSE 0 END), 0) as kartu_debit,
                COALESCE(SUM(CASE
                    WHEN pm.type = 'kartu_kredit' THEN p.amount
                    " . ($hasPtypeCol ? "WHEN p.payment_method_id IS NULL AND p.payment_type = 'kartu_kredit' THEN p.amount" : "") . "
                    ELSE 0 END), 0) as kartu_kredit,
                COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END), 0) as kredit
            ")
            ->first();

        return (array) $summary;
    }
    /**
     * Get sales summary for the shift
     */
    public function calculateSalesSummary(): array
    {
        $entity = $this->entity;
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';

        $closedAt = $this->closed_at ?? now();

        $previousShift = self::where('user_id', $this->user_id)
            ->where('branch_id', $this->branch_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();

        $startAt = $previousShift ? $previousShift->closed_at : \Carbon\Carbon::parse($this->opened_at)->startOfDay();

        $summary = \Illuminate\Support\Facades\DB::table($transactionTable)
            ->where('created_by', $this->user_id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startAt, $closedAt])
            ->selectRaw("
                COUNT(id) as jumlah_transaksi,
                COALESCE(SUM(discount_amount), 0) as tot_potongan,
                COALESCE(SUM(tax_amount), 0) as tot_pajak,
                0 as tot_biaya,
                COALESCE(SUM(grand_total), 0) as total
            ")
            ->first();

        return (array) $summary;
    }
}
