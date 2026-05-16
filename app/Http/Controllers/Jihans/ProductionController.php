<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansProduction;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = JihansProduction::with(['product', 'unit', 'creator']);

        if ($search = $request->search) {
            $q->where('production_number', 'like', "%$search%");
        }

        if ($request->filled('date_from')) $q->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('date', '<=', $request->date_to);

        $productions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('jihans.productions.index', compact('productions'));
    }

    public function create()
    {
        // Hanya produk dengan jenis bahan_jadi atau yang mengandung kata Tortilla
        $products = Product::where('status', 'active')
            ->where(function($q) {
                $q->where('jenis', 'bahan_jadi')
                  ->orWhere('name', 'like', '%Tortilla%');
            })
            ->with('unit')
            ->get();
            
        $units = Unit::all();

        return view('jihans.productions.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'              => 'required|date',
            'product_id'        => 'required|exists:master_products,id',
            'size'              => 'required|in:kecil,sedang,besar',
            'quantity_produced' => 'required|numeric|min:0.001',
            'unit_id'           => 'required|exists:master_units,id',
            'notes'             => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $production = JihansProduction::create([
                'production_number' => $this->numbers->generateYearly('JHS-PRD', 'jihans_productions', 'production_number'),
                'date'              => $request->date,
                'product_id'        => $request->product_id,
                'size'              => $request->size,
                'quantity_produced' => $request->quantity_produced,
                'unit_id'           => $request->unit_id,
                'notes'             => $request->notes,
                'created_by'        => auth()->id(),
            ]);

            // Tambah stok barang jadi Jihan's
            $this->stock->creditJihans(
                $request->product_id,
                $request->unit_id,
                $request->quantity_produced,
                'production',
                $production->id,
                auth()->id()
            );

            $this->logger->log('create', 'jihans.production', "Input produksi tortilla: {$production->production_number}", $production);
        });

        return redirect()->route('jihans.productions.index')->with('success', 'Produksi berhasil dicatat.');
    }

    public function show(JihansProduction $production)
    {
        $production->load(['product', 'unit', 'creator']);
        return view('jihans.productions.show', compact('production'));
    }
}
