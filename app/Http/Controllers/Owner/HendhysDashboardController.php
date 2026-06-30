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

        $products = DB::table('master_products')
            ->where('status', 'active')
            ->where('visible_hendhys', true)
            ->orderBy('name')
            ->get();

        $pusatStocks = DB::table('hendhys_stock_pusat')
            ->get()
            ->keyBy('product_id');

        $cabangStocks = DB::table('hendhys_stock_branch as sb')
            ->join('master_branches as b', 'b.id', '=', 'sb.branch_id')
            ->select('sb.product_id', 'b.name as branch_name', 'sb.quantity', 'sb.quantity_return')
            ->get()
            ->groupBy('product_id');

        $consolidatedStocks = $products->map(function ($p) use ($pusatStocks, $cabangStocks) {
            $pusatQty = isset($pusatStocks[$p->id]) ? (float) $pusatStocks[$p->id]->quantity : 0.0;
            $pusatRet = isset($pusatStocks[$p->id]) ? (float) $pusatStocks[$p->id]->quantity_return : 0.0;

            $branches = [];
            $totalQty = $pusatQty;
            $totalRet = $pusatRet;

            if ($pusatQty > 0 || $pusatRet > 0) {
                $branches[] = [
                    'branch_name' => 'Hendhys Produksi',
                    'quantity' => $pusatQty,
                    'quantity_return' => $pusatRet,
                ];
            }

            if (isset($cabangStocks[$p->id])) {
                foreach ($cabangStocks[$p->id] as $cs) {
                    $qty = (float) $cs->quantity;
                    $ret = (float) $cs->quantity_return;
                    if ($qty > 0 || $ret > 0) {
                        $branches[] = [
                            'branch_name' => $cs->branch_name,
                            'quantity' => $qty,
                            'quantity_return' => $ret,
                        ];
                        $totalQty += $qty;
                        $totalRet += $ret;
                    }
                }
            }

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'branches' => $branches,
                'total_quantity' => $totalQty,
                'total_quantity_return' => $totalRet,
            ];
        });

        return Inertia::render('Owner/Hendhys', [
            'stats' => [
                'total_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'production_today' => HendhysProduction::whereDate('date', today())->count(),
            ],
            'transactions' => $transactions,
            'stocks' => $consolidatedStocks,
            'branches' => $branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => $request->only('search', 'date_from', 'date_to', 'status', 'branch_id'),
        ]);
    }
}
