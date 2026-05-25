<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\GudangStockMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        // Join master_products with gudang_stock (left join — show all products)
        $q = Product::with(['unit', 'category'])
            ->visibleInGudang()
            ->leftJoin('gudang_stock', 'master_products.id', '=', 'gudang_stock.product_id')
            ->select('master_products.*', 'gudang_stock.quantity as current_stock')
            ->where('master_products.status', 'active')
            ->where('master_products.product_type', 'INV');

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('master_products.name', 'like', "%$search%")
                                   ->orWhere('master_products.code', 'like', "%$search%"));
        }

        if ($request->low_stock === '1') {
            $q->whereRaw('COALESCE(gudang_stock.quantity, 0) <= master_products.stock_min');
        }

        $stocks = $q->orderBy('master_products.name')->paginate(20)->withQueryString();
        $units  = Unit::orderBy('name')->get();

        return view('gudang.stock.index', compact('stocks', 'units'));
    }

    public function movements(Request $request)
    {
        $q = GudangStockMovement::with(['product', 'creator']);

        if ($search = $request->search) {
            $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%$search%"));
        }

        if ($request->filled('type'))   $q->where('type', $request->type);
        if ($request->filled('source')) $q->where('source', $request->source);

        $movements = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('gudang.stock.movements', compact('movements'));
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:master_products,id',
            'unit_id'    => 'required|exists:master_units,id',
            'quantity'   => 'required|integer|min:0',
            'notes'      => 'required|string|max:200',
        ]);

        $product = Product::findOrFail($request->product_id);

        $this->stock->adjustGudang(
            $request->product_id,
            $request->unit_id,
            $request->quantity,
            auth()->id(),
            $request->notes
        );

        $this->logger->log('update', 'gudang.stock', "Adjustment stok: {$product->name} → {$request->quantity}", null);

        return back()->with('success', "Stok {$product->name} berhasil disesuaikan.");
    }
}
