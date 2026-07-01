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
}
