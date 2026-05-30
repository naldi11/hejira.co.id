<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\GudangReturn;
use App\Models\GudangReturnDetail;
use App\Models\JihansStock;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GudangReturnController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $q = GudangReturn::where('from_entity', 'jihans')
            ->with(['creator', 'receiver']);

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('return_number', 'like', "%$search%");
        }

        $returns = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('jihans.returns-gudang.index', compact('returns'));
    }

    public function create()
    {
        // Ambil produk yang ada di stok Jihans dan quantity > 0
        $products = Product::where('status', 'active')
            ->join('jihans_stock', 'master_products.id', '=', 'jihans_stock.product_id')
            ->where('jihans_stock.quantity', '>', 0)
            ->select('master_products.*', 'jihans_stock.quantity as current_stock')
            ->with('unit')
            ->orderBy('master_products.name')
            ->get();

        $units = Unit::all();

        return view('jihans.returns-gudang.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

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
            DB::transaction(function () use ($request, $user) {
                $ret = GudangReturn::create([
                    'return_number' => $this->numbers->generateYearly('RET-JHS-GDG', 'gudang_returns', 'return_number'),
                    'from_entity' => 'jihans',
                    'branch_id' => null,
                    'date' => $request->date,
                    'status' => 'sent',
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($request->items as $item) {
                    $stokJihans = JihansStock::where('product_id', $item['product_id'])->first();
                        
                    if (!$stokJihans || $stokJihans->quantity < $item['quantity']) {
                        throw new \Exception("Stok Jihans tidak mencukupi untuk diretur (Produk ID: {$item['product_id']})");
                    }

                    GudangReturnDetail::create([
                        'return_id' => $ret->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'condition' => $item['condition'],
                        'notes' => $item['notes'] ?? null
                    ]);

                    // Potong stok Jihans
                    $this->stockService->debitJihans(
                        $item['product_id'],
                        $item['quantity'],
                        'return_gudang',
                        $ret->id,
                        $user->id
                    );
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
        if ($returns_to_gudang->from_entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }

        $return = $returns_to_gudang->load(['creator', 'receiver', 'details.product', 'details.unit']);
        return view('jihans.returns-gudang.show', compact('return'));
    }
}
