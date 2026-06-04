<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\TransferRequest;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Gudang/Dashboard', [
            'stats' => [
                'total_produk'    => Product::where('status', 'active')->count(),
                'pending_po'      => PurchaseOrder::where('status', 'pending')->count(),
                'pending_request' => TransferRequest::where('status', 'pending')->count(),
                'total_cabang'    => Branch::where('is_active', true)->count(),
            ],
        ]);
    }
}
