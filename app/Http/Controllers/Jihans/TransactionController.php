<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jihans\TransactionResource;
use App\Models\JihansTransaction;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = JihansTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

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
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'filters'      => $request->only(['search', 'date', 'start_date', 'end_date', 'shift_id']),
        ]);
    }

    /**
     * The faktur is a standalone HTML print document (preview-first; the user prints
     * it to a dot-matrix LX-310, 9.5"×5.5" 3-ply form). Kept as a Blade view.
     */
    public function show($id)
    {
        $transaction = JihansTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'payments'])->findOrFail($id);

        return view('jihans.transactions.show', compact('transaction'));
    }

    /** Same faktur as a downloadable PDF (for archive / sending). */
    public function pdf($id, InvoiceService $invoiceService)
    {
        return $invoiceService->generateJihansInvoice(JihansTransaction::findOrFail($id));
    }
}
