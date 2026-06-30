<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jihans\StoreGudangReturnRequest;
use App\Http\Resources\Gudang\GudangReturnResource;
use App\Models\GudangReturn;
use App\Models\GudangReturnDetail;
use App\Models\GudangStock;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GudangReturnController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $returns = GudangReturn::where('from_entity', 'jihans')
            ->with(['creator', 'receiver'])->withCount('details')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('return_number', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Returns/Index', [
            'returns' => GudangReturnResource::collection($returns),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Jihans/Returns/Create', [
            'products' => Product::where('status', 'active')
                ->join('gudang_stock', 'master_products.id', '=', 'gudang_stock.product_id')
                ->where('gudang_stock.quantity', '>', 0)
                ->select('master_products.*', 'gudang_stock.quantity as current_stock')
                ->with('unit')->orderBy('master_products.name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id, 'unit_name' => $p->unit?->abbreviation ?? 'PCS', 'stock' => (float) $p->current_stock]),
            'units'    => Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]),
        ]);
    }

    public function store(StoreGudangReturnRequest $request)
    {
        $data = $request->validated();
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($data, $userId) {
                $ret = GudangReturn::create([
                    'return_number' => $this->numbers->generateYearly('RET-JHS-GDG', 'gudang_returns', 'return_number'),
                    'from_entity'   => 'jihans',
                    'branch_id'     => null,
                    'date'          => $data['date'],
                    'status'        => 'sent',
                    'notes'         => $data['notes'] ?? null,
                    'created_by'    => $userId,
                ]);

                foreach ($data['items'] as $item) {
                    $stock = GudangStock::where('product_id', $item['product_id'])->first();
                    if (! $stock || $stock->quantity < $item['quantity']) {
                        throw new \Exception("Stok Jihans tidak mencukupi untuk diretur (Produk ID: {$item['product_id']})");
                    }

                    GudangReturnDetail::create([
                        'return_id'  => $ret->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'unit_id'    => $item['unit_id'],
                        'condition'  => $item['condition'],
                        'notes'      => $item['notes'] ?? null,
                    ]);

                    $this->stockService->debitJihans($item['product_id'], $item['quantity'], 'return_gudang', $ret->id, $userId);
                }
            });

            return redirect()->route('jihans.returns-to-gudang.index')
                ->with('success', 'Retur barang ke Gudang berhasil dikirim dan stok Jihans telah dikurangi.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses retur: ' . $e->getMessage());
        }
    }

    public function show(GudangReturn $returns_to_gudang)
    {
        abort_if($returns_to_gudang->from_entity !== 'jihans', 403, 'Akses ditolak.');

        $returns_to_gudang->load(['creator', 'receiver', 'details.product.unit', 'details.unit']);

        return Inertia::render('Jihans/Returns/Show', [
            'return' => new GudangReturnResource($returns_to_gudang),
        ]);
    }
}
