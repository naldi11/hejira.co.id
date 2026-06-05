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
        $query = HendhysTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

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
            'filters'      => $request->only('search'),
        ]);
    }

    /**
     * Reprint a sale from history → the SAME 80mm thermal receipt used after a sale
     * (one template, consistent output). Kept as a Blade print document.
     */
    public function show($id)
    {
        $transaction = HendhysTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'branch', 'payments'])->findOrFail($id);

        return view('hendhys.pos.receipt', compact('transaction'));
    }
}
