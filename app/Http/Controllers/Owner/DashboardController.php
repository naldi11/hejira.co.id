<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\HendhysTransaction;
use App\Models\JihansTransaction;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(today()->subDays($i)->format('Y-m-d'));
        }

        $jihansSales = JihansTransaction::where('status', 'paid')
            ->whereDate('date', '>=', today()->subDays(6))
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $hendhysSales = HendhysTransaction::where('status', 'paid')
            ->whereDate('date', '>=', today()->subDays(6))
            ->groupBy('date')
            ->selectRaw('date, SUM(grand_total) as total')
            ->pluck('total', 'date');

        $trends = $days->map(fn($d) => [
            'date' => Carbon::parse($d)->format('d M'),
            'jihans' => (float) ($jihansSales[$d] ?? 0),
            'hendhys' => (float) ($hendhysSales[$d] ?? 0),
            'total' => (float) (($jihansSales[$d] ?? 0) + ($hendhysSales[$d] ?? 0)),
        ]);

        return Inertia::render('Owner/Dashboard', [
            'stats' => [
                'jihans_revenue'     => (float) JihansTransaction::where('status', 'paid')->sum('grand_total'),
                'hendhys_revenue'    => (float) HendhysTransaction::where('status', 'paid')->sum('grand_total'),
                'total_items_gudang' => (float) GudangStock::sum('quantity'),
                'jihans_today'       => (float) JihansTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
                'hendhys_today'      => (float) HendhysTransaction::where('status', 'paid')->whereDate('date', today())->sum('grand_total'),
            ],
            'trends' => $trends,
        ]);
    }
}
