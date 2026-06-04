<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysBranchRequest;
use App\Models\HendhysPendingTransaction;
use App\Models\HendhysProduction;
use App\Models\HendhysReturnFromBranch;
use App\Models\HendhysTransaction;
use App\Models\Product;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $isPusat = $user->branch->type === 'pusat';
        $branchId = $user->branch_id;

        // Build recent transactions scoped by branch
        $qRecent = HendhysTransaction::with('creator')->latest('id')->take(5);
        if (!$isPusat) {
            $qRecent->where('branch_id', $branchId);
        } else {
            $qRecent->whereNull('branch_id');
        }
        $recent = $qRecent->get()->map(fn ($t) => [
            'id'                 => $t->id,
            'transaction_number' => $t->transaction_number,
            'date'               => $t->date,
            'time'               => $t->time,
            'customer_name'      => $t->customer_name,
            'grand_total'        => (float) $t->grand_total,
        ]);

        // Low stocks
        if ($isPusat) {
            $lowStocks = Product::where('status', 'active')
                ->whereIn('master_products.entity_scope', ['hendhys', 'all'])
                ->join('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
                ->where('hendhys_stock_pusat.quantity', '<=', 10)
                ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock')
                ->take(5)->get();
        } else {
            $lowStocks = Product::where('status', 'active')
                ->whereIn('master_products.entity_scope', ['hendhys', 'all'])
                ->join('hendhys_stock_branch', 'master_products.id', '=', 'hendhys_stock_branch.product_id')
                ->where('hendhys_stock_branch.branch_id', $branchId)
                ->where('hendhys_stock_branch.quantity', '<=', 5)
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock')
                ->take(5)->get();
        }

        $lowStocksData = $lowStocks->map(fn ($s) => [
            'id'            => $s->id,
            'name'          => $s->name,
            'code'          => $s->code,
            'current_stock' => (float) $s->current_stock,
        ]);

        // Stats
        $qSales = HendhysTransaction::whereDate('date', now())->where('status', 'paid');
        if (!$isPusat) $qSales->where('branch_id', $branchId);
        else $qSales->whereNull('branch_id');

        $qPending = HendhysPendingTransaction::query();
        if (!$isPusat) $qPending->where('branch_id', $branchId);
        else $qPending->whereNull('branch_id');

        $stats = [
            'omset_hari_ini' => (float) $qSales->sum('grand_total'),
            'pending_count'  => $qPending->count(),
            'is_pusat'       => $isPusat,
        ];

        if ($isPusat) {
            $stats['produksi_hari_ini'] = HendhysProduction::whereDate('date', now())->count();
            $stats['request_pending_cabang'] = HendhysBranchRequest::where('status', 'pending')->count();
        } else {
            $stats['return_bulan_ini'] = HendhysReturnFromBranch::where('branch_id', $branchId)
                ->whereMonth('date', now()->month)->count();
            $stats['request_pending'] = HendhysBranchRequest::where('branch_id', $branchId)
                ->where('status', 'pending')->count();
        }

        return Inertia::render('Hendhys/Dashboard', [
            'stats'              => $stats,
            'recentTransactions' => $recent,
            'lowStocks'          => $lowStocksData,
        ]);
    }
}
