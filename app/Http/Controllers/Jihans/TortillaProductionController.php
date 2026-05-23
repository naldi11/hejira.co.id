<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansTortillaSession;
use App\Models\JihansTortillaSessionDetail;
use App\Models\Karyawan;
use App\Models\Product;
use App\Models\ProductionRate;
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

        if ($request->filled('search')) {
            $q->where('session_number', 'like', '%' . $request->search . '%');
        }

        $sessions = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('jihans.tortilla.index', compact('sessions'));
    }

    public function create()
    {
        $karyawans = Karyawan::where('entity_scope', 'jihans')->where('is_active', true)->orderBy('name')->get();
        $rates = ProductionRate::where('entity_scope', 'jihans')->first();

        if (!$rates) {
            return redirect()->route('jihans.master.production-rates.edit')
                ->with('error', 'Harap atur tarif produksi terlebih dahulu sebelum menginput data.');
        }

        return view('jihans.tortilla.form', compact('karyawans', 'rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.karyawan_id' => 'required|exists:master_karyawan,id',
            'details.*.tb_qty' => 'required|integer|min:0',
            'details.*.ts_qty' => 'required|integer|min:0',
            'details.*.tk_qty' => 'required|integer|min:0',
            'details.*.tc_qty' => 'required|integer|min:0',
            'details.*.kribab_qty' => 'required|integer|min:0',
        ]);

        $rates = ProductionRate::where('entity_scope', 'jihans')->first();
        
        DB::transaction(function () use ($request, $rates) {
            $session = JihansTortillaSession::create([
                'session_number' => $this->numbers->generateYearly('JHS-TOR', 'jihans_tortilla_sessions', 'session_number'),
                'date' => $request->date,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->details as $detail) {
                $total = ($detail['tb_qty'] * $rates->tb_rate) +
                         ($detail['ts_qty'] * $rates->ts_rate) +
                         ($detail['tk_qty'] * $rates->tk_rate) +
                         ($detail['tc_qty'] * $rates->tc_rate) +
                         ($detail['kribab_qty'] * $rates->kribab_rate);

                $session->details()->create([
                    'karyawan_id' => $detail['karyawan_id'],
                    'tb_qty' => $detail['tb_qty'],
                    'ts_qty' => $detail['ts_qty'],
                    'tk_qty' => $detail['tk_qty'],
                    'tc_qty' => $detail['tc_qty'],
                    'kribab_qty' => $detail['kribab_qty'],
                    'tb_rate' => $rates->tb_rate,
                    'ts_rate' => $rates->ts_rate,
                    'tk_rate' => $rates->tk_rate,
                    'tc_rate' => $rates->tc_rate,
                    'kribab_rate' => $rates->kribab_rate,
                    'total_amount' => $total,
                ]);
            }

            // Agregasi total per varian dan update stok Jihans
            $details = collect($request->details);
            $variantMap = [
                [$rates->tb_product_id,     (int) $details->sum('tb_qty')],
                [$rates->ts_product_id,     (int) $details->sum('ts_qty')],
                [$rates->tk_product_id,     (int) $details->sum('tk_qty')],
                [$rates->tc_product_id,     (int) $details->sum('tc_qty')],
                [$rates->kribab_product_id, (int) $details->sum('kribab_qty')],
            ];

            foreach ($variantMap as [$productId, $totalQty]) {
                if ($productId && $totalQty > 0) {
                    $product = Product::find($productId);
                    if ($product) {
                        $this->stockService->creditJihans(
                            $productId,
                            $product->unit_id,
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

        return redirect()->route('jihans.tortilla.index')->with('success', 'Data produksi tortilla berhasil disimpan dan stok telah diperbarui.');
    }

    public function show(JihansTortillaSession $tortilla)
    {
        $tortilla->load(['details.karyawan', 'creator']);
        return view('jihans.tortilla.show', compact('tortilla'));
    }

    public function recap(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfWeek();
        $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : Carbon::now()->endOfWeek();

        $recap = JihansTortillaSessionDetail::select(
                'karyawan_id',
                DB::raw('COUNT(DISTINCT session_id) as hadir_count'),
                DB::raw('SUM(tb_qty) as total_tb'),
                DB::raw('SUM(ts_qty) as total_ts'),
                DB::raw('SUM(tk_qty) as total_tk'),
                DB::raw('SUM(tc_qty) as total_tc'),
                DB::raw('SUM(kribab_qty) as total_kribab'),
                DB::raw('SUM(total_amount) as total_gaji')
            )
            ->whereHas('session', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo]);
            })
            ->with('karyawan')
            ->groupBy('karyawan_id')
            ->get();

        return view('jihans.tortilla.recap', compact('recap', 'dateFrom', 'dateTo'));
    }

    public function exportRecap(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfWeek();
        $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : Carbon::now()->endOfWeek();

        $recap = JihansTortillaSessionDetail::select(
                'karyawan_id',
                DB::raw('COUNT(DISTINCT session_id) as hadir_count'),
                DB::raw('SUM(tb_qty) as total_tb'),
                DB::raw('SUM(ts_qty) as total_ts'),
                DB::raw('SUM(tk_qty) as total_tk'),
                DB::raw('SUM(tc_qty) as total_tc'),
                DB::raw('SUM(kribab_qty) as total_kribab'),
                DB::raw('SUM(total_amount) as total_gaji')
            )
            ->whereHas('session', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo]);
            })
            ->with('karyawan')
            ->groupBy('karyawan_id')
            ->get();

        $period = $dateFrom->format('d M Y') . ' - ' . $dateTo->format('d M Y');
        $filename = "Rekap_Gaji_Tortilla_{$dateFrom->format('Ymd')}_{$dateTo->format('Ymd')}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Jihans\TortillaRecapExport($recap, $period),
            $filename
        );
    }
}
