<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JihansProduction;
use App\Models\JihansTransaction;
use App\Models\GudangStock;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JihansDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ── Jihans Transactions ──
        $query = JihansTransaction::with('creator')
            ->when($request->search, function($q) use ($request) {
                $q->where('transaction_number', 'like', '%'.$request->search.'%')
                  ->orWhere('customer_name', 'like', '%'.$request->search.'%');
            })
            ->when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        $transactions = $query->latest('id')->paginate(10, ['*'], 'jihans_page')->withQueryString();

        // ── Jihans Stocks ──
        $jihansStocks = DB::table('master_products as p')
            ->leftJoin('gudang_stock as s', 'p.id', '=', 's.product_id')
            ->where('p.status', 'active')
            ->where(fn($w) => $w->whereRaw('p.visible_jihans = 1')->orWhereNotNull('s.quantity'))
            ->select('p.name', 'p.code', DB::raw('COALESCE(s.quantity, 0) as quantity'))
            ->orderBy('p.name')
            ->get();

        // ── Gudang Stocks ──
        $stocksQuery = GudangStock::with('product')
            ->when($request->stock_search, function($q) use ($request) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', '%'.$request->stock_search.'%')->orWhere('code', 'like', '%'.$request->stock_search.'%'));
            });
        $gudangStocks = $stocksQuery->paginate(15, ['*'], 'stock_page')->withQueryString();

        // ── Gudang Movements ──
        $movementsQuery = \App\Models\GudangStockMovement::with(['product', 'creator'])
            ->when($request->movement_search, function($q) use ($request) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', '%'.$request->movement_search.'%'));
            });
        $movements = $movementsQuery->latest('id')->paginate(15, ['*'], 'movement_page')->withQueryString();

        // ── Gudang PO ──
        $poQuery = PurchaseOrder::with(['supplier', 'creator'])
            ->when($request->po_search, function($q) use ($request) {
                $q->where('po_number', 'like', '%'.$request->po_search.'%')
                  ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', '%'.$request->po_search.'%'));
            });
        $purchaseOrders = $poQuery->latest('id')->paginate(15, ['*'], 'po_page')->withQueryString();

        // ── Stats (Combined Jihans and Gudang) ──
        $stats = [
            'total_revenue'     => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
            'revenue_today'     => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
            'production_today'  => JihansProduction::whereDate('date', today())->count(),
            // Gudang stats
            'po_pending'        => PurchaseOrder::whereIn('status', ['draft', 'sent'])->count(),
            'po_received'       => PurchaseOrder::where('status', 'received')->count(),
            'receive_month'     => Receiving::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
            'transfer_month'    => TransferOut::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
            'pending_requests'  => TransferRequest::where('status', 'pending')->count(),
        ];

        return Inertia::render('Owner/Jihans', [
            'stats'          => $stats,
            'transactions'   => $transactions,
            'stocks'         => $jihansStocks,
            'gudangStocks'   => $gudangStocks,
            'movements'      => $movements,
            'purchaseOrders' => $purchaseOrders,
            'filters'        => $request->only('search', 'date_from', 'date_to', 'status', 'stock_search', 'movement_search', 'po_search'),
        ]);
    }
}
