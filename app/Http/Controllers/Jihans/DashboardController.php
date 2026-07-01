<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansPendingTransaction;
use App\Models\JihansProduction;
use App\Models\JihansTransaction;
use App\Models\Product;
use App\Models\TransferRequest;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $recent = JihansTransaction::with('creator')->latest('id')->take(5)->get()
            ->map(fn ($t) => [
                'id'                 => $t->id,
                'transaction_number' => $t->transaction_number,
                'date'               => $t->date,
                'customer_name'      => $t->customer_name,
                'grand_total'        => (float) $t->grand_total,
            ]);

        $lowStocks = Product::where('status', 'active')
            ->whereIn('master_products.entity_scope', ['jihans', 'all'])
            ->join('jihans_retail_stock', 'master_products.id', '=', 'jihans_retail_stock.product_id')
            ->where('jihans_retail_stock.quantity', '<=', 50)
            ->select('master_products.*', 'jihans_retail_stock.quantity as current_stock')
            ->take(5)->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'name'          => $s->name,
                'code'          => $s->code,
                'jenis'         => $s->jenis,
                'current_stock' => (float) $s->current_stock,
            ]);

        return Inertia::render('Jihans/Dashboard', [
            'stats' => [
                'produksi_hari_ini' => JihansProduction::whereDate('date', now())->count(),
                'omset_hari_ini'    => (float) JihansTransaction::whereDate('date', now())->where('status', 'paid')->sum('grand_total'),
                'pending_count'     => JihansPendingTransaction::count(),
                'request_pending'   => TransferRequest::where('from_entity', 'jihans')->where('status', 'pending')->count(),
            ],
            'recentTransactions' => $recent,
            'lowStocks'          => $lowStocks,
        ]);
    }
}
