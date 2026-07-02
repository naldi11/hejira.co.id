<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysReturnResource;
use App\Models\HendhysReturnFromBranch;
use App\Models\HendhysReturnDetail;
use App\Models\HendhysStockBranch;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $q = HendhysReturnFromBranch::with(['branch', 'creator', 'receiver']);

        if ($user->branch->type === 'cabang') {
            $q->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('return_number', 'like', "%$search%");
        }

        $returns = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Hendhys/Returns/Index', [
            'returns' => HendhysReturnResource::collection($returns),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        if (auth()->user()->branch->type !== 'cabang') {
            abort(403, 'Hanya Cabang yang dapat membuat retur barang.');
        }

        $products = Product::where('status', 'active')->with('unit')->orderBy('name')->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id, 'unit' => $p->unit?->abbreviation ?? 'PCS']);
        $units = Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);

        return Inertia::render('Hendhys/Returns/Create', [
            'products' => $products,
            'units'    => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->branch->type !== 'cabang') {
            abort(403, 'Akses ditolak.');
        }

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
                    // Cek stok cabang mencukupi untuk di retur
                    $stokCabang = HendhysStockBranch::where('branch_id', $user->branch_id)
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (!$stokCabang || $stokCabang->quantity < $item['quantity']) {
                        throw new \Exception("Stok cabang tidak mencukupi untuk diretur (Produk ID: {$item['product_id']})");
                    }

                    HendhysReturnDetail::create([
                        'return_id' => $ret->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'condition' => $item['condition']
                    ]);

                    // Potong stok cabang
                    $this->stockService->debitHendhys(
                        $item['product_id'],
                        $item['quantity'],
                        $user->branch_id,
                        'return_to_pusat',
                        $ret->id,
                        $user->id
                    );
                }
            });

            return redirect()->route('hendhys.returns.index')
                ->with('success', 'Retur barang berhasil dikirim ke Pusat dan stok cabang telah dikurangi.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses retur: ' . $e->getMessage());
        }
    }

    public function show(HendhysReturnFromBranch $return)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $return->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $return->load(['branch', 'creator', 'receiver', 'details.product', 'details.unit']);

        return Inertia::render('Hendhys/Returns/Show', [
            'return' => new HendhysReturnResource($return),
        ]);
    }

    public function receive(Request $request, HendhysReturnFromBranch $return)
    {
        $user = auth()->user();
        if ($user->branch->type !== 'pusat') {
            abort(403, 'Hanya Pusat yang dapat mengkonfirmasi penerimaan retur.');
        }

        if ($return->status !== 'sent') {
            return back()->with('error', 'Retur ini sudah diproses sebelumnya.');
        }

        try {
            DB::transaction(function () use ($return, $user) {
                $return->update([
                    'status' => 'received',
                    'received_by' => $user->id
                ]);

                foreach ($return->details as $detail) {
                    $this->stockService->creditHendhysReturn(
                        $detail->product_id,
                        $detail->unit_id,
                        $detail->quantity,
                        null, // null untuk Pusat
                        'return_from_branch',
                        $return->id,
                        $user->id
                    );
                }
            });

            return back()->with('success', 'Retur barang dari cabang berhasil diterima dan dimasukkan ke Stok Return Pusat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menerima retur: ' . $e->getMessage());
        }
    }
}
