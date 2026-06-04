<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jihans\TransactionResource;
use App\Models\JihansTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = JihansTransaction::with(['creator', 'customer'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('transaction_number', 'like', "%{$request->search}%")
                ->orWhere('customer_name', 'like', "%{$request->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'filters'      => $request->only('search'),
        ]);
    }

    /** The receipt/faktur is a standalone print document — kept as a Blade view. */
    public function show($id)
    {
        $transaction = JihansTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'payments'])->findOrFail($id);

        return view('jihans.transactions.show', compact('transaction'));
    }
}
