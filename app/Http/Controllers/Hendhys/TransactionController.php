<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysTransaction;
use Illuminate\Http\Request;

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

        return view('hendhys.transactions.index', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = HendhysTransaction::with(['details.product', 'creator', 'customer', 'branch'])->findOrFail($id);
        return view('hendhys.transactions.show', compact('transaction'));
    }
}
