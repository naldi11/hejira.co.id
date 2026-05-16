<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use Illuminate\Http\Request;

class GudangDashboardController extends Controller
{
    public function index()
    {
        // Statistik Purchase Order
        $poPending = PurchaseOrder::where('status', 'draft')->orWhere('status', 'sent')->count();
        $poReceived = PurchaseOrder::where('status', 'received')->count();

        // Total Penerimaan Barang Bulan Ini
        $receiveThisMonth = Receiving::whereMonth('date', today()->month)->count();

        // Total Distribusi / Transfer Keluar Bulan Ini
        $transferOutThisMonth = TransferOut::whereMonth('date', today()->month)->count();

        // Transfer Request yang butuh Approval
        $pendingRequests = TransferRequest::where('status', 'pending')->count();

        // Top 5 Stok Terbanyak
        $topStocks = GudangStock::with('product')
            ->orderByDesc('quantity')
            ->take(5)
            ->get();

        // 5 Stok Terendah
        $lowStocks = GudangStock::with('product')
            ->orderBy('quantity')
            ->take(5)
            ->get();

        return view('owner.gudang', compact(
            'poPending',
            'poReceived',
            'receiveThisMonth',
            'transferOutThisMonth',
            'pendingRequests',
            'topStocks',
            'lowStocks'
        ));
    }
}
