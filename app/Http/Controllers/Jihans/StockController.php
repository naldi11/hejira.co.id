<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansStockMovement;
use App\Models\Jihans\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::where('status', 'active')
            ->leftJoin('jihans_stock', 'jihans_products.id', '=', 'jihans_stock.product_id')
            ->select('jihans_products.*', 'jihans_stock.quantity as current_stock')
            ->with(['unit', 'category']);

        if ($search = $request->search) {
            $q->where(function($w) use ($search) {
                $w->where('jihans_products.name', 'like', "%$search%")
                  ->orWhere('jihans_products.code', 'like', "%$search%");
            });
        }

        if ($request->filled('jenis')) {
            $q->where('jihans_products.jenis', $request->jenis);
        }

        $stocks = $q->orderBy('jihans_products.name')->paginate(20)->withQueryString();

        return view('jihans.stock.index', compact('stocks'));
    }

    public function movements(Request $request)
    {
        $q = JihansStockMovement::with(['product', 'creator']);

        if ($search = $request->search) {
            $q->whereHas('product', fn($p) => $p->where('name', 'like', "%$search%"));
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $movements = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('jihans.stock.movements', compact('movements'));
    }
}
