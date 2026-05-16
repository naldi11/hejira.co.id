<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\HendhysTransaction;
use App\Models\JihansTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total Pendapatan Jihan's (Lunas)
        $jihansRevenue = JihansTransaction::where('status', 'paid')->sum('grand_total');

        // 2. Total Pendapatan Hendhys (Lunas)
        $hendhysRevenue = HendhysTransaction::where('status', 'paid')->sum('grand_total');

        // 3. Estimasi Nilai Aset Gudang (Qty * Harga Beli Terakhir / Rata-rata)
        // Jika tidak ada data harga beli di master product, asumsikan value dari price * qty (atau kita hitung sum quantity saja untuk kemudahan)
        $totalItemsInGudang = GudangStock::sum('quantity');

        // 4. Performa Penjualan Hari Ini
        $jihansToday = JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total');
        $hendhysToday = HendhysTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total');

        return view('owner.dashboard', compact(
            'jihansRevenue',
            'hendhysRevenue',
            'totalItemsInGudang',
            'jihansToday',
            'hendhysToday'
        ));
    }
}
