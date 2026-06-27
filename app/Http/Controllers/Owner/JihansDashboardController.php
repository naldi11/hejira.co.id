<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JihansProduction;
use App\Models\JihansTransaction;
use App\Models\JihansTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JihansDashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = JihansTransaction::with('creator')
            ->when($request->search, function($q) use ($request) {
                $q->where('transaction_number', 'like', '%'.$request->search.'%')
                  ->orWhere('customer_name', 'like', '%'.$request->search.'%');
            })
            ->when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        $transactions = $query->latest('id')->paginate(10)->withQueryString();

        $stocks = DB::table('master_products as p')
            ->leftJoin('jihans_stock as s', 'p.id', '=', 's.product_id')
            ->where('p.status', 'active')
            ->where(fn($w) => $w->whereRaw('p.visible_jihans = 1')->orWhereNotNull('s.quantity'))
            ->select('p.name', 'p.code', DB::raw('COALESCE(s.quantity, 0) as quantity'))
            ->orderBy('p.name')
            ->get();

        return Inertia::render('Owner/Jihans', [
            'stats' => [
                'total_revenue'    => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
                'revenue_today'    => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'production_today' => JihansProduction::whereDate('date', today())->count(),
            ],
            'transactions' => $transactions,
            'stocks' => $stocks,
            'filters' => $request->only('search', 'date_from', 'date_to', 'status'),
        ]);
    }
}
