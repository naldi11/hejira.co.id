<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JihansProduction;
use App\Models\JihansTransaction;
use App\Models\JihansTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JihansDashboardController extends Controller
{
    public function index()
    {
        // Pendapatan Jihans
        $totalRevenue = JihansTransaction::where('status', 'paid')->sum('grand_total');
        $revenueToday = JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total');

        // Produksi Tortilla Hari Ini
        $totalProductionToday = JihansProduction::whereDate('date', today())->count();

        // 5 Transaksi Terakhir
        $recentTransactions = JihansTransaction::with('creator')
            ->latest('id')
            ->take(5)
            ->get();

        // Produk Terlaris (Top 5)
        $topProducts = JihansTransactionDetail::join('master_products', 'jihans_transaction_details.product_id', '=', 'master_products.id')
            ->select('master_products.name', DB::raw('SUM(jihans_transaction_details.quantity) as total_sold'))
            ->groupBy('jihans_transaction_details.product_id', 'master_products.name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        return view('owner.jihans', compact(
            'totalRevenue',
            'revenueToday',
            'totalProductionToday',
            'recentTransactions',
            'topProducts'
        ));
    }
}
