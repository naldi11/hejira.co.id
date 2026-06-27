<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HendhysProduction;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HendhysDashboardController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $query = HendhysTransaction::with(['creator', 'branch'])
            ->when($request->search, function($q) use ($request) {
                $q->where('transaction_number', 'like', '%'.$request->search.'%')
                  ->orWhere('customer_name', 'like', '%'.$request->search.'%');
            })
            ->when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->branch_id, function($q) use ($request) {
                if ($request->branch_id === 'pusat') {
                    $q->whereNull('branch_id');
                } else {
                    $q->where('branch_id', $request->branch_id);
                }
            });

        $transactions = $query->latest('id')->paginate(10)->withQueryString();

        // Get stocks for pusat
        $pusatStocks = DB::table('master_products as p')
            ->leftJoin('hendhys_stock_pusat as s', 'p.id', '=', 's.product_id')
            ->where('p.status', 'active')
            ->where(fn($w) => $w->whereRaw('p.visible_hendhys = 1')->orWhereNotNull('s.quantity'))
            ->select('p.name', 'p.code', DB::raw('COALESCE(s.quantity, 0) as quantity'), DB::raw('COALESCE(s.quantity_return, 0) as quantity_return'))
            ->orderBy('p.name')
            ->get();

        // Get stocks for cabang
        $cabangStocks = DB::table('master_products as p')
            ->join('hendhys_stock_branch as s', 'p.id', '=', 's.product_id')
            ->join('master_branches as b', 'b.id', '=', 's.branch_id')
            ->where('p.status', 'active')
            ->select('p.name', 'p.code', 'b.name as branch_name', 's.quantity', 's.quantity_return')
            ->orderBy('b.name')
            ->orderBy('p.name')
            ->get();

        return Inertia::render('Owner/Hendhys', [
            'stats' => [
                'total_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'production_today' => HendhysProduction::whereDate('date', today())->count(),
            ],
            'transactions' => $transactions,
            'pusatStocks' => $pusatStocks,
            'cabangStocks' => $cabangStocks,
            'branches' => $branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => $request->only('search', 'date_from', 'date_to', 'status', 'branch_id'),
        ]);
    }
}
