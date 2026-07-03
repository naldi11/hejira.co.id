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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Cabang Pusat yang dapat mengakses modul Produksi.');
        }

        $targetDate = $request->query('date', date('Y-m-d'));
        if ($targetDate && strlen($targetDate) > 10) {
            $targetDate = substr($targetDate, 0, 10);
        }

        $existingAktual = HendhysProduction::whereDate('date', $targetDate)
            ->where('type', 'aktual')
            ->first();

        $warning = $existingAktual 
            ? 'Aktual produksi untuk tanggal ' . $targetDate . ' sudah diinput. Anda tidak bisa menginput ulang atau mengeditnya.'
            : null;

        $existingPrediksi = HendhysProduction::with('details')
            ->whereDate('date', $targetDate)
            ->where('type', 'prediksi')
            ->first();

        return Inertia::render('Hendhys/Productions/Form', [
            'products'   => $this->getProductionProducts(),
            'units'      => $this->getUnits(),
            'type'       => 'aktual',
            'formAction' => route('hendhys.productions.store'),
            'warning'    => $warning,
            'targetDate' => $targetDate,
            'production' => $existingPrediksi ? new HendhysProductionResource($existingPrediksi) : null,
        ]);
    }

    public function createPrediksi()
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Cabang Pusat yang dapat mengakses modul Produksi.');
        }

        $existingToday = HendhysProduction::whereDate('date', today())
            ->whereIn('type', ['prediksi', 'aktual'])
            ->first();

        $warning = null;
        if ($existingToday) {
            $warning = $existingToday->type === 'aktual'
                ? 'Aktual produksi hari ini sudah diinput. Prediksi tidak diperlukan lagi.'
                : 'Prediksi hari ini sudah ada. Menyimpan ulang akan gagal.';
        }

        return Inertia::render('Hendhys/Productions/Form', [
            'products'   => $this->getProductionProducts(),
            'units'      => $this->getUnits(),
            'type'       => 'prediksi',
            'formAction' => route('hendhys.productions.prediksi.store'),
            'warning'    => $warning,
        ]);
    }

    public function storePrediksi(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|numeric|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        $existing = HendhysProduction::whereDate('date', $request->date)
            ->whereIn('type', ['prediksi', 'aktual'])
            ->first();

        if ($existing) {
            $msg = $existing->type === 'aktual'
                ? 'Aktual produksi tanggal ini sudah ada. Prediksi tidak bisa dibuat.'
                : 'Prediksi untuk tanggal ini sudah ada.';
            return back()->withInput()->withErrors(['date' => $msg]);
        }

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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity_produced' => 'required|numeric|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        $existingAktual = HendhysProduction::whereDate('date', $request->date)
            ->where('type', 'aktual')
            ->first();

        if ($existingAktual) {
            return back()->withInput()->withErrors(['date' => 'Aktual produksi untuk tanggal ini sudah diinput dan tidak bisa diubah.']);
        }

        try {
            DB::transaction(function () use ($request) {
                // Override prediksi hari yang sama jika ada
                $existingPrediksi = HendhysProduction::with('details')->where('type', 'prediksi')
                    ->whereDate('date', $request->date)
                    ->whereNull('overridden_at')
                    ->first();

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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

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
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $production->load(['creator', 'details.product', 'details.unit']);

        return Inertia::render('Hendhys/Productions/Show', [
            'production' => new HendhysProductionResource($production),
        ]);
    }
}
