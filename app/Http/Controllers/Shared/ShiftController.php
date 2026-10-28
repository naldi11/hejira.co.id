<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        return DB::transaction(function () use ($request) {
            $user = auth()->user();
            \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();

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
        }, 3);
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

        return DB::transaction(function () use ($request) {
            $user = auth()->user();
            $shift = app(\App\Services\SaleShiftGuard::class)->active($user);
            $closedAt = now();
            $cashSales = $shift->calculatePaymentSummary()['tunai'];

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
        }, 3);
    }

    /**
     * List shifts by date for frontend dropdowns
     */
    public function listByDate(Request $request)
    {
        $request->validate([
            'date'      => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'shift_id'  => 'nullable|integer',
            'entity'    => 'required|in:hendhys,jihans'
        ]);

        $user = auth()->user();
        abort_if($request->entity !== $this->routeEntity($request), 403, 'Entitas shift tidak sesuai.');
        $query = \App\Models\CashierShift::with('user')
            ->where('entity', $request->entity);

        // Dropdown "Pilih Shift" kini bisa mengirim rentang tanggal yang sedang
        // dipilih user. Sebelumnya tidak ada parameter tanggal sama sekali yang
        // dikirim, sehingga daftar selalu jatuh ke default 30 hari terakhir dan
        // shift yang lebih lama mustahil dipilih.
        $selectedShiftId = $request->integer('shift_id') ?: null;

        $query->where(function ($q) use ($request, $selectedShiftId) {
            $q->where(function ($w) use ($request) {
                if ($request->filled('date')) {
                    $w->whereDate('opened_at', $request->date);
                } elseif ($request->filled('date_from') || $request->filled('date_to')) {
                    if ($request->filled('date_from')) {
                        $w->whereDate('opened_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $w->whereDate('opened_at', '<=', $request->date_to);
                    }
                } else {
                    $w->where('opened_at', '>=', now()->subDays(30));
                }
            });

            // Shift yang sedang terpilih harus selalu ikut, walau di luar rentang —
            // kalau tidak, dropdown tampil kosong padahal filter sedang aktif.
            if ($selectedShiftId) {
                $q->orWhere('id', $selectedShiftId);
            }
        });

        if (!$user->hasRole(["admin_{$request->entity}", 'super_admin'])) {
            $query->where('user_id', $user->id);
        }

        $shifts = $query->orderBy('opened_at', 'desc')->take(100)->get()->map(function($shift) {
            return [
                'id' => $shift->id,
                'name' => \Carbon\Carbon::parse($shift->opened_at)->format('d M Y') . ' — Shift ' . \Carbon\Carbon::parse($shift->opened_at)->format('H:i') . ' - ' . ($shift->closed_at ? \Carbon\Carbon::parse($shift->closed_at)->format('H:i') : 'Aktif') . ' (' . ($shift->user->name ?? 'Unknown') . ')'
            ];
        });

        return response()->json($shifts);
    }

    /**
     * Get details for a specific cashier shift
     */
    public function show(CashierShift $shift)
    {
        abort_if($shift->entity !== $this->routeEntity(request()), 403, 'Entitas shift tidak sesuai.');
        Gate::authorize('view', $shift);

        $entity = $shift->entity;
        $paymentTable = ($entity === 'jihans') ? 'jihans_transaction_payments' : 'hendhys_transaction_payments';
        $transactionTable = ($entity === 'jihans') ? 'jihans_transactions' : 'hendhys_transactions';
        $detailTable = ($entity === 'jihans') ? 'jihans_transaction_details' : 'hendhys_transaction_details';

        $closedAt = $shift->periodEnd();
        $startAt  = $shift->periodStart();

        // Extra column exists in hendhys_transaction_payments for specific type tracking
        $hasPtypeCol = ($entity === 'hendhys');

        // 1. Rincian Metode Pembayaran
        $paymentSummary = $shift->calculatePaymentSummary();

        // 2. Daftar Transaksi
        $transactions = DB::table($transactionTable . ' as t')
            ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
            ->tap(fn ($q) => $shift->scopeTransactions($q))
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
            ->tap(fn ($q) => $shift->scopeTransactions($q))
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

    private function routeEntity(Request $request): string
    {
        return $request->routeIs('jihans.*') ? 'jihans' : 'hendhys';
    }
}
