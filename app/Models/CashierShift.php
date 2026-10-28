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
     * Awal periode shift ini.
     *
     * Aturannya: mulai dari awal hari saat shift dibuka, KECUALI ada shift
     * sebelumnya (kasir & cabang yang sama) yang sudah ditutup di hari yang sama —
     * maka periode dimulai tepat saat shift sebelumnya ditutup.
     *
     * Penting: shift sebelumnya yang masih terbuka (closed_at = NULL) harus
     * diabaikan. Carbon::parse(null) mengembalikan waktu SEKARANG, bukan null,
     * sehingga versi lama bisa lolos cek isSameDay() lalu menetapkan
     * $startAt = null — dan `whereBetween('created_at', [null, ...])` tidak pernah
     * cocok dengan baris apa pun, membuat ringkasan kas shift jadi nol semua.
     */
    public function periodStart(): \Carbon\Carbon
    {
        $openedAt = \Carbon\Carbon::parse($this->opened_at);

        $previousShift = self::where('user_id', $this->user_id)
            ->where('entity', $this->entity)
            ->when($this->entity === 'hendhys', fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('id', '<', $this->id)
            ->whereNotNull('closed_at')
            ->orderBy('id', 'desc')
            ->first();

        if ($previousShift && \Carbon\Carbon::parse($previousShift->closed_at)->isSameDay($openedAt)) {
            return \Carbon\Carbon::parse($previousShift->closed_at);
        }

        return $openedAt->copy()->startOfDay();
    }

    /** Akhir periode shift: waktu tutup, atau sekarang bila shift masih berjalan. */
    public function periodEnd(): \Carbon\Carbon
    {
        return $this->closed_at ? \Carbon\Carbon::parse($this->closed_at) : now();
    }

    /** Explicit ownership for new sales; branch-scoped periods for historical rows. */
    public function scopeTransactions($query, string $alias = 't', $start = null): void
    {
        $branchId = $this->branch_id;
        if ($this->entity === 'hendhys' && $branchId && Branch::find($branchId)?->type === 'pusat') $branchId = null;
        $query->where($alias.'.created_by', $this->user_id)->where($alias.'.status', '!=', 'cancelled')
            ->where(function ($q) use ($alias, $start, $branchId) {
                $q->where($alias.'.shift_id', $this->id)->orWhere(function ($legacy) use ($alias, $start, $branchId) {
                    $legacy->whereNull($alias.'.shift_id')->whereBetween($alias.'.created_at', [$start ?? $this->periodStart(), $this->periodEnd()]);
                    if ($this->entity === 'hendhys') $legacy->where($alias.'.branch_id', $branchId);
                });
            });
    }

    /**
     * Calculate live expected cash so far for active shift
     */
    public function calculateExpectedCashSoFar(): int
    {
        if ($this->entity === 'izzi') return $this->starting_cash + app(\App\Services\Izzi\Sales::class)->shiftSummary($this)['tunai'];
        $entity = $this->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';

        $query = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id');
        $this->scopeTransactions($query, 't', $this->opened_at);
        $cashSales = $query->selectRaw(self::paymentSummaryColumns($this->entity === 'hendhys'))->first()->tunai;

        return $this->starting_cash + (int) $cashSales;
    }

    /**
     * Get complete payment summary for the shift
     */
    public function calculatePaymentSummary(): array
    {
        if ($this->entity === 'izzi') return app(\App\Services\Izzi\Sales::class)->shiftSummary($this);
        $entity = $this->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';
        $hasPtypeCol = ($entity === 'hendhys');

        $query = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id');
        $this->scopeTransactions($query);
        $summary = $query->selectRaw(self::paymentSummaryColumns($hasPtypeCol))->first();

        return (array) $summary;
    }
    /** Shared SQL keeps batch reports and a single shift's calculation identical. */
    public static function paymentSummaryColumns(bool $hasPtypeCol): string
    {
        $type = $hasPtypeCol
            ? "COALESCE(p.payment_type, pm.type, CASE WHEN p.payment_method IN ('cash','tunai') THEN 'tunai' ELSE p.payment_method END)"
            : "COALESCE(pm.type, CASE WHEN p.payment_method IN ('cash','tunai') THEN 'tunai' ELSE p.payment_method END)";
        $applied = 'CASE WHEN p.amount < t.grand_total THEN p.amount ELSE t.grand_total END';
        $columns = [];
        foreach (['tunai', 'transfer', 'kartu_debit', 'kartu_kredit'] as $kind) {
            $columns[] = "COALESCE(SUM(CASE WHEN {$type} = '{$kind}' THEN {$applied} ELSE 0 END), 0) as {$kind}";
        }
        $columns[] = "COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END), 0) as kredit";
        return implode(', ', $columns);
    }

    /**
     * Get sales summary for the shift
     */
    public function calculateSalesSummary(): array
    {
        if ($this->entity === 'izzi') return (array) \Illuminate\Support\Facades\DB::table('izzi_sales')->where('shift_id', $this->id)
            ->selectRaw("COUNT(*) as jumlah_transaksi, SUM(total - net_total) as tot_potongan, 0 as tot_pajak, 0 as tot_biaya, SUM(net_total) as total")->first();
        $entity = $this->entity;
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';

        $query = \Illuminate\Support\Facades\DB::table($transactionTable.' as t');
        $this->scopeTransactions($query);
        $summary = $query->selectRaw("
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
