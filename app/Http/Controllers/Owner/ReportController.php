<?php

namespace App\Http\Controllers\Owner;

use App\Exports\Owner\RevenueReportExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$unit, $period, $dateFrom, $dateTo] = $this->reportFilters($request);

        $data = $this->getReportData($unit, $period, $dateFrom, $dateTo);

        return Inertia::render('Owner/Reports', [
            'reportData' => $data,
            'filters' => [
                'unit_bisnis' => $unit,
                'periode' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $request->validate(['format' => ['sometimes', 'required', Rule::in(['csv', 'pdf', 'xlsx'])]]);
        $format = $request->input('format', 'csv');
        [$unit, $period, $dateFrom, $dateTo] = $this->reportFilters($request);

        $data = $this->getReportData($unit, $period, $dateFrom, $dateTo);

        if ($format === 'xlsx') {
            return Excel::download(
                new RevenueReportExport($data, $unit, $period, $dateFrom, $dateTo),
                'Laporan_Omset_'.$period.'_'.now('Asia/Jakarta')->format('Ymd_His').'.xlsx',
            );
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('owner.reports_pdf', compact('data', 'unit', 'period', 'dateFrom', 'dateTo'));

            return $pdf->stream('Laporan_Omset_'.$period.'.pdf');
        }

        // CSV export
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=Laporan_Omset_'.$period.'_'.date('Ymd').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Periode', 'Jihans Food', 'Hendhys Brownies', 'Izzi Kebab (bersih)', 'Total Omset'], escape: '');

            foreach ($data as $row) {
                fputcsv($file, [$row['label'], $row['jihans'], $row['hendhys'], $row['izzi'], $row['total']], escape: '');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function reportFilters(Request $request): array
    {
        $request->validate([
            'unit_bisnis' => ['sometimes', 'required', Rule::in(['all', 'jihans', 'hendhys', 'izzi'])],
            'periode' => ['sometimes', 'required', Rule::in(['harian', 'mingguan', 'bulanan', '3_bulanan', '6_bulanan', 'tahunan', 'keseluruhan'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);

        return [
            $request->input('unit_bisnis', 'all'),
            $request->input('periode', 'bulanan'),
            $request->input('date_from'),
            $request->input('date_to'),
        ];
    }

    private function getReportData($unit, $period, $dateFrom, $dateTo)
    {
        $jihans = collect();
        $hendhys = collect();
        $izzi = collect();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $groupSql = match ($period) {
            'harian' => 'date as label',
            'mingguan' => $isSqlite ? 'strftime("%Y-%W", date) as label' : 'YEARWEEK(date, 1) as label',
            'bulanan' => $isSqlite ? 'strftime("%Y-%m", date) as label' : 'DATE_FORMAT(date, "%Y-%m") as label',
            '3_bulanan' => $isSqlite ? 'strftime("%Y", date) || "-Q" || ((cast(strftime("%m", date) as integer) - 1) / 3 + 1) as label' : 'CONCAT(YEAR(date), "-Q", QUARTER(date)) as label',
            '6_bulanan' => $isSqlite ? 'strftime("%Y", date) || "-" || (CASE WHEN cast(strftime("%m", date) as integer) <= 6 THEN "H1" ELSE "H2" END) as label' : 'CONCAT(YEAR(date), "-", CASE WHEN MONTH(date) <= 6 THEN "H1" ELSE "H2" END) as label',
            'tahunan' => $isSqlite ? 'strftime("%Y", date) as label' : 'YEAR(date) as label',
            default => '"Keseluruhan" as label',
        };

        if ($unit === 'all' || $unit === 'jihans') {
            $query = DB::table('jihans_transactions')
                ->where('status', 'paid')
                ->when($dateFrom, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('date', '<=', $dateTo))
                ->selectRaw($groupSql.', SUM(grand_total) as amount');
            if ($period !== 'keseluruhan') {
                $query->groupBy('label');
            }
            $jihans = $query->pluck('amount', 'label');
        }

        if ($unit === 'all' || $unit === 'hendhys') {
            $query = DB::table('hendhys_transactions')
                ->where('status', 'paid')
                ->when($dateFrom, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('date', '<=', $dateTo))
                ->selectRaw($groupSql.', SUM(grand_total) as amount');
            if ($period !== 'keseluruhan') {
                $query->groupBy('label');
            }
            $hendhys = $query->pluck('amount', 'label');
        }

        if ($unit === 'all' || $unit === 'izzi') {
            $query = DB::query()->fromSub(DB::table('izzi_sales')
                ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom.' 00:00:00'))
                ->when($dateTo, fn ($q) => $q->where('created_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay()))
                ->select('net_total', DB::raw('DATE(created_at) as date')), 'sales')
                ->selectRaw($groupSql.', SUM(net_total) as amount');
            if ($period !== 'keseluruhan') {
                $query->groupBy('label');
            }
            $izzi = $query->pluck('amount', 'label');
        }
        $allLabels = $jihans->keys()->concat($hendhys->keys())->concat($izzi->keys())->unique()->sortDesc();

        return $allLabels->map(fn ($label) => [
            'label' => $label,
            'jihans' => (float) ($jihans[$label] ?? 0),
            'hendhys' => (float) ($hendhys[$label] ?? 0),
            'izzi' => (float) ($izzi[$label] ?? 0),
            'total' => (float) (($jihans[$label] ?? 0) + ($hendhys[$label] ?? 0) + ($izzi[$label] ?? 0)),
        ])->values()->all();
    }
}
