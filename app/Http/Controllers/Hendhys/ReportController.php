<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\CashierShift;

class ReportController extends Controller
{
    private function buildSummaryQuery(Request $request, ?int $kasirId = null)
    {
        $user    = auth()->user();
        $isPusat = optional($user->branch)->type === 'pusat';

        $payAgg = DB::table('hendhys_transaction_payments as p')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->selectRaw("
                p.transaction_id,
                SUM(CASE
                    WHEN pm.type = 'tunai' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_type = 'tunai' THEN p.amount
                    ELSE 0 END) as tunai,
                SUM(CASE
                    WHEN pm.type = 'transfer' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_type = 'transfer' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_method = 'transfer' AND p.payment_type IS NULL THEN p.amount
                    ELSE 0 END) as transfer,
                SUM(CASE
                    WHEN pm.type = 'kartu_debit' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_type = 'kartu_debit' THEN p.amount
                    ELSE 0 END) as kartu_debit,
                SUM(CASE
                    WHEN pm.type = 'kartu_kredit' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_type = 'kartu_kredit' THEN p.amount
                    ELSE 0 END) as kartu_kredit
            ")
            ->groupBy('p.transaction_id');

        return DB::table('hendhys_transactions as t')
            ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
            ->where('t.status', '!=', 'cancelled')
            ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
            ->when($kasirId, fn($q) => $q->where('t.created_by', $kasirId))
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to));
    }

    public function index()
    {
        return Inertia::render('Hendhys/Reports/Index');
    }

    public function laci(Request $request)
    {
        $user = auth()->user();
        $isPusatOrAdmin = $user->hasRole('admin_hendhys') || $user->hasRole('super_admin_hendhys');

        $query = CashierShift::with('user')
            ->where('entity', 'hendhys')
            ->when(!$isPusatOrAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($user->branch && $user->branch->type !== 'pusat', fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->whereDate('opened_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('opened_at', '<=', $request->date_to));

        $rows = $query->orderBy('opened_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        foreach ($rows as $row) {
            $summary = $row->calculatePaymentSummary();
            $row->payment_summary = $summary;
            if ($row->status === 'open') {
                $row->expected_cash = $row->calculateExpectedCashSoFar();
            }
        }

        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            $activeShift->expected_cash = $activeShift->calculateExpectedCashSoFar();
            $activeShift->payment_summary = $activeShift->calculatePaymentSummary();
        }

        return Inertia::render('Hendhys/Reports/Laci', [
            'rows'    => $rows,
            'filters' => $request->only('date_from', 'date_to'),
            'activeShift' => $activeShift,
        ]);
    }

    public function harian(Request $request)
    {
        $user    = auth()->user();
        $isPusat = optional($user->branch)->type === 'pusat';

        $rows = DB::table('hendhys_transactions as t')
            ->leftJoin('master_users as u', 'u.id', '=', 't.created_by')
            ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.status', '!=', 'cancelled')
            ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where(function($qi) use ($request) {
                $qi->where('c.name', 'like', '%'.$request->search.'%')
                   ->orWhere('t.customer_name', 'like', '%'.$request->search.'%')
                   ->orWhere('t.transaction_number', 'like', '%'.$request->search.'%');
            }))
            ->select([
                't.id',
                't.transaction_number',
                't.date',
                'u.name as operator',
                DB::raw("COALESCE(c.code, 'UMUM') as customer_code"),
                DB::raw("COALESCE(c.name, t.customer_name, 'Pelanggan Umum') as customer_name"),
                DB::raw("COALESCE(c.address, 'Umum') as customer_address"),
                't.grand_total',
                't.discount_amount as discount_total',
                't.tax_amount as tax_total'
            ])
            ->orderBy('t.date', 'desc')
            ->orderBy('t.id', 'desc')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Hendhys/Reports/Harian', [
            'rows'    => $rows,
            'filters' => $request->only('search', 'date_from', 'date_to'),
        ]);
    }

    public function mingguan(Request $request)
    {
        $rows = $this->buildSummaryQuery($request)
            ->selectRaw("
                YEARWEEK(t.date, 1)                                                       as tahun_minggu,
                MIN(t.date)                                                                as minggu_mulai,
                MAX(t.date)                                                                as minggu_akhir,
                COUNT(*)                                                                   as jumlah_transaksi,
                SUM(t.grand_total)                                                         as total_transaksi,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                COALESCE(SUM(pay_agg.tunai), 0)                                            as tunai,
                COALESCE(SUM(pay_agg.kartu_debit), 0)                                     as kartu_debit,
                COALESCE(SUM(pay_agg.kartu_kredit), 0)                                    as kartu_kredit
            ")
            ->groupByRaw('YEARWEEK(t.date, 1)')
            ->orderByRaw('YEARWEEK(t.date, 1) DESC')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Hendhys/Reports/Mingguan', [
            'rows'    => $rows,
            'filters' => $request->only('date_from', 'date_to'),
        ]);
    }

    public function bulanan(Request $request)
    {
        $rows = $this->buildSummaryQuery($request)
            ->selectRaw("
                DATE_FORMAT(t.date, '%Y-%m')                                              as tahun_bulan,
                DATE_FORMAT(t.date, '%M %Y')                                              as label_bulan,
                COUNT(*)                                                                   as jumlah_transaksi,
                SUM(t.grand_total)                                                         as total_transaksi,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                COALESCE(SUM(pay_agg.tunai), 0)                                            as tunai,
                COALESCE(SUM(pay_agg.kartu_debit), 0)                                     as kartu_debit,
                COALESCE(SUM(pay_agg.kartu_kredit), 0)                                    as kartu_kredit
            ")
            ->groupByRaw("DATE_FORMAT(t.date, '%Y-%m'), DATE_FORMAT(t.date, '%M %Y')")
            ->orderByRaw("DATE_FORMAT(t.date, '%Y-%m') DESC")
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Hendhys/Reports/Bulanan', [
            'rows'    => $rows,
            'filters' => $request->only('date_from', 'date_to'),
        ]);
    }

    public function pelanggan(Request $request)
    {
        $user    = auth()->user();
        $isPusat = optional($user->branch)->type === 'pusat';

        $payAgg = DB::table('hendhys_transaction_payments as p')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->selectRaw("
                p.transaction_id,
                SUM(CASE
                    WHEN pm.type = 'tunai' THEN p.amount
                    WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN p.amount
                    ELSE 0 END) as tunai,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END) as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit
            ")
            ->groupBy('p.transaction_id');

        $rows = DB::table('hendhys_transactions as t')
            ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
            ->where('t.status', '!=', 'cancelled')
            ->whereNotNull('t.customer_name')
            ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->where('t.customer_name', 'like', '%'.$request->search.'%'))
            ->selectRaw("
                t.customer_name                                                            as pelanggan,
                MIN(t.date)                                                                as tanggal_pertama,
                MAX(t.date)                                                                as tanggal_terakhir,
                COUNT(*)                                                                   as jumlah_transaksi,
                SUM(t.grand_total)                                                         as total_transaksi,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                COALESCE(SUM(pay_agg.tunai), 0)                                            as tunai,
                COALESCE(SUM(pay_agg.kartu_debit), 0)                                     as kartu_debit,
                COALESCE(SUM(pay_agg.kartu_kredit), 0)                                    as kartu_kredit
            ")
            ->groupBy('t.customer_name')
            ->orderBy('total_transaksi', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Hendhys/Reports/Pelanggan', [
            'rows'    => $rows,
            'filters' => $request->only('search', 'date_from', 'date_to'),
        ]);
    }

    public function pdf(Request $request, $type)
    {
        $user = auth()->user();
        if ($user->hasRole('kasir_hendhys') && $type !== 'laci') {
            abort(403, 'Akses ditolak.');
        }

        $branch  = $user->branch;
        $title   = "Laporan " . ucfirst($type);
        $rows    = collect();
        $isDetailed = ($type === 'harian');

        if ($isDetailed) {
            $title = "LHI DETAIL";
            $isPusat = optional($user->branch)->type === 'pusat';

            $transactions = DB::table('hendhys_transactions as t')
                ->leftJoin('master_users as u', 'u.id', '=', 't.created_by')
                ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
                ->where('t.status', '!=', 'cancelled')
                ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
                ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
                ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
                ->select([
                    't.id',
                    't.transaction_number',
                    't.date',
                    'u.name as operator',
                    DB::raw("COALESCE(c.code, 'UMUM') as customer_code"),
                    DB::raw("COALESCE(c.name, t.customer_name, 'Pelanggan Umum') as customer_name"),
                    DB::raw("COALESCE(c.address, 'Umum') as customer_address"),
                    't.grand_total',
                    't.discount_amount as discount_total',
                    't.tax_amount as tax_total'
                ])
                ->orderBy('t.date', 'desc')
                ->orderBy('t.id', 'desc')
                ->get();

            $rows = $transactions->map(function($tx) {
                $tx->details = DB::table('hendhys_transaction_details as d')
                    ->join('master_products as p', 'p.id', '=', 'd.product_id')
                    ->join('master_units as u', 'u.id', '=', 'd.unit_id')
                    ->where('d.transaction_id', $tx->id)
                    ->select([
                        'p.code as kode_item',
                        'd.product_name as nama_item',
                        'd.quantity',
                        'u.abbreviation as satuan',
                        'd.price',
                        'd.discount_amount as pot',
                        'd.total'
                    ])
                    ->get();
                return $tx;
            });
        } else {
            $query = null;
            if ($type === 'laci') {
                $title = "Laporan Sesi Laci Kasir";
                $query = CashierShift::with('user')
                    ->where('entity', 'hendhys')
                    ->when($user->hasRole('kasir_hendhys'), fn($q) => $q->where('user_id', $user->id))
                    ->when($user->branch && $user->branch->type !== 'pusat', fn($q) => $q->where('branch_id', $user->branch_id))
                    ->when($request->shift_id, fn($q) => $q->where('id', $request->shift_id))
                    ->when(!$request->shift_id && $request->date_from, fn($q) => $q->whereDate('opened_at', '>=', $request->date_from))
                    ->when(!$request->shift_id && $request->date_to, fn($q) => $q->whereDate('opened_at', '<=', $request->date_to))
                    ->orderBy('opened_at', 'desc');
            } elseif ($type === 'mingguan') {
                $title = "Laporan Penjualan Mingguan";
                $query = $this->buildSummaryQuery($request)
                    ->selectRaw("
                        YEARWEEK(t.date, 1) as tahun_minggu,
                        MIN(t.date) as minggu_mulai,
                        MAX(t.date) as minggu_akhir,
                        COUNT(*) as jumlah_transaksi,
                        SUM(t.grand_total) as total_transaksi,
                        COALESCE(SUM(pay_agg.tunai), 0) as tunai,
                        COALESCE(SUM(pay_agg.kartu_debit), 0) as kartu_debit,
                        COALESCE(SUM(pay_agg.kartu_kredit), 0) as kartu_kredit,
                        SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END) as kredit
                    ")
                    ->groupByRaw('YEARWEEK(t.date, 1)')
                    ->orderByRaw('YEARWEEK(t.date, 1) DESC');
            } elseif ($type === 'bulanan') {
                $title = "Laporan Penjualan Bulanan";
                $query = $this->buildSummaryQuery($request)
                    ->selectRaw("
                        DATE_FORMAT(t.date, '%Y-%m') as tahun_bulan,
                        DATE_FORMAT(t.date, '%M %Y') as label_bulan,
                        COUNT(*) as jumlah_transaksi,
                        SUM(t.grand_total) as total_transaksi,
                        COALESCE(SUM(pay_agg.tunai), 0) as tunai,
                        COALESCE(SUM(pay_agg.kartu_debit), 0) as kartu_debit,
                        COALESCE(SUM(pay_agg.kartu_kredit), 0) as kartu_kredit,
                        SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END) as kredit
                    ")
                    ->groupByRaw("DATE_FORMAT(t.date, '%Y-%m'), DATE_FORMAT(t.date, '%M %Y')")
                    ->orderByRaw("DATE_FORMAT(t.date, '%Y-%m') DESC");
            } elseif ($type === 'pelanggan') {
                $title = "Laporan Statistik Pelanggan";
                
                $isPusat = optional($user->branch)->type === 'pusat';
                $payAgg = DB::table('hendhys_transaction_payments as p')
                    ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                    ->selectRaw("
                        p.transaction_id,
                        SUM(CASE
                            WHEN pm.type = 'tunai' THEN p.amount
                            WHEN p.payment_method_id IS NULL AND p.payment_method IN ('cash','tunai') THEN p.amount
                            ELSE 0 END) as tunai,
                        SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END) as kartu_debit,
                        SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit
                    ")
                    ->groupBy('p.transaction_id');

                $query = DB::table('hendhys_transactions as t')
                    ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
                    ->where('t.status', '!=', 'cancelled')
                    ->whereNotNull('t.customer_name')
                    ->when(!$isPusat, fn($q) => $q->where('t.branch_id', $user->branch_id))
                    ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
                    ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
                    ->when($request->search, fn($q) => $q->where('t.customer_name', 'like', '%'.$request->search.'%'))
                    ->selectRaw("
                        t.customer_name as pelanggan,
                        MIN(t.date) as tanggal_pertama,
                        MAX(t.date) as tanggal_terakhir,
                        COUNT(*) as jumlah_transaksi,
                        SUM(t.grand_total) as total_transaksi,
                        COALESCE(SUM(pay_agg.tunai), 0) as tunai,
                        COALESCE(SUM(pay_agg.kartu_debit), 0) as kartu_debit,
                        COALESCE(SUM(pay_agg.kartu_kredit), 0) as kartu_kredit,
                        SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END) as kredit
                    ")
                    ->groupBy('t.customer_name')
                    ->orderBy('total_transaksi', 'desc');
            }

            if (!$query) abort(404);
            $rows = $query->get();
            
            if ($type === 'laci') {
                foreach ($rows as $row) {
                    $row->sales_summary = $row->calculateSalesSummary();
                    $row->payment_summary = $row->calculatePaymentSummary();
                }
            }
        }

        $viewName = ($type === 'harian') ? 'hendhys.reports.harian_pdf' : 'hendhys.reports.pdf';
        $paperSize = ($type === 'pelanggan') ? 'legal' : [0, 0, 792, 684];
        $orientation = ($type === 'pelanggan') ? 'portrait' : 'landscape';
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, compact('rows', 'type', 'title', 'request', 'branch', 'isDetailed', 'orientation'))
                ->setPaper($paperSize, $orientation);
        $pdf->getDomPDF()->set_option("enable_php", true);

        return $pdf->stream($title . '.pdf');
    }
}
