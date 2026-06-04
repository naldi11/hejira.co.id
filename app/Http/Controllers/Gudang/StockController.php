<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StockAdjustRequest;
use App\Http\Requests\Gudang\StockIndexRequest;
use App\Http\Resources\Gudang\ProductStockResource;
use App\Http\Resources\Gudang\StockMovementResource;
use App\Http\Resources\UnitResource;
use App\Models\GudangStockMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(StockIndexRequest $request)
    {
        $filters = $request->validated();

        $stocks = $this->stock->paginateGudangStock(
            $filters['search'] ?? null,
            ($filters['low_stock'] ?? null) === '1',
        );

        return Inertia::render('Gudang/Stock/Index', [
            'stocks'  => ProductStockResource::collection($stocks),
            'units'   => UnitResource::collection(Unit::orderBy('name')->get()),
            'filters' => [
                'search'    => $filters['search'] ?? '',
                'low_stock' => $filters['low_stock'] ?? '',
            ],
        ]);
    }

    public function movements(Request $request)
    {
        $movements = GudangStockMovement::with(['product', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$request->search}%")))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return Inertia::render('Gudang/Stock/Movements', [
            'movements' => StockMovementResource::collection($movements),
            'filters'   => $request->only('search', 'type', 'source'),
        ]);
    }

    public function adjust(StockAdjustRequest $request)
    {
        $data    = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        $this->stock->adjustGudang(
            $data['product_id'],
            $data['unit_id'],
            $data['quantity'],
            auth()->id(),
            $data['notes'],
        );

        $this->logger->log('update', 'gudang.stock', "Adjustment stok: {$product->name} → {$data['quantity']}", null);

        return back()->with('success', "Stok {$product->name} berhasil disesuaikan.");
    }
}
