<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END) as tunai,
                SUM(CASE WHEN pm.type = 'kartu_debit'  THEN p.amount ELSE 0 END) as kartu_debit,
                SUM(CASE WHEN pm.type = 'kartu_kredit' THEN p.amount ELSE 0 END) as kartu_kredit
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
        return view('hendhys.reports.index');
    }

    public function laci(Request $request)
    {
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

        return view('hendhys.reports.laci', compact('rows'));
    }

    public function harian(Request $request)
    {
        $rows = $this->buildSummaryQuery($request)
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

        return view('hendhys.reports.harian', compact('rows'));
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

        return view('hendhys.reports.mingguan', compact('rows'));
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

        return view('hendhys.reports.bulanan', compact('rows'));
    }

    public function pelanggan(Request $request)
    {
        $user    = auth()->user();
        $isPusat = optional($user->branch)->type === 'pusat';

        $payAgg = DB::table('hendhys_transaction_payments as p')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->selectRaw("
                p.transaction_id,
                SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END) as tunai,
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

        return view('hendhys.reports.pelanggan', compact('rows'));
    }

    public function pdf(Request $request, $type)
    {
        $user    = auth()->user();
        $branch  = $user->branch;
        $title   = "Laporan " . ucfirst($type);
        $query   = null;

        if ($type === 'laci') {
            $title = "Laporan Laci Kasir: " . $user->name;
            $query = $this->buildSummaryQuery($request, $user->id)
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
        } elseif ($type === 'harian') {
            $title = "Laporan Penjualan Harian";
            $query = $this->buildSummaryQuery($request)
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
                ->orderBy('t.date', 'desc');
        } elseif ($type === 'mingguan') {
            $title = "Laporan Penjualan Mingguan";
            $query = $this->buildSummaryQuery($request)
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
                ->orderByRaw('YEARWEEK(t.date, 1) DESC');
        } elseif ($type === 'bulanan') {
            $title = "Laporan Penjualan Bulanan";
            $query = $this->buildSummaryQuery($request)
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
                ->orderByRaw("DATE_FORMAT(t.date, '%Y-%m') DESC");
        } elseif ($type === 'pelanggan') {
            $title = "Laporan Statistik Pelanggan";
            
            $isPusat = optional($user->branch)->type === 'pusat';
            $payAgg = DB::table('hendhys_transaction_payments as p')
                ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                ->selectRaw("
                    p.transaction_id,
                    SUM(CASE WHEN pm.type = 'tunai'        THEN p.amount ELSE 0 END) as tunai,
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
                ->orderBy('total_transaksi', 'desc');
        }

        if (!$query) abort(404);

        $rows = $query->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('hendhys.reports.pdf', compact('rows', 'type', 'title', 'request', 'branch'))
                ->setPaper('a4', 'landscape');

        return $pdf->stream($title . '.pdf');
    }
}
