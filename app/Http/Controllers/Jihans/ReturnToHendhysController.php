<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysReturnResource;
use App\Models\HendhysReturnFromBranch;
use App\Models\HendhysReturnDetail;
use App\Models\JihansRetailStock;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReturnToHendhysController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $q = HendhysReturnFromBranch::with(['branch', 'creator', 'receiver'])
            ->where('branch_id', $user->branch_id);

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('return_number', 'like', "%$search%");
        }

        $returns = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Jihans/ReturnsToHendhys/Index', [
            'returns' => HendhysReturnResource::collection($returns),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        // Get products that have stock in Jihans Retail Stock
        $products = Product::where('status', 'active')
            ->join('jihans_retail_stocks', 'master_products.id', '=', 'jihans_retail_stocks.product_id')
            ->where('jihans_retail_stocks.quantity', '>', 0)
            ->with('unit')
            ->select('master_products.*', 'jihans_retail_stocks.quantity as current_stock')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id, 
                'name' => $p->name, 
                'code' => $p->code, 
                'unit_id' => $p->unit_id, 
                'unit' => $p->unit?->abbreviation ?? 'PCS',
                'current_stock' => (float) $p->current_stock
            ]);
            
        $units = Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);

        return Inertia::render('Jihans/ReturnsToHendhys/Create', [
            'products' => $products,
            'units'    => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
            'items.*.condition' => 'required|in:rusak,basi,lainnya',
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                $ret = HendhysReturnFromBranch::create([
                    'return_number' => $this->numbers->generateYearly('RET-HND', 'hendhys_returns_from_branch', 'return_number'),
                    'branch_id' => $user->branch_id,
                    'date' => $request->date,
                    'status' => 'sent',
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($request->items as $item) {
                    // Cek stok cabang Jihans mencukupi untuk di retur
                    $stokCabang = JihansRetailStock::where('product_id', $item['product_id'])
                        ->first();

                    if (!$stokCabang || $stokCabang->quantity < $item['quantity']) {
                        throw new \Exception("Stok toko Jihan's tidak mencukupi untuk diretur (Produk ID: {$item['product_id']})");
                    }

                    HendhysReturnDetail::create([
                        'return_id' => $ret->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'condition' => $item['condition']
                    ]);

                    // Potong stok Jihan's Retail
                    $this->stockService->debitJihansRetail(
                        $item['product_id'],
                        $item['quantity'],
                        'return_to_hendhys',
                        $ret->id,
                        $user->id
                    );
                }
            });

            return redirect()->route('jihans.returns-to-hendhys.index')
                ->with('success', 'Retur barang berhasil dikirim ke Hendhys Pusat dan stok toko telah dikurangi.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses retur: ' . $e->getMessage());
        }
    }

    public function show(HendhysReturnFromBranch $returnsToHendhy)
    {
        $user = auth()->user();
        if ($returnsToHendhy->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $returnsToHendhy->load(['branch', 'creator', 'receiver', 'details.product', 'details.unit']);

        return Inertia::render('Jihans/ReturnsToHendhys/Show', [
            'return' => new HendhysReturnResource($returnsToHendhy),
        ]);
    }
}
