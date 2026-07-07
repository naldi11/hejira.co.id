<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShiftController extends Controller
{
    /**
     * Get active shift for current cashier
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        return response()->json([
            'active_shift' => $activeShift
        ]);
    }

    /**
     * Open cashier shift
     */
    public function open(Request $request)
    {
        $request->validate([
            'starting_cash' => 'required|integer|min:0',
        ]);

        $user = auth()->user();

        // Check if there is an active shift
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return back()->with('error', 'Anda masih memiliki shift aktif yang belum ditutup.');
        }

        // Create new shift
        CashierShift::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'entity' => $user->entity ?? 'hendhys', // Fallback
            'status' => 'open',
            'opened_at' => now(),
            'starting_cash' => $request->starting_cash,
        ]);

        return back()->with('success', 'Laci kasir berhasil dibuka. Selamat bekerja!');
    }

    /**
     * Close cashier shift
     */
    public function close(Request $request)
    {
        $request->validate([
            'actual_cash' => 'required|integer|min:0',
            'note' => 'nullable|string',
            'expenses' => 'nullable|array',
            'expenses.*.amount' => 'required|integer|min:1',
            'expenses.*.description' => 'required|string',
        ]);

        $user = auth()->user();
        $shift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return back()->with('error', 'Tidak ada shift aktif yang dapat ditutup.');
        }

        $closedAt = now();
        $entity = $shift->entity;

        $previousShift = \App\Models\CashierShift::where('user_id', $shift->user_id)
            ->where('branch_id', $shift->branch_id)
            ->where('id', '<', $shift->id)
            ->orderBy('id', 'desc')
            ->first();

        $startAt = \Carbon\Carbon::parse($shift->opened_at)->startOfDay();
        if ($previousShift && \Carbon\Carbon::parse($previousShift->closed_at)->isSameDay($shift->opened_at)) {
            $startAt = $previousShift->closed_at;
        }

        // Calculate total cash collected during shift
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';

        $cashSales = DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', $shift->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$startAt, $closedAt])
            ->sum(DB::raw("CASE
                WHEN pm.type = 'tunai' THEN LEAST(p.amount, t.grand_total)
                WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN LEAST(p.amount, t.grand_total)
                ELSE 0 END"));

        $expenses = $request->expenses ?? [];
        $totalExpenses = 0;
        foreach ($expenses as $expense) {
            $totalExpenses += (int) $expense['amount'];
        }

        $expectedCash = $shift->starting_cash + (int) $cashSales - $totalExpenses;
        $actualCash = (int) $request->actual_cash;
        $discrepancy = $actualCash - $expectedCash;

        $shift->update([
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'total_expenses' => $totalExpenses,
            'expenses_detail' => $expenses,
            'discrepancy' => $discrepancy,
            'status' => 'closed',
            'closed_at' => $closedAt,
            'note' => $request->note,
        ]);

        return back()->with('success', 'Laci kasir berhasil ditutup. Shift selesai.');
    }

    /**
     * Get details for a specific cashier shift
     */
    public function show(CashierShift $shift)
    {
        $user = auth()->user();
        
        // Ensure cashier can only see their own shifts
        if (($user->hasRole('kasir_hendhys') || $user->hasRole('kasir_jihans')) && $shift->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $entity = $shift->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';
        $detailTable = ($entity === 'jihans') ? 'jihans_transaction_details' : 'hendhys_transaction_details';

        $closedAt = $shift->closed_at ?? now();

        $previousShift = \App\Models\CashierShift::where('user_id', $shift->user_id)
            ->where('branch_id', $shift->branch_id)
            ->where('id', '<', $shift->id)
            ->orderBy('id', 'desc')
            ->first();

        $startAt = $previousShift ? $previousShift->closed_at : \Carbon\Carbon::parse($shift->opened_at)->startOfDay();

        // Extra column exists in hendhys_transaction_payments for specific type tracking
        $hasPtypeCol = ($entity === 'hendhys');

        // 1. Rincian Metode Pembayaran
        $paymentSummary = DB::table($paymentTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', $shift->user_id)
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

        // 2. Daftar Transaksi
        $transactions = DB::table($transactionTable . ' as t')
            ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.created_by', $shift->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$startAt, $closedAt])
            ->select([
                't.id',
                't.transaction_number',
                't.created_at',
                't.grand_total',
                't.status',
                DB::raw("COALESCE(c.name, t.customer_name, 'Pelanggan Umum') as customer_name")
            ])
            ->orderBy('t.created_at', 'desc')
            ->get();

        // 3. Ringkasan Produk Terjual
        $soldItems = DB::table($detailTable . ' as d')
            ->join($transactionTable . ' as t', 't.id', '=', 'd.transaction_id')
            ->where('t.created_by', $shift->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$startAt, $closedAt])
            ->select([
                'd.product_name',
                DB::raw('SUM(d.quantity) as total_qty'),
                'd.price',
                DB::raw('SUM(d.total) as total_amount')
            ])
            ->groupBy('d.product_id', 'd.product_name', 'd.price')
            ->orderBy('total_qty', 'desc')
            ->get();

        return response()->json([
            'shift' => $shift,
            'payment_summary' => $paymentSummary,
            'transactions' => $transactions,
            'sold_items' => $soldItems
        ]);
    }
}
