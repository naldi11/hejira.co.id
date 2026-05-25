<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysProduction;
use App\Models\HendhysProductionDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        // Pastikan hanya Pusat yang bisa akses
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Cabang Pusat yang dapat mengakses modul Produksi.');
        }

        $q = HendhysProduction::with(['creator', 'details.product', 'details.unit']);

        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->date_to);
        }
        if ($search = $request->search) {
            $q->where('production_number', 'like', "%$search%");
        }

        $productions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('hendhys.productions.index', compact('productions'));
    }

    public function create()
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Cabang Pusat yang dapat mengakses modul Produksi.');
        }

        // Ambil produk hasil produksi sendiri yang terlihat di Hendhys
        $products = Product::where('status', 'active')
            ->where('source_type', 'produced')
            ->visibleInHendhys()
            ->orderBy('name')
            ->get();
            
        $units = Unit::all();

        return view('hendhys.productions.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $production = HendhysProduction::create([
                    'production_number' => $this->numbers->generateYearly('HND-PRD', 'hendhys_productions', 'production_number'),
                    'date' => $request->date,
                    'notes' => $request->notes,
                    'created_by' => auth()->id()
                ]);

                foreach ($request->items as $item) {
                    HendhysProductionDetail::create([
                        'production_id' => $production->id,
                        'product_id' => $item['product_id'],
                        'quantity_produced' => $item['quantity_produced'],
                        'unit_id' => $item['unit_id']
                    ]);

                    // Tambah stok ke Hendhys Pusat
                    $this->stockService->creditHendhys(
                        $item['product_id'],
                        $item['unit_id'],
                        $item['quantity_produced'],
                        null, // branch_id null = Pusat
                        'production',
                        $production->id,
                        auth()->id()
                    );
                }
            });

            return redirect()->route('hendhys.productions.index')
                ->with('success', 'Catatan produksi Hendhys berhasil disimpan dan stok Pusat telah bertambah.');
                
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan produksi: ' . $e->getMessage());
        }
    }

    public function show(HendhysProduction $production)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $production->load(['creator', 'details.product', 'details.unit']);
        return view('hendhys.productions.show', compact('production'));
    }
}
