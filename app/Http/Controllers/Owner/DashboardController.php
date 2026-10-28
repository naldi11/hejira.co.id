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
     * SEMUA periode berbasis bulan dibulatkan ke batas bulan penuh — termasuk
     * `last_6_months` dan `last_year`, yang berakhir di akhir bulan berjalan,
     * bukan di tanggal hari ini. Ini permintaan eksplisit user: laporan omset
     * dibaca per bulan penuh, dan memotong bulan berjalan di tengah membuat
     * angkanya tidak setara untuk dibandingkan.
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
            // Dibulatkan ke batas BULAN, bukan tanggal berjalan: laporan omset
            // dibaca per bulan penuh, sehingga "6 bulan terakhir" yang berhenti
            // di tanggal 4 akan memotong bulan berjalan di tengah dan membuat
            // angkanya tidak bisa dibandingkan dengan bulan-bulan sebelumnya.
            'last_6_months' => [now()->subMonthsNoOverflow(6)->startOfMonth(), now()->endOfMonth()],
            'last_year'     => [now()->subYearNoOverflow()->startOfMonth(), now()->endOfMonth()],
            default         => null, // 'all'
        };
    }

    /**
     * Keterangan rentang tanggal sebuah periode, dalam bahasa Indonesia.
     *
     * Label seperti "Bulan Ini" tidak memberi tahu bulan apa dan tanggal berapa
     * saja yang ikut terhitung. Keterangan ini yang menjawabnya, sehingga angka
     * omset di kartu tidak perlu ditebak cakupannya.
     *
     * `->locale('id')` WAJIB eksplisit: APP_LOCALE aplikasi ini bernilai 'en',
     * tanpa itu keluar "August" alih-alih "Agustus".
     */
    private function periodDescription(?array $range): string
    {
        if (! $range) {
            // 'Keseluruhan' tidak punya batas yang ditentukan di kode — ambil
            // tanggal transaksi paling awal yang benar-benar ada di data.
            $earliest = collect([
                JihansTransaction::min('date'),
                HendhysTransaction::min('date'),
            ])->filter()->min();

            return $earliest
                ? 'Sejak ' . Carbon::parse($earliest)->locale('id')->translatedFormat('j F Y') . ' sampai hari ini'
                : 'Seluruh data yang tercatat';
        }

        $start = $range[0]->copy()->locale('id');
        $end   = $range[1]->copy()->locale('id');

        // Satu hari
        if ($start->toDateString() === $end->toDateString()) {
            return $start->translatedFormat('l, j F Y');
        }
        // Masih dalam bulan yang sama: "1 – 31 Agustus 2026"
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->translatedFormat('j') . ' – ' . $end->translatedFormat('j F Y');
        }
        // Beda bulan, tahun sama: "4 Februari – 4 Agustus 2026"
        if ($start->format('Y') === $end->format('Y')) {
            return $start->translatedFormat('j F') . ' – ' . $end->translatedFormat('j F Y');
        }
        // Beda tahun: "4 Agustus 2025 – 4 Agustus 2026"
        return $start->translatedFormat('j F Y') . ' – ' . $end->translatedFormat('j F Y');
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
            ->where('date', '>=', today()->subDays(6)->toDateString())
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $hendhysSales = HendhysTransaction::where('status', 'paid')
            ->where('date', '>=', today()->subDays(6)->toDateString())
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $izziSales = DB::table('izzi_sales')->where('created_at', '>=', today()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(net_total) as total')->groupBy('date')->pluck('total', 'date');
        $trends = $days->map(fn($d) => [
            'date' => Carbon::parse($d)->format('d M'),
            'jihans' => (float) ($jihansSales[$d] ?? 0),
            'hendhys' => (float) ($hendhysSales[$d] ?? 0),
            'izzi' => (float) ($izziSales[$d] ?? 0),
            'total' => (float) (($jihansSales[$d] ?? 0) + ($hendhysSales[$d] ?? 0) + ($izziSales[$d] ?? 0)),
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

        $movementTotals = collect([JihansGudangStockMovement::class, JihansRetailStockMovement::class, HendhysStockMovement::class])
            ->map(fn ($model) => $model::selectRaw('COUNT(*) as count, COALESCE(SUM(quantity), 0) as qty')->first());
        $totalMovementsCount = (int) $movementTotals->sum('count');
        $totalMovementsQty = (int) $movementTotals->sum('qty');

        // PO Calculation
        $totalPoCount = PurchaseOrder::count();
        $totalPoQty = (float) PoDetail::sum('quantity_ordered');

        // The dashboard renders aggregate cards only. Item lists are fetched by
        // their paginated detail routes rather than loaded and serialized here.
        $jihansRevenue = (float) $omsetPeriod(JihansTransaction::where('status', 'paid'))->sum('grand_total');
        $hendhysRevenue = (float) $omsetPeriod(HendhysTransaction::where('status', 'paid'))->sum('grand_total');
        $hendhysPusatRevenue = (float) $omsetPeriod(
            HendhysTransaction::where('status', 'paid')->whereNull('branch_id')
        )->sum('grand_total');

        $izziRevenue = (float) DB::table('izzi_sales')->when($range, fn ($q) => $q->whereBetween('created_at', [$range[0]->copy()->startOfDay(), $range[1]->copy()->endOfDay()]))->sum('net_total');
        $izziToday = (float) DB::table('izzi_sales')->where('created_at', '>=', today()->startOfDay())->where('created_at', '<', today()->addDay()->startOfDay())->sum('net_total');
        $jihansToday = (float) JihansTransaction::where('status', 'paid')->where('date', today()->toDateString())->sum('grand_total');
        $hendhysToday = (float) HendhysTransaction::where('status', 'paid')->where('date', today()->toDateString())->sum('grand_total');

        return Inertia::render('Owner/Dashboard', [
            'production_monitor' => fn () => app(\App\Services\ProductionStockMonitor::class)->summaryForRequest($request),
            'period'        => $period,
            'periodLabel'   => self::PERIODS[$period],
            'periodRange'   => $this->periodDescription($range),
            'periodOptions' => collect(self::PERIODS)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),

            'stats' => [
                'jihans_revenue'        => $jihansRevenue,
                'hendhys_revenue'       => $hendhysRevenue,
                'hendhys_pusat_revenue' => $hendhysPusatRevenue,
                'izzi_revenue' => $izziRevenue,
                'izzi_date_from' => $range ? $range[0]->toDateString() : null,
                'izzi_date_to' => $range ? $range[1]->toDateString() : null,
                'total_revenue'         => $jihansRevenue + $hendhysRevenue + $izziRevenue,

                'jihans_today'       => $jihansToday,
                'hendhys_today'      => $hendhysToday,
                'izzi_today' => $izziToday,
                'total_today'        => $jihansToday + $hendhysToday + $izziToday,

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
                $q->where('date', $filter);
            } elseif ($range) {
                $q->whereBetween('date', [$range[0]->toDateString(), $range[1]->toDateString()]);
            }
        };

        if ($mode === 'stock') {
            return $this->stockDetail($request, $unit, $filter);
        }

        $input = $request->validate([
            'per_page' => ['nullable', 'in:20,50,100,all'],
            'search' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'shift_page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (string) ($input['per_page'] ?? '20');
        $search = trim($input['search'] ?? '');
        $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
        $pagination = $shiftPagination = null;
        $searchTransactions = function ($q) use ($search, $needle) {
            if ($search === '') return;
            $hasBranch = $q->getModel() instanceof HendhysTransaction;
            $q->where(function ($q) use ($needle, $hasBranch) {
                $q->whereRaw("LOWER(transaction_number) LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("LOWER(customer_name) LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereHas('creator', fn ($u) => $u->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$needle]))
                    ->orWhereHas('details', fn ($d) => $d->whereRaw("LOWER(product_name) LIKE ? ESCAPE '!'", [$needle]));
                if ($hasBranch) $q->orWhereHas('branch', fn ($b) => $b->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$needle]));
            });
        };
        $paginateShifts = function ($q, $map) use ($perPage, $search, $needle) {
            if ($search !== '') $q->where(function ($q) use ($needle) {
                $q->whereRaw("LOWER(note) LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereHas('user', fn ($u) => $u->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$needle]))
                    ->orWhereHas('branch', fn ($b) => $b->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$needle]));
            });
            return $this->paginateDetail($q->latest('id'), $perPage, $map, 'shift_page');
        };
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
                             ->selectRaw((DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H', created_at) AS INTEGER)" : 'HOUR(created_at)').' as h, SUM(grand_total) as total')
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
                             ->selectRaw((DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', date) AS INTEGER)" : 'MONTH(date)').' as m, SUM(grand_total) as total')
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
                $searchTransactions($jihansQuery);
                $hendhysQuery = HendhysTransaction::with(['creator', 'branch', 'details'])->where('status', 'paid');
                $dateFilter($hendhysQuery);
                $searchTransactions($hendhysQuery);
                $izziQuery = DB::query()->fromSub(DB::table('izzi_sales')
                    ->when($isExactDate, fn ($q) => $q->where('created_at', '>=', $filter.' 00:00:00')->where('created_at', '<', Carbon::parse($filter)->addDay()->startOfDay()))
                    ->when($range, fn ($q) => $q->whereBetween('created_at', [$range[0]->copy()->startOfDay(), $range[1]->copy()->endOfDay()]))
                    ->select('*', DB::raw('DATE(created_at) as date'), DB::raw('net_total as grand_total')), 'izzi')
                    ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', '%'.$search.'%')->orWhere('customer_name', 'like', '%'.$search.'%')->orWhere('actor_name', 'like', '%'.$search.'%')->orWhere('branch_name', 'like', '%'.$search.'%')));
                // IDs are only unique within each business unit. Paginate keys from
                // both tables, then hydrate just that page while retaining its order.
                $keys = (clone $jihansQuery)->select('id', 'created_at', DB::raw("'jihans' as entity"))->toBase()
                    ->unionAll((clone $hendhysQuery)->select('id', 'created_at', DB::raw("'hendhys' as entity"))->toBase())
                    ->unionAll((clone $izziQuery)->select('id', 'created_at', DB::raw("'izzi' as entity")));
                $combined = DB::query()->fromSub($keys, 'transactions')
                    ->orderByDesc('created_at')->orderByDesc('id')->orderBy('entity');
                [$references, $pagination] = $this->paginateDetail($combined, $perPage, fn ($r) => $r);
                $jRows = JihansTransaction::with(['creator', 'details'])->whereIn('id', $references->where('entity', 'jihans')->pluck('id'))->get()->keyBy('id');
                $hRows = HendhysTransaction::with(['creator', 'branch', 'details'])->whereIn('id', $references->where('entity', 'hendhys')->pluck('id'))->get()->keyBy('id');
                $iRows = DB::table('izzi_sales')->whereIn('id', $references->where('entity', 'izzi')->pluck('id'))->get()->keyBy('id');
                $iLines = DB::table('izzi_sale_lines')->whereIn('sale_id', $iRows->keys())->get()->groupBy('sale_id');
                $list = $references->map(function ($r) use ($jRows, $hRows, $iRows, $iLines, $mapTransaction) {
                    if ($r->entity === 'izzi') {
                        $t = $iRows[$r->id];
                        return ['id' => $t->id, 'date' => Carbon::parse($t->created_at)->translatedFormat('d M Y, H:i').' WIB',
                            'transaction_number' => $t->number, 'customer' => $t->customer_name, 'grand_total' => (float) $t->net_total,
                            'status' => $t->status, 'type_unit' => 'Izzi Kebab · '.$t->branch_name, 'user' => $t->actor_name,
                            'href' => route('izzi.sales.show', ['sale' => $t->id, 'branch_id' => $t->branch_id]),
                            'details' => $iLines->get($t->id, collect())->map(fn ($l) => ['product_name' => $l->menu_name,
                                'quantity' => $l->quantity - $l->cancelled_quantity, 'price' => (float) $l->price, 'total' => (float) (($l->quantity - $l->cancelled_quantity) * $l->price)])];
                    }
                    $t = $r->entity === 'jihans' ? $jRows[$r->id] : $hRows[$r->id];
                    return $mapTransaction($t, $r->entity === 'jihans' ? "Jihan's Food" : ($t->branch?->name ?? 'Hendhys Produksi (Pusat)'));
                });

                $shiftQ = CashierShift::with(['user', 'branch'])->whereIn('entity', ['jihans', 'hendhys', 'izzi']);
                $dateFilterShift($shiftQ);
                [$shifts, $shiftPagination] = $paginateShifts($shiftQ, fn ($s) => $mapShift($s,
                    $s->entity === 'izzi' ? 'Izzi Kebab · '.$s->branch?->name : ($s->entity === 'jihans' ? "Jihan's Food" : ($s->branch?->name ?? 'Hendhys Produksi (Pusat)'))));
                $totalJihansOmset = (clone $jihansQuery)->sum('grand_total');
                $totalHendhysOmset = (clone $hendhysQuery)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalJihansOmset + $totalHendhysOmset + (clone $izziQuery)->sum('net_total'), 0, ',', '.');

                // Trends
                $jSales = $trendQueryCallback(JihansTransaction::where('status', 'paid'));
                $hSales = $trendQueryCallback(HendhysTransaction::where('status', 'paid'));
                $iSales = $trendQueryCallback(DB::query()->fromSub(DB::table('izzi_sales')->select('created_at', DB::raw('DATE(created_at) as date'), DB::raw('net_total as grand_total')), 'sales'));
                $trends = $mapTrends($jSales, collect($hSales)->map(fn ($v, $k) => $v + ($iSales[$k] ?? 0))->union($iSales)->all());
            } elseif ($unit === 'jihans_transactions') {
                $title = "Jihan's Food";
                $query = JihansTransaction::with(['creator', 'details'])->where('status', 'paid');
                $dateFilter($query);
                $searchTransactions($query);
                [$list, $pagination] = $this->paginateDetail($query->latest('id'), $perPage, fn ($t) => $mapTransaction($t, "Jihan's Food"));
                
                $shiftQ = CashierShift::with(['user'])->where('entity', 'jihans');
                $dateFilterShift($shiftQ);
                [$shifts, $shiftPagination] = $paginateShifts($shiftQ, fn ($s) => $mapShift($s, "Jihan's Food"));
                $totalOmset = (clone $query)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalOmset, 0, ',', '.');

                // Trends
                $sales = $trendQueryCallback(JihansTransaction::where('status', 'paid'));
                $trends = $mapTrends($sales);
            } elseif ($unit === 'hendhys_pusat') {
                $title = 'Hendhys Pusat';
                $query = HendhysTransaction::with(['creator', 'details'])->whereNull('branch_id')->where('status', 'paid');
                $dateFilter($query);
                $searchTransactions($query);
                [$list, $pagination] = $this->paginateDetail($query->latest('id'), $perPage, fn ($t) => $mapTransaction($t, 'Hendhys Produksi (Pusat)'));

                $shiftQ = CashierShift::with(['user'])->where('entity', 'hendhys')->whereNull('branch_id');
                $dateFilterShift($shiftQ);
                [$shifts, $shiftPagination] = $paginateShifts($shiftQ, fn ($s) => $mapShift($s, 'Hendhys Produksi (Pusat)'));
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
                $searchTransactions($query);
                [$list, $pagination] = $this->paginateDetail($query->latest('id'), $perPage, fn ($t) => $mapTransaction($t, $title));

                $shiftQ = CashierShift::with(['user'])->where('entity', 'hendhys')->where('branch_id', $branchId);
                $dateFilterShift($shiftQ);
                [$shifts, $shiftPagination] = $paginateShifts($shiftQ, fn ($s) => $mapShift($s, $title));
                $totalOmset = (clone $query)->sum('grand_total');
                $subtitle = 'Total Omset: Rp ' . number_format($totalOmset, 0, ',', '.');

                // Trends
                $sales = $trendQueryCallback(HendhysTransaction::where('branch_id', $branchId)->where('status', 'paid'));
                $trends = $mapTrends($sales);
            }
        }

        return Inertia::render('Owner/Detail', [
            'production_monitor' => fn () => app(\App\Services\ProductionStockMonitor::class)->summaryForRequest($request),
            'mode' => $mode,
            'unit' => $unit,
            'title' => $title,
            'subtitle' => $subtitle,
            'list' => $list,
            'shifts' => $shifts,
            'filter' => $filter,
            'trends' => $trends,
            'pagination' => $pagination, 'shifts_pagination' => $shiftPagination,
            'search' => $search, 'per_page' => $perPage,
        ]);
    }

    private function stockDetail(\Illuminate\Http\Request $request, string $unit, string $filter)
    {
        $input = $request->validate([
            'per_page' => ['nullable', 'in:20,50,100,all'],
            'search' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $perPage = (string) ($input['per_page'] ?? '20');
        $search = trim($input['search'] ?? '');
        // Escape wildcard characters so a product code containing % or _ is searchable literally.
        $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
        $match = fn ($q, $column) => $q->whereRaw("LOWER($column) LIKE ? ESCAPE '!'", [$needle]);

        if ($unit === 'movements') {
            $title = 'Mutasi Pergerakan Stok';
            $query = JihansGudangStockMovement::with(['product', 'creator'])->latest('id');
            if ($search !== '') {
                $query->where(function ($q) use ($match, $needle) {
                    $match($q, 'notes');
                    $q->orWhereHas('product', function ($p) use ($match, $needle) {
                        $match($p, 'name');
                        $p->orWhereRaw("LOWER(code) LIKE ? ESCAPE '!'", [$needle]);
                    })->orWhereHas('creator', fn ($u) => $match($u, 'name'));
                });
            }
            $map = fn ($m) => [
                'id' => $m->id, 'date' => $m->created_at->toDateTimeString(),
                'product_name' => $m->product?->name ?? '-', 'type' => $m->type,
                'quantity' => (int) $m->quantity, 'notes' => $m->notes, 'user' => $m->creator?->name ?? '-',
            ];
            $subtitle = ''; // Filled from the paginator's count below.
        } elseif ($unit === 'po') {
            $title = 'Purchase Order Supplier';
            $query = PurchaseOrder::with(['supplier', 'creator'])->latest('id');
            if ($search !== '') {
                $query->where(function ($q) use ($match) {
                    $match($q, 'po_number');
                    $q->orWhereHas('supplier', fn ($s) => $match($s, 'name'))
                        ->orWhereHas('creator', fn ($u) => $match($u, 'name'));
                });
            }
            $map = fn ($po) => [
                'id' => $po->id, 'po_number' => $po->po_number, 'supplier' => $po->supplier?->name ?? '-',
                'date' => $po->date?->toDateString() ?? '-', 'status' => $po->status,
                'total_amount' => (float) $po->total_amount, 'user' => $po->creator?->name ?? '-',
            ];
            $subtitle = '';
        } else {
            $query = DB::table('master_products as p')->leftJoin('master_units as u', 'p.unit_id', '=', 'u.id');
            $quantity = 'COALESCE(s.quantity, 0)';
            $isBranch = str_starts_with($unit, 'hendhys_cabang_');
            if ($unit === 'gudang') {
                $title = 'Gudang Utama';
                // Include archived products that still have stock, as in the warehouse product relation.
                $query->join('jihans_gudang_stock as s', 'p.id', '=', 's.product_id');
            } elseif ($unit === 'retail') {
                $title = "Stok Penjualan Jihan’s";
                $query->leftJoin('jihans_retail_stock as s', 'p.id', '=', 's.product_id')
                    ->where('p.status', 'active')
                    ->where(fn ($q) => $q->where('p.visible_jihans', 1)->orWhereNotNull('s.quantity'));
            } elseif ($unit === 'hendhys_pusat' || $isBranch) {
                $query->where('p.status', 'active')->where('p.visible_hendhys', true);
                if ($isBranch) {
                    $branchId = substr($unit, strlen('hendhys_cabang_'));
                    $branch = Branch::findOrFail($branchId);
                    $title = $branch->name;
                    $query->leftJoin('hendhys_stock_branch as s', fn ($join) => $join->on('p.id', '=', 's.product_id')->where('s.branch_id', $branchId))
                        ->where(fn ($q) => $q->where('s.quantity', '>', 0)->orWhere('s.quantity_return', '>', 0));
                } else {
                    $title = 'Hendhys Produksi (Pusat)';
                    $query->leftJoin('hendhys_stock_pusat as s', 'p.id', '=', 's.product_id')->where('s.quantity', '>', 0);
                }
            } else {
                abort(404);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($match, $needle) {
                    $match($q, 'p.name');
                    $q->orWhereRaw("LOWER(p.code) LIKE ? ESCAPE '!'", [$needle]);
                });
            }
            $subtitle = number_format((clone $query)->sum(DB::raw($quantity)), 0, ',', '.').' Item';
            $query->select('p.code', 'p.name', DB::raw("$quantity as quantity"), 'u.abbreviation as unit');
            if ($isBranch) $query->addSelect(DB::raw('COALESCE(s.quantity_return, 0) as quantity_return'));
            $query->orderByRaw("CASE WHEN $quantity > 0 THEN 0 ELSE 1 END")->orderBy('p.name')->orderBy('p.id');
            $map = fn ($s) => [
                'code' => $s->code, 'name' => $s->name, 'quantity' => (int) $s->quantity,
                'unit' => $s->unit ?? 'PCS',
            ] + ($isBranch ? ['quantity_return' => (int) $s->quantity_return] : []);
        }

        [$list, $pagination] = $this->paginateDetail($query, $perPage, $map);
        if ($unit === 'movements' || $unit === 'po') $subtitle = $pagination['total'].($unit === 'po' ? ' PO' : ' Mutasi');
        return Inertia::render('Owner/Detail', [
            'production_monitor' => function () use ($request, $unit, $list) {
                $monitor = app(\App\Services\ProductionStockMonitor::class);
                $summary = $monitor->summaryForRequest($request);
                if ($unit === 'hendhys_pusat') {
                    $codes = array_flip($list->pluck('code')->all());
                    $summary['stocks'] = array_values(array_filter($monitor->overviewForRequest($request)['stocks'], fn ($row) => isset($codes[$row['code']])));
                }
                return $summary;
            },
            'mode' => 'stock', 'unit' => $unit, 'filter' => $filter, 'title' => $title, 'subtitle' => $subtitle,
            'list' => $list, 'shifts' => [], 'trends' => [], 'pagination' => $pagination,
            'search' => $search, 'per_page' => $perPage,
        ]);
    }

    private function paginateDetail($query, string $perPage, callable $map, string $pageName = 'page'): array
    {
        if ($perPage === 'all') {
            $list = $query->get()->map($map)->values();
            $total = $list->count();
            return [$list, ['total' => $total, 'from' => $total ? 1 : null, 'to' => $total ?: null, 'links' => []]];
        }
        $paginator = $query->paginate((int) $perPage, ['*'], $pageName)->onEachSide(1)->withQueryString();
        return [$paginator->getCollection()->map($map)->values(), \Illuminate\Support\Arr::except($paginator->toArray(), ['data'])];
    }

    public function shiftDetail($id)
    {
        $shift = \App\Models\CashierShift::with(['user', 'branch'])->findOrFail($id);
        
        if ($shift->entity === 'izzi') return redirect()->route('izzi.shifts', ['branch_id' => $shift->branch_id]);
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
            ->tap(fn ($q) => $shift->scopeTransactions($q))
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
