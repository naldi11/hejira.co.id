<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\JihansGudangStock;
use App\Models\JihansRetailStock;
use App\Models\HendhysStockPusat;
use App\Models\HendhysStockBranch;
use App\Models\JihansGudangStockMovement;
use App\Models\JihansRetailStockMovement;
use App\Models\HendhysStockMovement;
use App\Models\PurchaseOrder;
use App\Models\PoDetail;
use App\Models\HendhysTransaction;
use App\Models\JihansTransaction;
use App\Models\CashierShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Pilihan periode untuk kartu OMSET di dashboard Owner.
     *
     * Didefinisikan sekali di sini lalu dikirim ke frontend, supaya daftar di
     * layar tidak bisa melenceng dari yang benar-benar dipahami backend.
     *
     * Catatan penafsiran: `last_6_months` dan `last_year` adalah jendela BERJALAN
     * (6 dan 12 bulan terakhir dihitung mundur dari hari ini), bukan periode
     * kalender. `this_month`/`last_month` sebaliknya mengikuti batas bulan.
     */
    private const PERIODS = [
        'today'         => 'Hari Ini',
        'this_week'     => 'Minggu Ini',
        'this_month'    => 'Bulan Ini',
        'last_month'    => 'Bulan Lalu',
        'last_6_months' => '6 Bulan Terakhir',
        'last_year'     => '1 Tahun Terakhir',
        'all'           => 'Keseluruhan',
    ];

    private const DEFAULT_PERIOD = 'this_month';

    /**
     * Rentang tanggal untuk sebuah periode. `null` berarti tanpa batas (keseluruhan).
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    private function periodRange(string $period): ?array
    {
        return match ($period) {
            'today'         => [today(), today()],
            'this_week'     => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month'    => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month'    => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_6_months' => [now()->subMonthsNoOverflow(6)->startOfDay(), today()],
            'last_year'     => [now()->subYearNoOverflow()->startOfDay(), today()],
            default         => null, // 'all'
        };
    }

    /** Ambil periode dari query string, tolak nilai yang tidak dikenal. */
    private function resolvePeriod(\Illuminate\Http\Request $request): string
    {
        $period = (string) $request->query('period', self::DEFAULT_PERIOD);

        return array_key_exists($period, self::PERIODS) ? $period : self::DEFAULT_PERIOD;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $period = $this->resolvePeriod($request);
        $range  = $this->periodRange($period);

        // Hanya OMSET yang mengikuti periode. Stok, mutasi, dan PO tetap
        // menampilkan posisi keseluruhan — stok adalah saldo berjalan, bukan
        // akumulasi periode, sehingga memfilternya per bulan justru menyesatkan.
        $omsetPeriod = function ($query) use ($range) {
            if ($range) {
                $query->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()]);
            }

            return $query;
        };

        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(today()->subDays($i)->format('Y-m-d'));
        }

        $jihansSales = JihansTransaction::where('status', 'paid')
            ->whereDate('date', '>=', today()->subDays(6))
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $hendhysSales = HendhysTransaction::where('status', 'paid')
            ->whereDate('date', '>=', today()->subDays(6))
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $trends = $days->map(fn($d) => [
            'date' => Carbon::parse($d)->format('d M'),
            'jihans' => (float) ($jihansSales[$d] ?? 0),
            'hendhys' => (float) ($hendhysSales[$d] ?? 0),
            'total' => (float) (($jihansSales[$d] ?? 0) + ($hendhysSales[$d] ?? 0)),
        ]);

        // Stock Calculation
        $jihansGudangStock = (int) JihansGudangStock::sum('quantity');
        $jihansRetailStock = (int) JihansRetailStock::sum('quantity');
        $hendhysPusatStock = (int) HendhysStockPusat::sum('quantity');
        $hendhysCabangStock = (int) HendhysStockBranch::sum('quantity');
        $totalStock = $jihansGudangStock + $jihansRetailStock + $hendhysPusatStock + $hendhysCabangStock;

        // Dynamic Hendhys branches list with their current stock.
        // Pakai kolom `entity`, bukan tebak-tebakan nama/kode: cabang Hendhys yang
        // namanya tidak mengandung "Hendhy" dan kodenya bukan HB*/HND* dulu hilang
        // sepenuhnya dari dashboard Owner.
        $cabangBranches = Branch::where('is_active', true)
            ->where('type', 'cabang')
            ->where('entity', 'hendhys')
            ->orderBy('name')
            ->get();

        // Agregat dihitung sekali secara batch (dulu 2 query per cabang di dalam loop).
        $branchIds = $cabangBranches->pluck('id');
        $stockPerBranch = HendhysStockBranch::whereIn('branch_id', $branchIds)
            ->groupBy('branch_id')
            ->selectRaw('branch_id, SUM(quantity) as total')
            ->pluck('total', 'branch_id');
        // Omset per cabang mengikuti periode; stok per cabang di atas tidak.
        $revenuePerBranch = $omsetPeriod(
            HendhysTransaction::where('status', 'paid')->whereIn('branch_id', $branchIds)
        )
            ->groupBy('branch_id')
            ->selectRaw('branch_id, SUM(grand_total) as total')
            ->pluck('total', 'branch_id');

        $cabangList = $cabangBranches->map(fn ($cb) => [
            'id'       => $cb->id,
            'name'     => $cb->name,
            'quantity' => (int) ($stockPerBranch[$cb->id] ?? 0),
            'revenue'  => (float) ($revenuePerBranch[$cb->id] ?? 0),
        ])->values()->all();

        // Movements Calculation
        $jihansGudangMovementsCount = JihansGudangStockMovement::count();
        $jihansGudangMovementsQty = (int) JihansGudangStockMovement::sum('quantity');
        
        $jihansRetailMovementsCount = JihansRetailStockMovement::count();
        $jihansRetailMovementsQty = (int) JihansRetailStockMovement::sum('quantity');

        $hendhysMovementsCount = HendhysStockMovement::count();
        $hendhysMovementsQty = (int) HendhysStockMovement::sum('quantity');

        $totalMovementsCount = $jihansGudangMovementsCount + $jihansRetailMovementsCount + $hendhysMovementsCount;
        $totalMovementsQty = $jihansGudangMovementsQty + $jihansRetailMovementsQty + $hendhysMovementsQty;

        // PO Calculation
        $totalPoCount = PurchaseOrder::count();
        $totalPoQty = (float) PoDetail::sum('quantity_ordered');

        // Detailed Tables Data (values() added to reset collection keys for clean JSON arrays)
        // 1. Jihans Gudang Stocks
        $gudangStocksList = JihansGudangStock::with('product')->get()
            ->map(fn($s) => [
                'code' => $s->product?->code ?? '-',
                'name' => $s->product?->name ?? '-',
                'quantity' => (int) $s->quantity,
                'unit' => $s->product?->unit?->abbreviation ?? 'PCS'
            ])->values();

        // 2. Jihans Retail Stocks
        $jihansStocksList = DB::table('master_products as p')
            ->leftJoin('jihans_retail_stock as s', 'p.id', '=', 's.product_id')
            ->leftJoin('master_units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.status', 'active')
            ->where(fn($w) => $w->whereRaw('p.visible_jihans = 1')->orWhereNotNull('s.quantity'))
            ->select('p.name', 'p.code', DB::raw('COALESCE(s.quantity, 0) as quantity'), 'u.abbreviation as unit')
            ->orderBy('p.name')
            ->get()
            ->map(fn($s) => [
                'code' => $s->code,
                'name' => $s->name,
                'quantity' => (int) $s->quantity,
                'unit' => $s->unit ?? 'PCS'
            ])->values();

        // 3. Hendhys Consolidated Stocks (Pusat & Cabang combined)
        $hendhysProducts = DB::table('master_products')
            ->where('status', 'active')
            ->where('visible_hendhys', true)
            ->orderBy('name')
            ->get();
        $pusatStocks = DB::table('hendhys_stock_pusat')->get()->keyBy('product_id');
        $cabangStocks = DB::table('hendhys_stock_branch as sb')
            ->join('master_branches as b', 'b.id', '=', 'sb.branch_id')
            ->select('sb.product_id', 'b.id as branch_id', 'b.name as branch_name', 'sb.quantity', 'sb.quantity_return')
            ->get()
            ->groupBy('product_id');

        $hendhysStocksList = $hendhysProducts->map(function ($p) use ($pusatStocks, $cabangStocks) {
            $pusatQty = isset($pusatStocks[$p->id]) ? (int) $pusatStocks[$p->id]->quantity : 0;
            $pusatRet = isset($pusatStocks[$p->id]) ? (int) $pusatStocks[$p->id]->quantity_return : 0;

            $branches = [];
            $totalQty = $pusatQty;
            $totalRet = $pusatRet;

            if ($pusatQty > 0 || $pusatRet > 0) {
                $branches[] = [
                    'branch_id' => 'pusat',
                    'branch_name' => 'Hendhys Produksi (Pusat)',
                    'quantity' => $pusatQty,
                    'quantity_return' => $pusatRet,
                ];
            }

            if (isset($cabangStocks[$p->id])) {
                foreach ($cabangStocks[$p->id] as $cs) {
                    $qty = (int) $cs->quantity;
                    $ret = (int) $cs->quantity_return;
                    if ($qty > 0 || $ret > 0) {
                        $branches[] = [
                            'branch_id' => $cs->branch_id,
                            'branch_name' => $cs->branch_name,
                            'quantity' => $qty,
                            'quantity_return' => $ret,
                        ];
                        $totalQty += $qty;
                        $totalRet += $ret;
                    }
                }
            }

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'branches' => $branches,
                'total_quantity' => $totalQty,
                'total_quantity_return' => $totalRet,
            ];
        })->values();

        // 4. Movements List
        // Kartu ringkasan "movements" di atas menjumlahkan 3 sumber (Gudang, Retail
        // Jihans, Hendhys), tapi daftar ini dulu hanya membaca Gudang — sehingga
        // angka ringkasan dan isi daftarnya tidak pernah cocok.
        $mapMovement = fn ($m, string $sumber) => [
            'date'         => $m->created_at?->toDateTimeString(),
            'product_name' => $m->product?->name ?? '-',
            'type'         => $m->type,
            'quantity'     => (int) $m->quantity,
            'notes'        => $m->notes,
            'user'         => $m->creator?->name ?? '-',
            'sumber'       => $sumber,
        ];

        $movementsList = collect()
            ->concat(JihansGudangStockMovement::with(['product', 'creator'])->latest('id')->take(50)->get()
                ->map(fn ($m) => $mapMovement($m, 'Gudang')))
            ->concat(JihansRetailStockMovement::with(['product', 'creator'])->latest('id')->take(50)->get()
                ->map(fn ($m) => $mapMovement($m, "Jihan's Retail")))
            ->concat(HendhysStockMovement::with(['product', 'creator'])->latest('id')->take(50)->get()
                ->map(fn ($m) => $mapMovement($m, 'Hendhys')))
            ->sortByDesc('date')
            ->take(50)
            ->values();

        // 5. PO List
        $poList = PurchaseOrder::with(['supplier', 'creator'])->latest('id')->take(50)->get()
            ->map(fn($po) => [
                'po_number' => $po->po_number,
                'supplier' => $po->supplier?->name ?? '-',
                'date' => $po->date ? $po->date->toDateString() : '-',
                'status' => $po->status,
                'total_amount' => (float) $po->total_amount,
                'user' => $po->creator?->name ?? '-'
            ])->values();

        // 6. Jihans Transactions List
        $jihansTransactionsList = JihansTransaction::with('creator')->latest('id')->take(50)->get()
            ->map(fn($t) => [
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'customer' => $t->customer_name,
                'grand_total' => (float) $t->grand_total,
                'status' => $t->status,
                'user' => $t->creator?->name ?? '-'
            ])->values();

        // 7. Hendhys Transactions List
        $hendhysTransactionsList = HendhysTransaction::with(['creator', 'branch'])->latest('id')->take(50)->get()
            ->map(fn($t) => [
                'date' => $t->date,
                'transaction_number' => $t->transaction_number,
                'customer' => $t->customer_name,
                'grand_total' => (float) $t->grand_total,
                'status' => $t->status,
                'branch' => $t->branch?->name ?? 'Hendhys Produksi (Pusat)',
                'user' => $t->creator?->name ?? '-'
            ])->values();

        $jihansRevenue = (float) $omsetPeriod(JihansTransaction::where('status', 'paid'))->sum('grand_total');
        $hendhysRevenue = (float) $omsetPeriod(HendhysTransaction::where('status', 'paid'))->sum('grand_total');
        $hendhysPusatRevenue = (float) $omsetPeriod(
            HendhysTransaction::where('status', 'paid')->whereNull('branch_id')
        )->sum('grand_total');

        return Inertia::render('Owner/Dashboard', [
            'period'        => $period,
            'periodLabel'   => self::PERIODS[$period],
            'periodOptions' => collect(self::PERIODS)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),

            'stats' => [
                'jihans_revenue'        => $jihansRevenue,
                'hendhys_revenue'       => $hendhysRevenue,
                'hendhys_pusat_revenue' => $hendhysPusatRevenue,
                'total_revenue'         => $jihansRevenue + $hendhysRevenue,

                'jihans_today'       => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'hendhys_today'      => (float) HendhysTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'total_today'        => (float) (JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total') + HendhysTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total')),

                'stock' => [
                    'total' => $totalStock,
                    'jihans_gudang' => $jihansGudangStock,
                    'jihans_retail' => $jihansRetailStock,
                    'hendhys_pusat' => $hendhysPusatStock,
                    'hendhys_cabang_list' => $cabangList,
                ],

                'movements' => [
                    'count' => $totalMovementsCount,
                    'qty' => $totalMovementsQty,
                ],

                'po' => [
                    'count' => $totalPoCount,
                    'qty' => $totalPoQty,
                ],
            ],
            'trends' => $trends,
            'details' => [
                'gudang_stocks'        => $gudangStocksList,
                'jihans_stocks'        => $jihansStocksList,
                'hendhys_stocks'       => $hendhysStocksList,
                'movements'            => $movementsList,
                'purchase_orders'      => $poList,
                'jihans_transactions'  => $jihansTransactionsList,
                'hendhys_transactions' => $hendhysTransactionsList,
            ],
        ]);
    }

    public function detail(\Illuminate\Http\Request $request)
    {
        $mode = $request->query('mode', 'stock');
        $unit = $request->query('unit', 'gudang');
        $filter = $request->query('filter', 'all');

        // Samakan kosakata periode dengan dashboard supaya angka di kartu dan di
        // halaman detail tidak pernah berbeda. Kunci lama ('week'/'month') tetap
        // diterima agar tautan/bookmark yang sudah beredar tidak rusak.
        $filter = ['week' => 'this_week', 'month' => 'this_month'][$filter] ?? $filter;

        $isExactDate = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter);
        $range = $isExactDate ? null : $this->periodRange($filter);

        $title = 'Detail';
        $subtitle = '';
        $list = [];
        $shifts = collect(); // Store shifts for the omset mode

        // Apply date filter logic for omset
        $dateFilter = function ($q) use ($range, $isExactDate, $filter) {
            if ($isExactDate) {
                $q->whereDate('date', $filter);
            } elseif ($range) {
                $q->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()]);
            }
        };

        if ($mode === 'stock') {
            if ($unit === 'gudang') {
                $title = 'Jihans Gudang';
                $list = JihansGudangStock::with('product')->get()
                    ->map(fn($s) => [
                        'code' => $s->product?->code ?? '-',
                        'name' => $s->product?->name ?? '-',
                        'quantity' => (int) $s->quantity,
                        'unit' => $s->product?->unit?->abbreviation ?? 'PCS'
                    ])->values();
                $subtitle = number_format($list->sum('quantity'), 0, ',', '.') . ' Item';
            } elseif ($unit === 'retail') {
                $title = 'Jihans Retail';
                $list = DB::table('master_products as p')
                    ->leftJoin('jihans_retail_stock as s', 'p.id', '=', 's.product_id')
                    ->leftJoin('master_units as u', 'p.unit_id', '=', 'u.id')
                    ->where('p.status', 'active')
                    ->where(fn($w) => $w->whereRaw('p.visible_jihans = 1')->orWhereNotNull('s.quantity'))
                    ->select('p.name', 'p.code', DB::raw('COALESCE(s.quantity, 0) as quantity'), 'u.abbreviation as unit')
                    ->orderBy('p.name')
                    ->get()
                    ->map(fn($s) => [
                        'code' => $s->code,
                        'name' => $s->name,
                        'quantity' => (int) $s->quantity,
                        'unit' => $s->unit ?? 'PCS'
                    ])->values();
                $subtitle = number_format($list->sum('quantity'), 0, ',', '.') . ' Item';
            } elseif ($unit === 'hendhys_pusat') {
                $title = 'Hendhys Pusat';
                $hendhysProducts = DB::table('master_products')
                    ->where('status', 'active')
                    ->where('visible_hendhys', true)
                    ->orderBy('name')
                    ->get();
                $pusatStocks = DB::table('hendhys_stock_pusat')->get()->keyBy('product_id');
                $list = $hendhysProducts->map(function ($p) use ($pusatStocks) {
                    $pusatQty = isset($pusatStocks[$p->id]) ? (int) $pusatStocks[$p->id]->quantity : 0;
                    return [
                        'code' => $p->code,
                        'name' => $p->name,
                        'quantity' => $pusatQty,
                        'unit' => 'PCS'
                    ];
                })->filter(fn($p) => $p['quantity'] > 0)->values();
                $subtitle = number_format($list->sum('quantity'), 0, ',', '.') . ' Item';
            } elseif (str_starts_with($unit, 'hendhys_cabang_')) {
                $branchId = str_replace('hendhys_cabang_', '', $unit);
                $branch = Branch::find($branchId);
                $title = $branch ? $branch->name : 'Hendhys Cabang';
                
                $hendhysProducts = DB::table('master_products')
                    ->where('status', 'active')
                    ->where('visible_hendhys', true)
                    ->orderBy('name')
                    ->get();
                $cabangStocks = DB::table('hendhys_stock_branch')
                    ->where('branch_id', $branchId)
                    ->get()
                    ->keyBy('product_id');

                $list = $hendhysProducts->map(function ($p) use ($cabangStocks) {
                    $qty = isset($cabangStocks[$p->id]) ? (int) $cabangStocks[$p->id]->quantity : 0;
                    $qty_ret = isset($cabangStocks[$p->id]) ? (int) $cabangStocks[$p->id]->quantity_return : 0;
                    return [
                        'code' => $p->code,
                        'name' => $p->name,
                        'quantity' => $qty,
                        'quantity_return' => $qty_ret,
                        'unit' => 'PCS'
                    ];
                })->filter(fn($p) => $p['quantity'] > 0 || $p['quantity_return'] > 0)->values();
                $subtitle = number_format($list->sum('quantity'), 0, ',', '.') . ' Item';
            } elseif ($unit === 'movements') {
                $title = 'Mutasi Pergerakan Stok';
                $list = JihansGudangStockMovement::with(['product', 'creator'])->latest('id')->get()
                    ->map(fn($m) => [
                        'date' => $m->created_at->toDateTimeString(),
                        'product_name' => $m->product?->name ?? '-',
                        'type' => $m->type,
                        'quantity' => (int) $m->quantity,
                        'notes' => $m->notes,
                        'user' => $m->creator?->name ?? '-'
                    ])->values();
                $subtitle = $list->count() . ' Mutasi Terakhir';
            } elseif ($unit === 'po') {
                $title = 'Purchase Order Supplier';
                $list = PurchaseOrder::with(['supplier', 'creator'])->latest('id')->get()
                    ->map(fn($po) => [
                        'po_number' => $po->po_number,
                        'supplier' => $po->supplier?->name ?? '-',
                        'date' => $po->date ? $po->date->toDateString() : '-',
                        'status' => $po->status,
                        'total_amount' => (float) $po->total_amount,
                        'user' => $po->creator?->name ?? '-'
                    ])->values();
                $subtitle = $list->count() . ' PO Terakhir';
            }
        }
        
        $trends = [];
        if ($mode === 'omset') {
            Carbon::setLocale('id');

            $mapTransaction = function($t, $typeUnit) {
                return [
                    'id' => $t->id,
                    'date' => Carbon::parse($t->created_at ?? $t->date)->translatedFormat('d M Y, H:i') . ' (' . Carbon::parse($t->created_at ?? $t->date)->diffForHumans() . ')',
                    'transaction_number' => $t->transaction_number,
                    'customer' => $t->customer_name,
                    'grand_total' => (float) $t->grand_total,
                    'status' => $t->status,
                    'type_unit' => $typeUnit,
                    'user' => $t->creator?->name ?? '-',
                    'details' => $t->details->map(fn($d) => [
                        'product_name' => $d->product_name,
                        'quantity' => (int) $d->quantity,
                        'price' => (float) $d->price,
                        'total' => (float) $d->total,
                    ])
                ];
            };

            $dateFilterShift = function ($q) use ($range, $isExactDate, $filter) {
                if ($isExactDate) {
                    $q->whereDate('opened_at', $filter);
                } elseif ($range) {
                    // endOfDay: `opened_at` bertipe datetime, kalau dibatasi ke
                    // tanggal saja shift yang dibuka siang hari ikut terbuang.
                    $q->whereBetween('opened_at', [$range[0]->copy()->startOfDay(), $range[1]->copy()->endOfDay()]);
                }
            };

            $mapShift = function($s, $typeUnit) {
                $s->payment_summary = $s->calculatePaymentSummary();
                return [
                    'id' => $s->id,
                    'user' => $s->user?->name ?? 'Sistem',
                    'opened_at' => $s->opened_at,
                    'closed_at' => $s->closed_at,
                    'status' => $s->status,
                    'starting_cash' => $s->starting_cash,
                    'expected_cash' => $s->expected_cash,
                    'actual_cash' => $s->actual_cash,
                    'discrepancy' => $s->discrepancy,
                    'note' => $s->note,
                    'payment_summary' => $s->payment_summary,
                    'type_unit' => $typeUnit,
                ];
            };

            // Dynamic Trend Calculation based on filter
            $trendQueryCallback = null;
            $mapTrends = null;

            if ($filter === 'today' || $isExactDate) {
                $targetDate = $filter === 'today' ? today() : Carbon::parse($filter);
                $hours = collect();
                for ($i = 8; $i <= 22; $i++) {
                    $hours->push(sprintf('%02d:00', $i));
                }
                $trendQueryCallback = function($q) use ($targetDate) {
                    return $q->whereDate('date', $targetDate)
                             ->selectRaw('HOUR(created_at) as h, SUM(grand_total) as total')
                             ->groupBy('h')
                             ->pluck('total', 'h');
                };
                $mapTrends = function($salesMap1, $salesMap2 = []) use ($hours) {
                    return $hours->map(fn($h) => [
                        'date' => $h,
                        'total' => (float) (($salesMap1[(int)substr($h,0,2)] ?? 0) + ($salesMap2[(int)substr($h,0,2)] ?? 0)),
                    ])->values();
                };
            } elseif (in_array($filter, ['this_week', 'this_month', 'last_month'], true) && $range) {
                // Periode pendek (≤ 1 bulan) digambar per hari. Satu blok untuk
                // ketiganya, batasnya diambil dari $range — dulu "bulan ini"
                // di-hardcode ke bulan berjalan sehingga "bulan lalu" mustahil.
                $days = collect();
                $cursor = $range[0]->copy()->startOfDay();
                $last = $range[1]->copy()->startOfDay();
                while ($cursor->lte($last)) {
                    $days->push($cursor->format('Y-m-d'));
                    $cursor->addDay();
                }

                $trendQueryCallback = function ($q) use ($range) {
                    return $q->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()])
                             ->selectRaw('date, SUM(grand_total) as total')
                             ->groupBy('date')
                             ->pluck('total', 'date');
                };
                // Minggu ini cukup nama hari; rentang sebulan pakai tanggal.
                $labelFormat = $filter === 'this_week' ? 'D' : 'd';
                $mapTrends = function ($salesMap1, $salesMap2 = []) use ($days, $labelFormat) {
                    return $days->map(fn ($d) => [
                        'date' => Carbon::parse($d)->translatedFormat($labelFormat),
                        'total' => (float) (($salesMap1[$d] ?? 0) + ($salesMap2[$d] ?? 0)),
                    ])->values();
                };
            } else {
                $months = collect();
                for ($i = 1; $i <= 12; $i++) {
                    $months->push($i);
                }
                $trendQueryCallback = function($q) {
                    return $q->whereYear('date', now()->year)
                             ->selectRaw('MONTH(date) as m, SUM(grand_total) as total')
                             ->groupBy('m')
                             ->pluck('total', 'm');
                };
                $mapTrends = function($salesMap1, $salesMap2 = []) use ($months) {
                    return $months->map(fn($m) => [
                        'date' => Carbon::createFromDate(now()->year, $m, 1)->translatedFormat('M'),
                        'total' => (float) (($salesMap1[$m] ?? 0) + ($salesMap2[$m] ?? 0)),
                    ])->values();
                };
            }

            if ($unit === 'all_transactions') {
                $title = 'Semua Unit Bisnis';
                $jihansQuery = JihansTransaction::with(['creator', 'details'])->where('status', 'paid');
                $dateFilter($jihansQuery);
                $jihans = $jihansQuery->latest('id')->take(50)->get()->map(fn($t) => $mapTransaction($t, "Jihan's Food"));

                $hendhysQuery = HendhysTransaction::with(['creator', 'branch', 'details'])->where('status', 'paid');
                $dateFilter($hendhysQuery);
                $hendhys = $hendhysQuery->latest('id')->take(50)->get()->map(fn($t) => $mapTransaction($t, $t->branch?->name ?? 'Hendhys Produksi (Pusat)'));

                $list = collect($jihans)->concat($hendhys)->sortByDesc('id')->values();

                // Shifts
                $sJihansQ = CashierShift::with(['user', 'branch'])->where('entity', 'jihans');
                $dateFilterShift($sJihansQ);
                $sJihans = $sJihansQ->latest('id')->take(50)->get()->map(fn($s) => $mapShift($s, "Jihan's Food"));

                $sHendhysQ = CashierShift::with(['user', 'branch'])->where('entity', 'hendhys');
                $dateFilterShift($sHendhysQ);
                $sHendhys = $sHendhysQ->latest('id')->take(50)->get()->map(fn($s) => $mapShift($s, $s->branch?->name ?? 'Hendhys Pusat'));

                $shifts = collect($sJihans)->concat($sHendhys)->sortByDesc('id')->values();
                $totalJihansOmset = (clone $jihansQuery)->sum('grand_total');
                $totalHendhysOmset = (clone $hendhysQuery)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalJihansOmset + $totalHendhysOmset, 0, ',', '.');

                // Trends
                $jSales = $trendQueryCallback(JihansTransaction::where('status', 'paid'));
                $hSales = $trendQueryCallback(HendhysTransaction::where('status', 'paid'));
                $trends = $mapTrends($jSales, $hSales);
            } elseif ($unit === 'jihans_transactions') {
                $title = "Jihan's Food";
                $query = JihansTransaction::with(['creator', 'details'])->where('status', 'paid');
                $dateFilter($query);
                $list = $query->latest('id')->get()->map(fn($t) => $mapTransaction($t, "Jihan's Food"))->values();
                
                $shiftQ = CashierShift::with(['user'])->where('entity', 'jihans');
                $dateFilterShift($shiftQ);
                $shifts = $shiftQ->latest('id')->get()->map(fn($s) => $mapShift($s, "Jihan's Food"))->values();
                $totalOmset = (clone $query)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalOmset, 0, ',', '.');

                // Trends
                $sales = $trendQueryCallback(JihansTransaction::where('status', 'paid'));
                $trends = $mapTrends($sales);
            } elseif ($unit === 'hendhys_pusat') {
                $title = 'Hendhys Pusat';
                $query = HendhysTransaction::with(['creator', 'details'])->whereNull('branch_id')->where('status', 'paid');
                $dateFilter($query);
                $list = $query->latest('id')->get()->map(fn($t) => $mapTransaction($t, 'Hendhys Produksi (Pusat)'))->values();

                $shiftQ = CashierShift::with(['user'])->where('entity', 'hendhys')->whereNull('branch_id');
                $dateFilterShift($shiftQ);
                $shifts = $shiftQ->latest('id')->get()->map(fn($s) => $mapShift($s, 'Hendhys Produksi (Pusat)'))->values();
                $totalOmset = (clone $query)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalOmset, 0, ',', '.');

                // Trends
                $sales = $trendQueryCallback(HendhysTransaction::whereNull('branch_id')->where('status', 'paid'));
                $trends = $mapTrends($sales);
            } elseif (str_starts_with($unit, 'hendhys_cabang_')) {
                $branchId = str_replace('hendhys_cabang_', '', $unit);
                $branch = Branch::find($branchId);
                $title = $branch ? $branch->name : 'Hendhys Cabang';
                
                $query = HendhysTransaction::with(['creator', 'details'])->where('branch_id', $branchId)->where('status', 'paid');
                $dateFilter($query);
                $list = $query->latest('id')->get()->map(fn($t) => $mapTransaction($t, $title))->values();

                $shiftQ = CashierShift::with(['user'])->where('entity', 'hendhys')->where('branch_id', $branchId);
                $dateFilterShift($shiftQ);
                $shifts = $shiftQ->latest('id')->get()->map(fn($s) => $mapShift($s, $title))->values();
                $totalOmset = (clone $query)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalOmset, 0, ',', '.');

                // Trends
                $sales = $trendQueryCallback(HendhysTransaction::where('branch_id', $branchId)->where('status', 'paid'));
                $trends = $mapTrends($sales);
            }
        }

        return Inertia::render('Owner/Detail', [
            'mode' => $mode,
            'unit' => $unit,
            'title' => $title,
            'subtitle' => $subtitle,
            'list' => $list,
            'shifts' => $shifts,
            'filter' => $filter,
            'trends' => $trends
        ]);
    }

    public function shiftDetail($id)
    {
        $shift = \App\Models\CashierShift::with(['user', 'branch'])->findOrFail($id);
        
        $transactionTable = $shift->entity === 'hendhys' ? 'hendhys_transactions' : 'jihans_transactions';
        $paymentTable = $shift->entity === 'hendhys' ? 'hendhys_transaction_payments' : 'jihans_transaction_payments';
        $detailTable = $shift->entity === 'hendhys' ? 'hendhys_transaction_details' : 'jihans_transaction_details';

        // Pakai batas periode kanonik dari model, sama persis dengan yang dipakai
        // layar tutup kasir — dulu blok ini disalin ulang di sini dan ikut membawa
        // bug Carbon::parse(null) pada shift sebelumnya yang masih terbuka.
        $closedAt = $shift->periodEnd();
        $startAt  = $shift->periodStart();

        $transactions = \Illuminate\Support\Facades\DB::table($transactionTable . ' as t')
            ->select('t.*')
            ->where('t.created_by', $shift->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [$startAt, $closedAt])
            ->orderBy('t.created_at', 'desc')
            ->get();

        $trxIds = $transactions->pluck('id')->toArray();

        // Get details
        $details = \Illuminate\Support\Facades\DB::table($detailTable . ' as d')
            ->whereIn('transaction_id', $trxIds)
            ->get();

        // Attach details to transactions
        $transactions = $transactions->map(function($t) use ($details) {
            $t->details = $details->where('transaction_id', $t->id)->values();
            return $t;
        });

        $payments = \Illuminate\Support\Facades\DB::table($paymentTable . ' as p')
            ->whereIn('p.transaction_id', $trxIds)
            ->select('p.transaction_id', 'p.amount', 'p.payment_method')
            ->get();

        $payments = $payments->map(function($p) {
            $type = 'lainnya';
            $method = strtolower($p->payment_method ?? '');
            
            if ($method === 'cash' || $method === 'tunai') {
                $type = 'tunai';
            } elseif ($method === 'transfer') {
                $type = 'transfer';
            } elseif ($method === 'debit' || $method === 'kredit' || str_contains($method, 'qris')) {
                $type = $method === 'debit' || $method === 'kredit' ? $method : 'transfer';
            }
            
            $p->type = $type;
            return $p;
        });

        // Attach payments to transactions
        $transactions = $transactions->map(function($t) use ($payments) {
            $t->payments = $payments->where('transaction_id', $t->id)->values();
            return $t;
        });


        // Ringkasan pembayaran diambil dari sumber kebenaran yang sama dengan
        // layar tutup kasir (CashierShift::calculatePaymentSummary). Versi lama di
        // sini menghitung "tunai" sebagai sisa grand_total dikurangi non-tunai, dan
        // hanya membaca kolom legacy payment_method — akibatnya kartu debit/kredit
        // Hendhys (yang disimpan sebagai payment_method='transfer' + payment_type)
        // salah dikelompokkan, dan angka kas Owner bisa berbeda dari angka kasir
        // untuk shift yang sama.
        $paymentSummary = $shift->calculatePaymentSummary();

        $tunai    = (float) ($paymentSummary['tunai'] ?? 0);
        $transfer = (float) ($paymentSummary['transfer'] ?? 0);
        $debit    = (float) ($paymentSummary['kartu_debit'] ?? 0);
        $kredit   = (float) ($paymentSummary['kartu_kredit'] ?? 0);

        $totalOmset = $transactions->sum('grand_total');
        
        return \Inertia\Inertia::render('Owner/ShiftDetail', [
            'shift' => $shift,
            'transactions' => $transactions,
            'summary' => [
                'omset' => $totalOmset,
                'tunai' => $tunai,
                'transfer' => $transfer,
                'debit' => $debit,
                'kredit' => $kredit,
            ]
        ]);
    }
}
