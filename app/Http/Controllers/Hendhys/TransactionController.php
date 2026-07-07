<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysTransactionResource;
use App\Models\HendhysTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = HendhysTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

        if ($user->branch && $user->branch->type !== 'pusat') {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('shift_id')) {
            $shift = \App\Models\CashierShift::find($request->shift_id);
            if ($shift) {
                $query->whereBetween('created_at', [
                    $shift->opened_at,
                    $shift->closed_at ?? now()
                ]);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Hendhys/Transactions/Index', [
            'transactions' => HendhysTransactionResource::collection($transactions),
            'filters'      => $request->only(['search', 'date', 'start_date', 'end_date', 'shift_id']),
        ]);
    }

    /**
     * Reprint a sale from history → the SAME 80mm thermal receipt used after a sale
     * (one template, consistent output). Kept as a Blade print document.
     */
    public function show(\Illuminate\Http\Request $request, $id)
    {
        $transaction = HendhysTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'branch', 'payments'])->findOrFail($id);
        $paperSize = $request->input('paper_size', '58');

        return view('hendhys.pos.receipt', compact('transaction', 'paperSize'));
    }
}
