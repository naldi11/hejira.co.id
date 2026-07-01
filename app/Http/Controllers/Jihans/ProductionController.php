<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansProductionSession;
use App\Models\JihansProductionSessionDetail;
use App\Models\Karyawan;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class ProductionController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $q = JihansProductionSession::with(['creator'])->withCount('details');

        if ($request->filled('date_from')) $q->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('date', '<=', $request->date_to);
        if ($request->filled('search'))    $q->where('session_number', 'like', '%' . $request->search . '%');

        $sessions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Jihans/Production/Index', [
            'sessions' => $sessions, 
            'filters' => $request->only('search', 'date_from', 'date_to')
        ]);
    }

    private function getProductionProducts()
    {
        return Product::where('source_type', 'produced')
            ->where(function ($q) {
                $q->where('entity_scope', 'jihans')
                  ->orWhere('entity_scope', 'all');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function create(Request $request)
    {
        $targetDate = $request->query('date', date('Y-m-d'));
        if ($targetDate && strlen($targetDate) > 10) {
            $targetDate = substr($targetDate, 0, 10);
        }

        $existingAktual = JihansProductionSession::whereDate('date', $targetDate)
            ->where('type', 'aktual')
            ->first();

        $warning = $existingAktual 
            ? 'Aktual produksi untuk tanggal ' . $targetDate . ' sudah diinput. Anda tidak bisa menginput ulang atau mengeditnya.'
            : null;

        $karyawans = Karyawan::where('entity_scope', 'jihans')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = $this->getProductionProducts();

        $existingPrediksi = JihansProductionSession::with('details')
            ->whereDate('date', $targetDate)
            ->where('type', 'prediksi')
            ->first();

        return Inertia::render('Jihans/Production/Form', [
            'karyawans'  => $karyawans,
            'products'   => $products,
            'type'       => 'aktual',
            'formAction' => route('jihans.production.store'),
            'warning'    => $warning,
            'targetDate' => $targetDate,
            'production' => $existingPrediksi,
        ]);
    }

    public function createPrediksi()
    {
        $existingToday = JihansProductionSession::whereDate('date', today())
            ->whereIn('type', ['prediksi', 'aktual'])
            ->first();

        $warning = null;
        if ($existingToday) {
            $warning = $existingToday->type === 'aktual'
                ? 'Aktual produksi hari ini sudah diinput. Prediksi tidak diperlukan lagi.'
                : 'Prediksi hari ini sudah ada. Menyimpan ulang akan gagal.';
        }

        $karyawans = Karyawan::where('entity_scope', 'jihans')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = $this->getProductionProducts();

        return Inertia::render('Jihans/Production/Form', [
            'karyawans'  => $karyawans,
            'products'   => $products,
            'type'       => 'prediksi',
            'formAction' => route('jihans.production.prediksi.store'),
            'warning'    => $warning,
        ]);
    }

    public function storePrediksi(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'notes'      => 'nullable|string',
            'details'    => 'required|array',
            'details.*.karyawan_id' => 'nullable|integer',
            'details.*.product_id'  => 'required|integer|exists:master_products,id',
            'details.*.quantity'    => 'required|numeric|min:0',
        ]);

        $existing = JihansProductionSession::whereDate('date', $request->date)
            ->whereIn('type', ['prediksi', 'aktual'])
            ->first();

        if ($existing) {
            $msg = $existing->type === 'aktual'
                ? 'Aktual produksi tanggal ini sudah ada. Prediksi tidak bisa dibuat.'
                : 'Prediksi untuk tanggal ini sudah ada.';
            return back()->withInput()->withErrors(['date' => $msg]);
        }

        $totalQty = collect($request->details)->sum('quantity');
        if ($totalQty <= 0) {
            return back()->withInput()->withErrors(['details' => 'Total produksi harus lebih dari 0.']);
        }

        DB::transaction(function () use ($request) {
            $session = JihansProductionSession::create([
                'session_number'    => $this->numbers->generateYearly('JHS-PRD', 'jihans_production_sessions', 'session_number'),
                'type'              => 'prediksi',
                'date'              => $request->date,
                'notes'             => $request->notes,
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->details as $detail) {
                if ($detail['quantity'] > 0) {
                    $session->details()->create([
                        'karyawan_id' => $detail['karyawan_id'] ?? null,
                        'product_id'  => $detail['product_id'],
                        'quantity'    => $detail['quantity'],
                    ]);
                }
            }

            $this->logger->log('create', 'jihans.production', "Input prediksi produksi: {$session->session_number}", $session);
        });

        return redirect()->route('jihans.production.index')->with('success', 'Prediksi produksi berhasil disimpan.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'notes'      => 'nullable|string',
            'details'    => 'required|array',
            'details.*.karyawan_id' => 'nullable|integer',
            'details.*.product_id'  => 'required|integer|exists:master_products,id',
            'details.*.quantity'    => 'required|numeric|min:0',
        ]);

        $totalQtyAll = collect($request->details)->sum('quantity');

        if ($totalQtyAll <= 0) {
            return back()->withInput()->withErrors(['details' => 'Total produksi harus lebih dari 0.']);
        }

        $existingAktual = JihansProductionSession::whereDate('date', $request->date)
            ->where('type', 'aktual')
            ->first();

        if ($existingAktual) {
            return back()->withInput()->withErrors(['date' => 'Aktual produksi untuk tanggal ini sudah diinput dan tidak bisa diubah.']);
        }

        DB::transaction(function () use ($request) {
            // Override prediksi hari yang sama jika ada
            $existingPrediksi = JihansProductionSession::with('details')->where('type', 'prediksi')
                ->whereDate('date', $request->date)
                ->whereNull('overridden_at')
                ->first();

            $oldProductQtyMap = [];
            if ($existingPrediksi) {
                foreach ($existingPrediksi->details as $oldDetail) {
                    $pid = $oldDetail->product_id;
                    $qty = $oldDetail->quantity;
                    if ($qty > 0) {
                        $oldProductQtyMap[$pid] = ($oldProductQtyMap[$pid] ?? 0) + $qty;
                    }
                }
                
                // Hapus detail lama untuk diganti
                $existingPrediksi->details()->delete();

                // Perbarui sesi prediksi menjadi aktual
                $existingPrediksi->update([
                    'type'              => 'aktual',
                    'notes'             => $request->notes,
                ]);
                $session = $existingPrediksi;
            } else {
                $session = JihansProductionSession::create([
                    'session_number'    => $this->numbers->generateYearly('JHS-PRD', 'jihans_production_sessions', 'session_number'),
                    'type'              => 'aktual',
                    'date'              => $request->date,
                    'notes'             => $request->notes,
                    'created_by'        => auth()->id(),
                ]);
            }

            $productQtyMap = [];

            foreach ($request->details as $detail) {
                if ($detail['quantity'] > 0) {
                    $session->details()->create([
                        'karyawan_id' => $detail['karyawan_id'] ?? null,
                        'product_id'  => $detail['product_id'],
                        'quantity'    => $detail['quantity'],
                    ]);

                    $pid = $detail['product_id'];
                    $qty = $detail['quantity'];
                    $productQtyMap[$pid] = ($productQtyMap[$pid] ?? 0) + $qty;
                }
            }

            // Tambah stok Jihans Gudang sejumlah Aktual
            if (!empty($productQtyMap)) {
                $products = Product::whereIn('id', array_keys($productQtyMap))->get()->keyBy('id');
                foreach ($productQtyMap as $productId => $newQty) {
                    if (!$products->has($productId) || $newQty <= 0) continue;
                    
                    $this->stockService->creditJihansGudang(
                        $productId,
                        $products[$productId]->unit_id,
                        $newQty,
                        'production',
                        $session->id,
                        auth()->id()
                    );
                }
            }

            $this->logger->log('create', 'jihans.production', "Input produksi aktual: {$session->session_number}", $session);
        });

        return redirect()->route('jihans.production.index')
            ->with('success', 'Data produksi berhasil disimpan dan stok telah diperbarui.');
    }

    public function editPrediksi(JihansProductionSession $production)
    {
        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('jihans.production.index')->withErrors('Sesi ini bukan prediksi atau sudah diaktualisasi.');
        }

        $production->load('details');
        $karyawans = Karyawan::where('entity_scope', 'jihans')->where('is_active', true)->orderBy('name')->get();
        $products = $this->getProductionProducts();

        return Inertia::render('Jihans/Production/Form', [
            'karyawans'  => $karyawans,
            'products'   => $products,
            'type'       => 'prediksi',
            'formAction' => route('jihans.production.prediksi.update', $production->id),
            'warning'    => null,
            'isEdit'     => true,
            'production' => $production
        ]);
    }

    public function updatePrediksi(Request $request, JihansProductionSession $production)
    {
        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('jihans.production.index')->withErrors('Sesi ini tidak bisa diedit.');
        }

        $request->validate([
            'date'       => 'required|date',
            'notes'      => 'nullable|string',
            'details'    => 'required|array',
            'details.*.karyawan_id' => 'nullable|integer',
            'details.*.product_id'  => 'required|integer|exists:master_products,id',
            'details.*.quantity'    => 'required|numeric|min:0',
        ]);

        $totalQtyAll = collect($request->details)->sum('quantity');
        if ($totalQtyAll <= 0) {
            return back()->withInput()->withErrors(['details' => 'Total produksi harus lebih dari 0.']);
        }

        DB::transaction(function () use ($request, $production) {
            $production->load('details');
            $oldProductQtyMap = [];
            foreach ($production->details as $oldDetail) {
                $pid = $oldDetail->product_id;
                $qty = $oldDetail->quantity;
                if ($qty > 0) {
                    $oldProductQtyMap[$pid] = ($oldProductQtyMap[$pid] ?? 0) + $qty;
                }
            }

            $production->update(['date' => $request->date, 'notes' => $request->notes]);
            $production->details()->delete();

            $productQtyMap = [];
            foreach ($request->details as $detail) {
                if ($detail['quantity'] > 0) {
                    $production->details()->create([
                        'karyawan_id' => $detail['karyawan_id'] ?? null,
                        'product_id'  => $detail['product_id'],
                        'quantity'    => $detail['quantity'],
                    ]);

                    $pid = $detail['product_id'];
                    $qty = $detail['quantity'];
                    $productQtyMap[$pid] = ($productQtyMap[$pid] ?? 0) + $qty;
                }
            }

            // Prediksi tidak memengaruhi stok

            $this->logger->log('update', 'jihans.production', "Update prediksi produksi: {$production->session_number}", $production);
        });

        return redirect()->route('jihans.production.index')->with('success', 'Prediksi berhasil diperbarui.');
    }

    public function destroyPrediksi(JihansProductionSession $production)
    {
        if (!$production->isPrediksi() || $production->type === 'aktual') {
            return redirect()->route('jihans.production.index')->withErrors('Sesi ini tidak bisa dihapus.');
        }

        DB::transaction(function () use ($production) {
            $production->load('details');
            $oldProductQtyMap = [];
            foreach ($production->details as $oldDetail) {
                $pid = $oldDetail->product_id;
                $qty = $oldDetail->quantity;
                if ($qty > 0) {
                    $oldProductQtyMap[$pid] = ($oldProductQtyMap[$pid] ?? 0) + $qty;
                }
            }

            // Prediksi tidak memengaruhi stok

            $this->logger->log('delete', 'jihans.production', "Hapus prediksi produksi: {$production->session_number}");
            $production->details()->delete();
            $production->delete();
        });

        return redirect()->route('jihans.production.index')->with('success', 'Data prediksi berhasil dihapus.');
    }

    public function show(JihansProductionSession $production)
    {
        $production->load(['details.karyawan', 'details.product', 'creator']);
        return Inertia::render('Jihans/Production/Show', ['production' => $production]);
    }

    public function recap(Request $request)
    {
        $noFilter = !$request->filled('date_from') && !$request->filled('date_to') && !$request->filled('periode');

        $periode = $request->periode;
        if ($periode === 'hari') {
            $dateFrom = Carbon::today()->startOfDay();
            $dateTo   = Carbon::today()->endOfDay();
        } elseif ($periode === 'minggu') {
            $dateFrom = Carbon::now()->startOfWeek();
            $dateTo   = Carbon::now()->endOfWeek();
        } elseif ($periode === 'bulan') {
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo   = Carbon::now()->endOfMonth();
        } else {
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
            $dateTo   = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        }

        $query = JihansProductionSessionDetail::query()
            ->join('jihans_production_sessions', 'jihans_production_sessions.id', '=', 'jihans_production_session_details.session_id')
            ->join('master_products', 'master_products.id', '=', 'jihans_production_session_details.product_id')
            ->where('jihans_production_sessions.type', 'aktual');

        if ($dateFrom) $query->where('jihans_production_sessions.date', '>=', $dateFrom);
        if ($dateTo)   $query->where('jihans_production_sessions.date', '<=', $dateTo);

        $productTotals = $query->selectRaw('
                master_products.name as product_name,
                SUM(jihans_production_session_details.quantity) as total_qty
            ')
            ->groupBy('master_products.id', 'master_products.name')
            ->orderBy('master_products.name')
            ->get();

        return Inertia::render('Jihans/Production/Recap', [
            'productTotals' => $productTotals,
            'filters' => $request->only('date_from', 'date_to', 'periode'),
            'noFilter' => $noFilter,
        ]);
    }
}
