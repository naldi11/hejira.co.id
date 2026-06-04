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

    public function show($id)
    {
        // ⏭️ Show tetap Blade (faktur/print)
        $transaction = HendhysTransaction::with(['details.product', 'creator', 'customer', 'branch'])->findOrFail($id);
        return view('hendhys.transactions.show', compact('transaction'));
    }
}
