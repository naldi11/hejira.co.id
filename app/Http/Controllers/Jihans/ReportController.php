<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function buildSummaryQuery(Request $request, ?int $kasirId = null)
    {
        // Pre-aggregate payments per transaction to prevent row multiplication from LEFT JOIN
        $payAgg = DB::table('jihans_transaction_payments as p')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->selectRaw("
                p.transaction_id,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END) as tunai,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END) as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit
            ")
            ->groupBy('p.transaction_id');

        return DB::table('jihans_transactions as t')
            ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
            ->where('t.status', '!=', 'cancelled')
            ->when($kasirId, fn($q) => $q->where('t.created_by', $kasirId))
            ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to));
    }

    public function index()
    {
        return view('jihans.reports.index');
    }

    public function laci(Request $request)
    {
        // Laci kasir: hanya data kasir yang sedang login, group by date
        $rows = $this->buildSummaryQuery($request, auth()->id())
            ->selectRaw("
                t.date,
                COUNT(*)                                                                   as jumlah_transaksi,
                SUM(t.grand_total)                                                         as total_transaksi,
                SUM(CASE WHEN t.status = 'pending'     THEN t.grand_total ELSE 0 END)     as kredit,
                COALESCE(SUM(pay_agg.tunai), 0)                                            as tunai,
                COALESCE(SUM(pay_agg.kartu_debit), 0)                                     as kartu_debit,
                COALESCE(SUM(pay_agg.kartu_kredit), 0)                                    as kartu_kredit
            ")
            ->groupBy('t.date')
            ->orderBy('t.date', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('jihans.reports.laci', compact('rows'));
    }

    public function harian(Request $request)
    {
        // Laporan Perpelanggan Detail: list transaksi dengan detail pelanggan
        $rows = DB::table('jihans_transactions as t')
            ->leftJoin('master_users as u', 'u.id', '=', 't.created_by')
            ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.status', '!=', 'cancelled')
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

        return view('jihans.reports.harian', compact('rows'));
    }

    public function mingguan(Request $request)
    {
        // Mingguan: group by YEARWEEK
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

        return view('jihans.reports.mingguan', compact('rows'));
    }

    public function bulanan(Request $request)
    {
        // Bulanan: group by YEAR + MONTH
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

        return view('jihans.reports.bulanan', compact('rows'));
    }

    public function pelanggan(Request $request)
    {
        // Pre-aggregate payments per transaction
        $payAgg = DB::table('jihans_transaction_payments as p')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->selectRaw("
                p.transaction_id,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END) as tunai,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END) as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit
            ")
            ->groupBy('p.transaction_id');

        $rows = DB::table('jihans_transactions as t')
            ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
            ->where('t.status', '!=', 'cancelled')
            ->whereNotNull('t.customer_name')
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

        return view('jihans.reports.pelanggan', compact('rows'));
    }

    public function pdf(Request $request, $type)
    {
        $title = "Laporan " . ucfirst($type);
        $rows = collect();
        $isDetailed = ($type === 'harian');

        if ($isDetailed) {
            $title = "LHI DETAIL";

            $transactions = DB::table('jihans_transactions as t')
                ->leftJoin('master_users as u', 'u.id', '=', 't.created_by')
                ->leftJoin('master_customers as c', 'c.id', '=', 't.customer_id')
                ->where('t.status', '!=', 'cancelled')
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
                ->get();

            $rows = $transactions->map(function($tx) {
                $tx->details = DB::table('jihans_transaction_details as d')
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
            // Logic summary (laci, mingguan, bulanan, pelanggan)
            $query = null;
            if ($type === 'laci') {
                $title = "Laporan Laci Kasir: " . auth()->user()->name;
                $query = $this->buildSummaryQuery($request, auth()->id())
                    ->selectRaw("
                        t.date,
                        COUNT(*) as jumlah_transaksi,
                        SUM(t.grand_total) as total_transaksi,
                        COALESCE(SUM(pay_agg.tunai), 0) as tunai,
                        SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END) as kredit,
                        COALESCE(SUM(pay_agg.kartu_debit), 0) as kartu_debit,
                        COALESCE(SUM(pay_agg.kartu_kredit), 0) as kartu_kredit
                    ")
                    ->groupBy('t.date')
                    ->orderBy('t.date', 'desc');
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
                $payAgg = DB::table('jihans_transaction_payments as p')
                    ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                    ->selectRaw("p.transaction_id, SUM(CASE WHEN pm.type = 'tunai' THEN p.amount ELSE 0 END) as tunai, SUM(CASE WHEN pm.type = 'kartu_debit' THEN p.amount ELSE 0 END) as kartu_debit, SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit")
                    ->groupBy('p.transaction_id');
                $query = DB::table('jihans_transactions as t')
                    ->leftJoinSub($payAgg, 'pay_agg', 'pay_agg.transaction_id', '=', 't.id')
                    ->where('t.status', '!=', 'cancelled')->whereNotNull('t.customer_name')
                    ->when($request->date_from, fn($q) => $q->whereDate('t.date', '>=', $request->date_from))
                    ->when($request->date_to, fn($q) => $q->whereDate('t.date', '<=', $request->date_to))
                    ->when($request->search, fn($q) => $q->where('t.customer_name', 'like', '%'.$request->search.'%'))
                    ->selectRaw("t.customer_name as pelanggan, MIN(t.date) as tanggal_pertama, MAX(t.date) as tanggal_terakhir, COUNT(*) as jumlah_transaksi, SUM(t.grand_total) as total_transaksi, COALESCE(SUM(pay_agg.tunai), 0) as tunai, COALESCE(SUM(pay_agg.kartu_debit), 0) as kartu_debit, COALESCE(SUM(pay_agg.kartu_kredit), 0) as kartu_kredit, SUM(CASE WHEN t.status = 'pending' THEN t.grand_total ELSE 0 END) as kredit")
                    ->groupBy('t.customer_name')->orderBy('total_transaksi', 'desc');
            }
            if (!$query) abort(404);
            $rows = $query->get();
        }

        // Laporan harian kini menggunakan landscape A5 sesuai template baru
        $orientation = ($type === 'pelanggan') ? 'portrait' : 'landscape';

        $viewName = ($type === 'harian') ? 'jihans.reports.harian_pdf' : 'jihans.reports.pdf';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, compact('rows', 'type', 'title', 'request', 'isDetailed', 'orientation'))
                ->setPaper('a5', $orientation);
        $pdf->getDomPDF()->set_option("enable_php", true);

        return $pdf->stream($title . '.pdf');
    }
}
