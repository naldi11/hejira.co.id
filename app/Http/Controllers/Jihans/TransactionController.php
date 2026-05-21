<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = JihansTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('jihans.transactions.index', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = JihansTransaction::with(['details.product', 'creator', 'customer'])->findOrFail($id);
        return view('jihans.transactions.show', compact('transaction'));
    }
}
