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
    public function index()
    {
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
        $jihansGudangStock = (float) JihansGudangStock::sum('quantity');
        $jihansRetailStock = (float) JihansRetailStock::sum('quantity');
        $hendhysPusatStock = (float) HendhysStockPusat::sum('quantity');
        $hendhysCabangStock = (float) HendhysStockBranch::sum('quantity');
        $totalStock = $jihansGudangStock + $jihansRetailStock + $hendhysPusatStock + $hendhysCabangStock;

        // Dynamic Hendhys branches list with their current stock (Filtering out other entities)
        $cabangBranches = Branch::where('is_active', true)
            ->where('type', 'cabang')
            ->where(function($q) {
                $q->where('name', 'like', '%Hendhy%')
                  ->orWhere('code', 'like', 'HB%')
                  ->orWhere('code', 'like', 'HND%');
            })
            ->orderBy('name')
            ->get();

        $cabangList = [];
        foreach ($cabangBranches as $cb) {
            $qty = (float) HendhysStockBranch::where('branch_id', $cb->id)->sum('quantity');
            $revenue = (float) HendhysTransaction::where('status', 'paid')->where('branch_id', $cb->id)->sum('grand_total');
            $cabangList[] = [
                'id' => $cb->id,
                'name' => $cb->name,
                'quantity' => $qty,
                'revenue' => $revenue,
            ];
        }

        // Movements Calculation
        $jihansGudangMovementsCount = JihansGudangStockMovement::count();
        $jihansGudangMovementsQty = (float) JihansGudangStockMovement::sum('quantity');
        
        $jihansRetailMovementsCount = JihansRetailStockMovement::count();
        $jihansRetailMovementsQty = (float) JihansRetailStockMovement::sum('quantity');

        $hendhysMovementsCount = HendhysStockMovement::count();
        $hendhysMovementsQty = (float) HendhysStockMovement::sum('quantity');

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
                'quantity' => (float) $s->quantity,
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
                'quantity' => (float) $s->quantity,
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
            $pusatQty = isset($pusatStocks[$p->id]) ? (float) $pusatStocks[$p->id]->quantity : 0.0;
            $pusatRet = isset($pusatStocks[$p->id]) ? (float) $pusatStocks[$p->id]->quantity_return : 0.0;

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
                    $qty = (float) $cs->quantity;
                    $ret = (float) $cs->quantity_return;
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
        $movementsList = JihansGudangStockMovement::with(['product', 'creator'])->latest('id')->take(50)->get()
            ->map(fn($m) => [
                'date' => $m->created_at->toDateTimeString(),
                'product_name' => $m->product?->name ?? '-',
                'type' => $m->type,
                'quantity' => (float) $m->quantity,
                'notes' => $m->notes,
                'user' => $m->creator?->name ?? '-'
            ])->values();

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

        return Inertia::render('Owner/Dashboard', [
            'stats' => [
                'jihans_revenue'     => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
                'hendhys_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'hendhys_pusat_revenue' => (float) HendhysTransaction::where('status', 'paid')->whereNull('branch_id')->sum('grand_total'),
                'total_revenue'      => (float) (JihansTransaction::where('status', 'paid')->sum('grand_total') + HendhysTransaction::where('status', 'paid')->sum('grand_total')),
                
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
        $filter = $request->query('filter', 'all'); // 'today', 'week', 'month', 'all'

        $title = 'Detail';
        $subtitle = '';
        $list = [];
        $shifts = collect(); // Store shifts for the omset mode

        // Apply date filter logic for omset
        $dateFilter = function($q) use ($filter) {
            if ($filter === 'today') {
                $q->whereDate('date', today());
            } elseif ($filter === 'week') {
                $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($filter === 'month') {
                $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
            }
        };

        if ($mode === 'stock') {
            if ($unit === 'gudang') {
                $title = 'Jihans Gudang';
                $list = JihansGudangStock::with('product')->get()
                    ->map(fn($s) => [
                        'code' => $s->product?->code ?? '-',
                        'name' => $s->product?->name ?? '-',
                        'quantity' => (float) $s->quantity,
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
                        'quantity' => (float) $s->quantity,
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
                    $pusatQty = isset($pusatStocks[$p->id]) ? (float) $pusatStocks[$p->id]->quantity : 0.0;
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
                    $qty = isset($cabangStocks[$p->id]) ? (float) $cabangStocks[$p->id]->quantity : 0.0;
                    $qty_ret = isset($cabangStocks[$p->id]) ? (float) $cabangStocks[$p->id]->quantity_return : 0.0;
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
                        'quantity' => (float) $m->quantity,
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
                        'quantity' => (float) $d->quantity,
                        'price' => (float) $d->price,
                        'total' => (float) $d->total,
                    ])
                ];
            };

            $dateFilterShift = function($q) use ($filter) {
                if ($filter === 'today') {
                    $q->whereDate('opened_at', today());
                } elseif ($filter === 'week') {
                    $q->whereBetween('opened_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($filter === 'month') {
                    $q->whereMonth('opened_at', now()->month)->whereYear('opened_at', now()->year);
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

            if ($filter === 'today') {
                $hours = collect();
                for ($i = 8; $i <= 22; $i++) {
                    $hours->push(sprintf('%02d:00', $i));
                }
                $trendQueryCallback = function($q) {
                    return $q->whereDate('date', today())
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
            } elseif ($filter === 'week') {
                $days = collect();
                $start = now()->startOfWeek();
                for ($i = 0; $i < 7; $i++) {
                    $days->push($start->copy()->addDays($i)->format('Y-m-d'));
                }
                $trendQueryCallback = function($q) use ($start) {
                    return $q->whereBetween('date', [$start, now()->endOfWeek()])
                             ->selectRaw('date, SUM(grand_total) as total')
                             ->groupBy('date')
                             ->pluck('total', 'date');
                };
                $mapTrends = function($salesMap1, $salesMap2 = []) use ($days) {
                    return $days->map(fn($d) => [
                        'date' => Carbon::parse($d)->translatedFormat('D'),
                        'total' => (float) (($salesMap1[$d] ?? 0) + ($salesMap2[$d] ?? 0)),
                    ])->values();
                };
            } elseif ($filter === 'month') {
                $days = collect();
                $start = now()->startOfMonth();
                $daysInMonth = now()->daysInMonth;
                for ($i = 0; $i < $daysInMonth; $i++) {
                    $days->push($start->copy()->addDays($i)->format('Y-m-d'));
                }
                $trendQueryCallback = function($q) {
                    return $q->whereMonth('date', now()->month)
                             ->whereYear('date', now()->year)
                             ->selectRaw('date, SUM(grand_total) as total')
                             ->groupBy('date')
                             ->pluck('total', 'date');
                };
                $mapTrends = function($salesMap1, $salesMap2 = []) use ($days) {
                    return $days->map(fn($d) => [
                        'date' => Carbon::parse($d)->format('d'),
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

        $closedAt = $shift->closed_at ?? now();

        $previousShift = \App\Models\CashierShift::where('user_id', $shift->user_id)
            ->where('branch_id', $shift->branch_id)
            ->where('id', '<', $shift->id)
            ->orderBy('id', 'desc')
            ->first();

        $startAt = $previousShift ? $previousShift->closed_at : \Carbon\Carbon::parse($shift->opened_at)->startOfDay();

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


        $tunai = 0;
        $transfer = 0;
        $debit = 0;
        $kredit = 0;

        foreach ($transactions as $t) {
            $tTransfer = $t->payments->where('type', 'transfer')->sum('amount');
            $tDebit = $t->payments->where('type', 'debit')->sum('amount');
            $tKredit = $t->payments->where('type', 'kredit')->sum('amount');
            
            // Tunai adalah sisa dari grand_total dikurangi pembayaran non-tunai (karena kembalian selalu dalam bentuk tunai)
            $tTunai = max(0, $t->grand_total - ($tTransfer + $tDebit + $tKredit));
            
            $tunai += $tTunai;
            $transfer += $tTransfer;
            $debit += $tDebit;
            $kredit += $tKredit;
        }

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
