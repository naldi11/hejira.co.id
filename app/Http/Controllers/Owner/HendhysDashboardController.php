<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\HendhysProduction;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use App\Models\Master\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HendhysDashboardController extends Controller
{
    public function index()
    {
        // Pendapatan Hendhys Total
        $totalRevenue = HendhysTransaction::where('status', 'paid')->sum('grand_total');
        
        // Pendapatan Berdasarkan Cabang (Termasuk Pusat jika ada penjualan)
        $revenueByBranch = DB::table('hendhys_transactions')
            ->where('status', 'paid')
            ->select('branch_id', DB::raw('SUM(grand_total) as total'))
            ->groupBy('branch_id')
            ->get()
            ->map(function($item) {
                $branchName = $item->branch_id ? Branch::find($item->branch_id)->name : 'Pusat';
                return [
                    'branch' => $branchName,
                    'total' => $item->total
                ];
            });

        // Produksi Hendhys Hari Ini
        $totalProductionToday = HendhysProduction::whereDate('date', today())->count();

        // 5 Transaksi Terakhir
        $recentTransactions = HendhysTransaction::with(['creator', 'branch'])
            ->latest('id')
            ->take(5)
            ->get();

        // Produk Terlaris (Top 5)
        $topProducts = HendhysTransactionDetail::join('master_products', 'hendhys_transaction_details.product_id', '=', 'master_products.id')
            ->select('master_products.name', DB::raw('SUM(hendhys_transaction_details.quantity) as total_sold'))
            ->groupBy('hendhys_transaction_details.product_id', 'master_products.name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        return view('owner.hendhys', compact(
            'totalRevenue',
            'revenueByBranch',
            'totalProductionToday',
            'recentTransactions',
            'topProducts'
        ));
    }
}
