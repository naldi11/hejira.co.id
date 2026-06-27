<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GudangDashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Stocks Tab Query
        $stocksQuery = GudangStock::with('product')
            ->when($request->stock_search, function($q) use ($request) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', '%'.$request->stock_search.'%')->orWhere('code', 'like', '%'.$request->stock_search.'%'));
            });
        $stocks = $stocksQuery->paginate(15, ['*'], 'stock_page')->withQueryString();

        // 2. Movements Tab Query
        $movementsQuery = \App\Models\GudangStockMovement::with(['product', 'creator'])
            ->when($request->movement_search, function($q) use ($request) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', '%'.$request->movement_search.'%'));
            });
        $movements = $movementsQuery->latest('id')->paginate(15, ['*'], 'movement_page')->withQueryString();

        // 3. PO Tab Query
        $poQuery = PurchaseOrder::with(['supplier', 'creator'])
            ->when($request->po_search, function($q) use ($request) {
                $q->where('po_number', 'like', '%'.$request->po_search.'%')
                  ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', '%'.$request->po_search.'%'));
            });
        $purchaseOrders = $poQuery->latest('id')->paginate(15, ['*'], 'po_page')->withQueryString();

        return Inertia::render('Owner/Gudang', [
            'stats' => [
                'po_pending'        => PurchaseOrder::whereIn('status', ['draft', 'sent'])->count(),
                'po_received'       => PurchaseOrder::where('status', 'received')->count(),
                'receive_month'     => Receiving::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
                'transfer_month'    => TransferOut::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
                'pending_requests'  => TransferRequest::where('status', 'pending')->count(),
            ],
            'stocks' => $stocks,
            'movements' => $movements,
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only('stock_search', 'movement_search', 'po_search'),
        ]);
    }
}
