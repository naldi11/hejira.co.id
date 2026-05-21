<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Gudang\Product;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\TransferRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProduk = Product::where('status', 'active')->count();
        $pendingPo = PurchaseOrder::where('status', 'pending')->count();
        $totalCabang = Branch::where('is_active', true)->count();
        $pendingRequest = TransferRequest::where('status', 'pending')->count();

        // Get 5 latest POs
        $recentPos = PurchaseOrder::with('supplier')->latest()->take(5)->get();
        // Get 5 latest Transfer Requests
        $recentRequests = TransferRequest::with(['branch'])->latest()->take(5)->get();

        return view('gudang.dashboard', compact(
            'totalProduk',
            'pendingPo',
            'totalCabang',
            'pendingRequest',
            'recentPos',
            'recentRequests'
        ));
    }
}
