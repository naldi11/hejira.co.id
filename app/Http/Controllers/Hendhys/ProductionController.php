<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysProductionResource;
use App\Models\HendhysProduction;
use App\Models\HendhysProductionDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductionController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    private function checkPusat(): void
    {
        $user = auth()->user();
        if ($user->branch && $user->branch->type !== 'pusat') {
            abort(403, 'Hanya Cabang Pusat yang dapat mengakses modul Produksi.');
        }
    }

    public function index(Request $request)
    {
        $this->checkPusat();

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

        return Inertia::render('Hendhys/Productions/Index', [
            'productions' => HendhysProductionResource::collection($productions),
            'filters'     => $request->only('search', 'date_from', 'date_to'),
        ]);
    }

    private function getProductionProducts()
    {
        return Product::where('status', 'active')
            ->visibleInHendhys()
            ->with('unit')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id]);
    }

    private function getUnits()
    {
        return Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);
    }

    public function create(Request $request)
    {
        $this->checkPusat();

        $predictionId = $request->query('prediction_id');
        $targetDate = $request->query('date', date('Y-m-d'));
        if ($targetDate && strlen($targetDate) > 10) {
            $targetDate = substr($targetDate, 0, 10);
        }

        $existingPrediksi = null;
        if ($predictionId) {
            $existingPrediksi = HendhysProduction::with('details')
                ->where('id', $predictionId)
                ->where('type', 'prediksi')
                ->first();
        }

        return Inertia::render('Hendhys/Productions/Form', [
            'products'   => $this->getProductionProducts(),
            'units'      => $this->getUnits(),
            'type'       => 'aktual',
            'formAction' => route('hendhys.productions.store'),
            'warning'    => null,
            'targetDate' => $existingPrediksi ? $existingPrediksi->date : $targetDate,
            'prediction_id' => $existingPrediksi ? $existingPrediksi->id : null,
            'production' => $existingPrediksi ? new HendhysProductionResource($existingPrediksi) : null,
        ]);
    }

    public function createPrediksi()
    {
        $this->checkPusat();

        return Inertia::render('Hendhys/Productions/Form', [
            'products'   => $this->getProductionProducts(),
            'units'      => $this->getUnits(),
            'type'       => 'prediksi',
            'formAction' => route('hendhys.productions.prediksi.store'),
            'warning'    => null,
        ]);
    }

    public function storePrediksi(Request $request)
    {
        $this->checkPusat();

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|numeric|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $production = HendhysProduction::create([
                    'production_number' => $this->numbers->generateYearly('HND-PRD', 'hendhys_productions', 'production_number'),
                    'type' => 'prediksi',
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
                }
            });

            return redirect()->route('hendhys.productions.index')
                ->with('success', 'Prediksi produksi Hendhys berhasil disimpan.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan prediksi produksi: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $this->checkPusat();

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'prediction_id' => 'nullable|integer|exists:hendhys_productions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|numeric|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $existingPrediksi = null;
                
                if ($request->prediction_id) {
                    $existingPrediksi = HendhysProduction::with('details')->where('type', 'prediksi')
                        ->where('id', $request->prediction_id)
                        ->whereNull('overridden_at')
                        ->first();
                }

                if ($existingPrediksi) {
                    $existingPrediksi->details()->delete();
                    $existingPrediksi->update([
                        'type'  => 'aktual',
                        'notes' => $request->notes,
                    ]);
                    $production = $existingPrediksi;
                } else {
                    $production = HendhysProduction::create([
                        'production_number' => $this->numbers->generateYearly('HND-PRD', 'hendhys_productions', 'production_number'),
                        'type' => 'aktual',
                        'date' => $request->date,
                        'notes' => $request->notes,
                        'created_by' => auth()->id()
                    ]);
                }

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

    public function editPrediksi(HendhysProduction $production)
    {
        $this->checkPusat();

        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('hendhys.productions.index')->withErrors('Sesi ini bukan prediksi atau sudah diaktualisasi.');
        }

        $production->load('details');

        return Inertia::render('Hendhys/Productions/Form', [
            'products'   => $this->getProductionProducts(),
            'units'      => $this->getUnits(),
            'type'       => 'prediksi',
            'formAction' => route('hendhys.productions.prediksi.update', $production->id),
            'warning'    => null,
            'isEdit'     => true,
            'production' => new HendhysProductionResource($production),
        ]);
    }

    public function updatePrediksi(Request $request, HendhysProduction $production)
    {
        $this->checkPusat();

        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('hendhys.productions.index')->withErrors('Sesi ini tidak bisa diedit.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|numeric|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        try {
            DB::transaction(function () use ($request, $production) {
                $production->update(['date' => $request->date, 'notes' => $request->notes]);
                $production->details()->delete();

                foreach ($request->items as $item) {
                    HendhysProductionDetail::create([
                        'production_id' => $production->id,
                        'product_id' => $item['product_id'],
                        'quantity_produced' => $item['quantity_produced'],
                        'unit_id' => $item['unit_id']
                    ]);
                }
            });

            return redirect()->route('hendhys.productions.index')
                ->with('success', 'Prediksi berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui prediksi produksi: ' . $e->getMessage());
        }
    }

    public function destroyPrediksi(HendhysProduction $production)
    {
        $this->checkPusat();

        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('hendhys.productions.index')->withErrors('Sesi ini tidak bisa dihapus.');
        }

        try {
            DB::transaction(function () use ($production) {
                $production->details()->delete();
                $production->delete();
            });

            return redirect()->route('hendhys.productions.index')->with('success', 'Data prediksi berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('hendhys.productions.index')->with('error', 'Gagal menghapus prediksi: ' . $e->getMessage());
        }
    }

    public function show(HendhysProduction $production)
    {
        $this->checkPusat();

        $production->load(['creator', 'details.product', 'details.unit']);

        return Inertia::render('Hendhys/Productions/Show', [
            'production' => new HendhysProductionResource($production),
        ]);
    }
}
