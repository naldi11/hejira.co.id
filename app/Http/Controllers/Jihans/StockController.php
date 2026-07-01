<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gudang\ProductStockResource;
use App\Http\Resources\Gudang\StockMovementResource;
use App\Models\JihansRetailStockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $lowStockOnly = $request->low_stock === '1';

        $stocks = Product::where('status', 'active')
            ->where(fn ($w) => $w->visibleInJihans()->orWhereExists(fn ($sq) => $sq
                ->from('jihans_retail_stock')->whereColumn('jihans_retail_stock.product_id', 'master_products.id')))
            ->leftJoin('jihans_retail_stock', 'master_products.id', '=', 'jihans_retail_stock.product_id')
            ->leftJoin('jihans_gudang_stock', 'master_products.id', '=', 'jihans_gudang_stock.product_id')
            ->select('master_products.*', 'jihans_retail_stock.quantity as current_stock', 'jihans_gudang_stock.quantity as gudang_stock')
            ->with(['unit', 'category'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('master_products.name', 'like', "%{$request->search}%")
                ->orWhere('master_products.code', 'like', "%{$request->search}%")))
            ->when($request->filled('jenis'), fn ($q) => $q->where('master_products.jenis', $request->jenis))
            ->when($lowStockOnly, fn ($q) => $q->whereRaw('COALESCE(jihans_retail_stock.quantity, 0) <= master_products.stock_min')->where('master_products.stock_min', '>', 0))
            ->when($lowStockOnly, function ($q) {
                $q->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(jihans_retail_stock.quantity, 0) = 0 THEN 1 ELSE 0 END"), 'asc')
                  ->orderBy('jihans_retail_stock.quantity', 'desc');
            })
            ->orderBy('master_products.name')
            ->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Stock/Index', [
            'stocks'  => ProductStockResource::collection($stocks),
            'filters' => $request->only('search', 'jenis', 'low_stock'),
        ]);
    }

    public function movements(Request $request)
    {
        $movements = JihansRetailStockMovement::with(['product', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Stock/Movements', [
            'movements' => StockMovementResource::collection($movements),
            'filters'   => $request->only('search', 'type'),
        ]);
    }
}
