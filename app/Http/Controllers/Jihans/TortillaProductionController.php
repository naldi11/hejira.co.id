<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansTortillaSession;
use App\Models\JihansTortillaSessionDetail;
use App\Models\JihansProductionConfig;
use App\Models\Karyawan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TortillaProductionController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $q = JihansTortillaSession::with(['creator'])->withCount('details');

        if ($request->filled('date_from')) $q->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('date', '<=', $request->date_to);
        if ($request->filled('search'))    $q->where('session_number', 'like', '%' . $request->search . '%');

        $sessions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('jihans.tortilla.index', compact('sessions'));
    }

    public function create()
    {
        $karyawans = Karyawan::where('entity_scope', 'jihans')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('jihans.tortilla.form', compact('karyawans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'                  => 'required|date',
            'notes'                 => 'nullable|string',
            'details'               => 'required|array|min:1',
            'details.*.karyawan_id' => 'required|exists:master_karyawan,id',
            'details.*.tb_qty'      => 'required|integer|min:0',
            'details.*.ts_qty'      => 'required|integer|min:0',
            'details.*.tk_qty'      => 'required|integer|min:0',
            'details.*.tc_qty'      => 'required|integer|min:0',
            'details.*.kribab_qty'  => 'required|integer|min:0',
        ]);

        $totalQtyAll = collect($request->details)->sum(function ($d) {
            return ($d['tb_qty'] ?? 0) + ($d['ts_qty'] ?? 0) + ($d['tk_qty'] ?? 0)
                 + ($d['tc_qty'] ?? 0) + ($d['kribab_qty'] ?? 0);
        });

        if ($totalQtyAll <= 0) {
            return back()->withInput()->withErrors(['details' => 'Minimal ada 1 karyawan dengan jumlah produksi > 0.']);
        }

        DB::transaction(function () use ($request) {
            $config = JihansProductionConfig::current();

            // Auto-check and create products if they don't exist
            $variants = [
                'tb'     => ['field' => 'tb_product_id',     'name' => 'Tortilla Besar'],
                'ts'     => ['field' => 'ts_product_id',     'name' => 'Tortilla Sedang'],
                'tk'     => ['field' => 'tk_product_id',     'name' => 'Tortilla Kecil'],
                'tc'     => ['field' => 'tc_product_id',     'name' => 'Tortilla Catering'],
                'kribab' => ['field' => 'kribab_product_id', 'name' => 'Kribab'],
            ];

            $configUpdated = false;

            foreach ($variants as $key => $v) {
                $field = $v['field'];
                $defaultName = $v['name'];

                // Check if product is set and exists in DB
                $productExists = false;
                if ($config->$field) {
                    $productExists = Product::where('id', $config->$field)->exists();
                }

                if (!$productExists) {
                    // Search if there is already a product with this name in jihans / all scope
                    $product = Product::where('name', $defaultName)
                        ->where(function ($q) {
                            $q->where('entity_scope', 'jihans')
                              ->orWhere('entity_scope', 'all');
                        })
                        ->first();

                    if (!$product) {
                        // Create ProductCategory if "Tortilla" doesn't exist
                        $category = ProductCategory::where('name', 'Tortilla')->first();
                        if (!$category) {
                            $category = ProductCategory::create([
                                'name' => 'Tortilla',
                                'entity_scope' => 'all',
                                'visible_gudang' => true,
                                'visible_jihans' => true,
                                'visible_hendhys' => false,
                            ]);
                        }

                        // Get first available unit, or "Pak"
                        $unit = Unit::where('abbreviation', 'PAK')
                            ->orWhere('abbreviation', 'Pak')
                            ->orWhere('name', 'Pak')
                            ->first();
                        if (!$unit) {
                            $unit = Unit::first();
                        }
                        $unitId = $unit ? $unit->id : 1;

                        // Generate code
                        $code = $this->numbers->generate('PRD', 'master_products', 'code');

                        $product = Product::create([
                            'code'            => $code,
                            'name'            => $defaultName,
                            'category_id'     => $category->id,
                            'unit_id'         => $unitId,
                            'hpp'             => 0,
                            'selling_price'   => 0,
                            'stock_min'       => 0,
                            'ppn_type'        => 'none',
                            'ppn_rate'        => 0,
                            'product_type'    => 'INV',
                            'source_type'     => 'produced',
                            'entity_scope'    => 'jihans',
                            'visible_jihans'  => true,
                            'visible_gudang'  => false,
                            'visible_hendhys' => false,
                            'status'          => 'active',
                            'created_by'      => auth()->id(),
                        ]);
                    }

                    $config->$field = $product->id;
                    $configUpdated = true;
                }
            }

            if ($configUpdated) {
                $config->updated_by = auth()->id();
                $config->save();
            }

            $session = JihansTortillaSession::create([
                'session_number'    => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
                'date'              => $request->date,
                'notes'             => $request->notes,
                'created_by'        => auth()->id(),
                'tb_product_id'     => $config->tb_product_id,
                'ts_product_id'     => $config->ts_product_id,
                'tk_product_id'     => $config->tk_product_id,
                'tc_product_id'     => $config->tc_product_id,
                'kribab_product_id' => $config->kribab_product_id,
            ]);

            $productQtyMap = [];

            foreach ($request->details as $detail) {
                $session->details()->create([
                    'karyawan_id' => $detail['karyawan_id'],
                    'tb_qty'      => $detail['tb_qty'],
                    'ts_qty'      => $detail['ts_qty'],
                    'tk_qty'      => $detail['tk_qty'],
                    'tc_qty'      => $detail['tc_qty'],
                    'kribab_qty'  => $detail['kribab_qty'],
                ]);

                // Akumulasi qty per produk
                $variantMap = [
                    $session->tb_product_id     => (int) $detail['tb_qty'],
                    $session->ts_product_id     => (int) $detail['ts_qty'],
                    $session->tk_product_id     => (int) $detail['tk_qty'],
                    $session->tc_product_id     => (int) $detail['tc_qty'],
                    $session->kribab_product_id => (int) $detail['kribab_qty'],
                ];
                foreach ($variantMap as $pid => $qty) {
                    if ($pid && $qty > 0) {
                        $productQtyMap[$pid] = ($productQtyMap[$pid] ?? 0) + $qty;
                    }
                }
            }

            // Update stok Jihans per produk
            if (!empty($productQtyMap)) {
                $products = Product::whereIn('id', array_keys($productQtyMap))->get()->keyBy('id');
                foreach ($productQtyMap as $productId => $totalQty) {
                    if ($products->has($productId)) {
                        $this->stockService->creditJihans(
                            $productId,
                            $products[$productId]->unit_id,
                            $totalQty,
                            'production',
                            $session->id,
                            auth()->id()
                        );
                    }
                }
            }

            $this->logger->log('create', 'jihans.tortilla', "Input produksi tortilla: {$session->session_number}", $session);
        });

        return redirect()->route('jihans.tortilla.index')
            ->with('success', 'Data produksi tortilla berhasil disimpan dan stok telah diperbarui.');
    }

    public function show(JihansTortillaSession $tortilla)
    {
        $tortilla->load(['details.karyawan', 'creator']);
        return view('jihans.tortilla.show', compact('tortilla'));
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
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::createFromDate(2000, 1, 1)->startOfDay();
            $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : Carbon::now()->endOfDay();
        } else {
            $dateFrom = null;
            $dateTo   = null;
        }

        $recap = JihansTortillaSessionDetail::select(
                'karyawan_id',
                DB::raw('COUNT(DISTINCT session_id) as hadir_count'),
                DB::raw('SUM(tb_qty) as total_tb'),
                DB::raw('SUM(ts_qty) as total_ts'),
                DB::raw('SUM(tk_qty) as total_tk'),
                DB::raw('SUM(tc_qty) as total_tc'),
                DB::raw('SUM(kribab_qty) as total_kribab')
            )
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereHas('session', fn($s) => $s->whereBetween('date', [$dateFrom, $dateTo]));
            })
            ->with('karyawan')
            ->groupBy('karyawan_id')
            ->get();

        return view('jihans.tortilla.recap', compact('recap', 'dateFrom', 'dateTo', 'periode', 'noFilter'));
    }

    public function exportRecap(Request $request)
    {
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
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
            $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : Carbon::now()->endOfDay();
        } else {
            $dateFrom = null;
            $dateTo   = null;
        }

        $recap = JihansTortillaSessionDetail::select(
                'karyawan_id',
                DB::raw('COUNT(DISTINCT session_id) as hadir_count'),
                DB::raw('SUM(tb_qty) as total_tb'),
                DB::raw('SUM(ts_qty) as total_ts'),
                DB::raw('SUM(tk_qty) as total_tk'),
                DB::raw('SUM(tc_qty) as total_tc'),
                DB::raw('SUM(kribab_qty) as total_kribab')
            )
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereHas('session', fn($s) => $s->whereBetween('date', [$dateFrom, $dateTo]));
            })
            ->with('karyawan')
            ->groupBy('karyawan_id')
            ->get();

        $period = ($dateFrom && $dateTo)
            ? $dateFrom->format('d M Y') . ' - ' . $dateTo->format('d M Y')
            : 'Semua Data';
        $filename = "Rekap_Gaji_Tortilla_{$dateFrom?->format('Ymd')}_{$dateTo?->format('Ymd')}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Jihans\TortillaRecapExport($recap, $period),
            $filename
        );
    }
}
