<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HendhysProduction;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HendhysDashboardController extends Controller
{
    public function index()
    {
        $branchNames = Branch::pluck('name', 'id');

        return Inertia::render('Owner/Hendhys', [
            'stats' => [
                'total_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'production_today' => HendhysProduction::whereDate('date', today())->count(),
            ],
            'revenueByBranch' => DB::table('hendhys_transactions')->where('status', 'paid')
                ->select('branch_id', DB::raw('SUM(grand_total) as total'))->groupBy('branch_id')->get()
                ->map(fn ($r) => [
                    'branch' => $r->branch_id ? ($branchNames[$r->branch_id] ?? 'Cabang') : 'Pusat',
                    'total'  => (float) $r->total,
                ]),
            'recentTransactions' => HendhysTransaction::with(['creator', 'branch'])->latest('id')->take(5)->get()
                ->map(fn ($t) => [
                    'id'                 => $t->id,
                    'transaction_number' => $t->transaction_number,
                    'customer_name'      => $t->customer_name,
                    'grand_total'        => (float) $t->grand_total,
                    'branch'             => $t->branch?->name ?? 'Pusat',
                ]),
            'topProducts' => HendhysTransactionDetail::join('master_products', 'hendhys_transaction_details.product_id', '=', 'master_products.id')
                ->select('master_products.name', DB::raw('SUM(hendhys_transaction_details.quantity) as total_sold'))
                ->groupBy('hendhys_transaction_details.product_id', 'master_products.name')
                ->orderByDesc('total_sold')->take(5)->get()
                ->map(fn ($p) => ['name' => $p->name, 'total_sold' => (float) $p->total_sold]),
        ]);
    }
}
