<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JihansProduction;
use App\Models\JihansTransaction;
use App\Models\JihansTransactionDetail;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JihansDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Owner/Jihans', [
            'stats' => [
                'total_revenue'    => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
                'revenue_today'    => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'production_today' => JihansProduction::whereDate('date', today())->count(),
            ],
            'recentTransactions' => JihansTransaction::with('creator')->latest('id')->take(5)->get()
                ->map(fn ($t) => [
                    'id'                 => $t->id,
                    'transaction_number' => $t->transaction_number,
                    'customer_name'      => $t->customer_name,
                    'grand_total'        => (float) $t->grand_total,
                    'date'               => $t->date,
                ]),
            'topProducts' => JihansTransactionDetail::join('master_products', 'jihans_transaction_details.product_id', '=', 'master_products.id')
                ->select('master_products.name', DB::raw('SUM(jihans_transaction_details.quantity) as total_sold'))
                ->groupBy('jihans_transaction_details.product_id', 'master_products.name')
                ->orderByDesc('total_sold')->take(5)->get()
                ->map(fn ($p) => ['name' => $p->name, 'total_sold' => (float) $p->total_sold]),
        ]);
    }
}
