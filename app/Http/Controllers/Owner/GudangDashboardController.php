<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use Inertia\Inertia;

class GudangDashboardController extends Controller
{
    public function index()
    {
        $mapStock = fn ($s) => ['product' => $s->product?->name ?? '-', 'quantity' => (float) $s->quantity];

        return Inertia::render('Owner/Gudang', [
            'stats' => [
                'po_pending'        => PurchaseOrder::whereIn('status', ['draft', 'sent'])->count(),
                'po_received'       => PurchaseOrder::where('status', 'received')->count(),
                'receive_month'     => Receiving::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
                'transfer_month'    => TransferOut::whereMonth('date', today()->month)->whereYear('date', today()->year)->count(),
                'pending_requests'  => TransferRequest::where('status', 'pending')->count(),
            ],
            'topStocks' => GudangStock::with('product')->orderByDesc('quantity')->take(5)->get()->map($mapStock),
            'lowStocks' => GudangStock::with('product')->orderBy('quantity')->take(5)->get()->map($mapStock),
        ]);
    }
}
