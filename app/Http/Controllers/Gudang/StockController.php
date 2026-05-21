<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\GudangStockMovement;
use App\Models\Gudang\Product;
use App\Models\Gudang\Unit;
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
        // Join gudang_products with gudang_stock (left join — show all products)
        $q = Product::with(['unit', 'category'])
            ->leftJoin('gudang_stock', 'gudang_products.id', '=', 'gudang_stock.product_id')
            ->select('gudang_products.*', 'gudang_stock.quantity as current_stock')
            ->where('gudang_products.status', 'active')
            ->where('gudang_products.product_type', 'INV');

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('gudang_products.name', 'like', "%$search%")
                                   ->orWhere('gudang_products.code', 'like', "%$search%"));
        }

        if ($request->filled('jenis')) $q->where('gudang_products.jenis', $request->jenis);

        if ($request->low_stock === '1') {
            $q->whereRaw('COALESCE(gudang_stock.quantity, 0) <= gudang_products.stock_min');
        }

        $stocks = $q->orderBy('gudang_products.name')->paginate(20)->withQueryString();
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
            'product_id' => 'required|exists:gudang_products,id',
            'unit_id'    => 'required|exists:gudang_units,id',
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
