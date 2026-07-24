<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysGudangReturnResource;
use App\Models\GudangReturn;
use App\Models\GudangReturnDetail;
use App\Models\HendhysStockPusat;
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
        $user = auth()->user();

        $q = GudangReturn::where('from_entity', 'hendhys')
            ->where('branch_id', $user->branch_id)
            ->with(['creator', 'receiver']);

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('return_number', 'like', "%$search%");
        }

        $returns = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Hendhys/ReturnsToGudang/Index', [
            'returns' => HendhysGudangReturnResource::collection($returns),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $isPusat = !$user->branch || $user->branch->type === 'pusat';

        if ($isPusat) {
            $products = Product::where('status', 'active')
                ->join('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
                ->where('hendhys_stock_pusat.quantity', '>', 0)
                ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock');
        } else {
            $products = Product::where('status', 'active')
                ->join('hendhys_stock_branch', 'master_products.id', '=', 'hendhys_stock_branch.product_id')
                ->where('hendhys_stock_branch.branch_id', $user->branch_id)
                ->where('hendhys_stock_branch.quantity', '>', 0)
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock');
        }

        $products = $products->with('unit')
            ->orderBy('master_products.name')
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'code'          => $p->code,
                'unit_id'       => $p->unit_id,
                'unit'          => $p->unit?->abbreviation ?? 'PCS',
                'current_stock' => (float) $p->current_stock,
            ]);

        $units = Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);

        return Inertia::render('Hendhys/ReturnsToGudang/Create', [
            'products' => $products,
            'units'    => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isPusat = !$user->branch || $user->branch->type === 'pusat';

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_id' => 'required|exists:master_units,id',
            'items.*.condition' => 'required|string|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $user, $isPusat) {
                $ret = GudangReturn::create([
                    'return_number' => $this->numbers->generateYearly('RET-HND-GDG', 'gudang_returns', 'return_number'),
                    'from_entity' => 'hendhys',
                    'branch_id' => $user->branch_id,
                    'date' => $request->date,
                    'status' => 'sent',
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($request->items as $item) {
                    if ($isPusat) {
                        $stok = HendhysStockPusat::where('product_id', $item['product_id'])->first();
                    } else {
                        $stok = \App\Models\HendhysStockBranch::where('branch_id', $user->branch_id)
                            ->where('product_id', $item['product_id'])->first();
                    }

                    if (!$stok || $stok->quantity < $item['quantity']) {
                        throw new \Exception("Stok tidak mencukupi untuk diretur (Produk ID: {$item['product_id']})");
                    }

                    GudangReturnDetail::create([
                        'return_id' => $ret->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'condition' => $item['condition'],
                        'notes' => $item['notes'] ?? null
                    ]);

                    // Potong stok Hendhys (Pusat atau Cabang secara otomatis lewat debitHendhys)
                    $this->stockService->debitHendhys(
                        $item['product_id'],
                        $item['quantity'],
                        $user->branch_id,
                        'return_gudang',
                        $ret->id,
                        $user->id
                    );
                }
            });

            return redirect()->route('hendhys.returns-to-gudang.index')
                ->with('success', 'Return ke Gudang berhasil dikirim.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat return: ' . $e->getMessage());
        }
    }

    public function show(GudangReturn $returns_to_gudang)
    {
        if ($returns_to_gudang->from_entity !== 'hendhys') {
            abort(403, 'Akses ditolak.');
        }

        if (!auth()->user()->hasRole('owner') && auth()->user()->branch_id !== $returns_to_gudang->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $return = $returns_to_gudang->load(['branch', 'creator', 'receiver', 'details.product', 'details.unit']);

        return Inertia::render('Hendhys/ReturnsToGudang/Show', [
            'return' => new HendhysGudangReturnResource($return),
        ]);
    }
}
