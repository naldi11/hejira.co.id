<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\HendhysTransaction;
use App\Models\JihansTransaction;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Owner/Dashboard', [
            'stats' => [
                'jihans_revenue'     => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
                'hendhys_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'total_items_gudang' => (float) GudangStock::sum('quantity'),
                'jihans_today'       => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'hendhys_today'      => (float) HendhysTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
            ],
        ]);
    }
}
