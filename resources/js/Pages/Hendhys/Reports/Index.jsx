import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
const route = window.route;
const REPORTS = [
    { route: 'hendhys.reports.laci', label: 'Laci Kasir', desc: 'Rekap penjualan per hari untuk kasir yang sedang login', icon: 'point_of_sale', color: 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400' },
    { route: 'hendhys.reports.harian', label: 'Laporan Harian', desc: 'Detail transaksi per pelanggan', icon: 'receipt_long', color: 'bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
    { route: 'hendhys.reports.mingguan', label: 'Laporan Mingguan', desc: 'Rekap penjualan per minggu', icon: 'date_range', color: 'bg-purple-100 text-purple-600' },
    { route: 'hendhys.reports.bulanan', label: 'Laporan Bulanan', desc: 'Rekap penjualan per bulan', icon: 'calendar_month', color: 'bg-amber-100 text-amber-600 dark:text-amber-400' },
    { route: 'hendhys.reports.pelanggan', label: 'Statistik Pelanggan', desc: 'Rekap total belanja per pelanggan', icon: 'group', color: 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400' },
];
export default function ReportsIndex() {
    return (
        <HendhysLayout pageTitle="Laporan Hendhys">
            <Head title="Laporan" />
            <div className="space-y-6">
                <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Pilih Jenis Laporan</h2>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">{REPORTS.map((r) => (<Link key={r.route} href={route(r.route)} className="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs transition-all hover:border-amber-200 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]"><div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-full ${r.color}`}><Icon name={r.icon} className="text-[24px]" /></div><div><h3 className="font-bold text-gray-800 group-hover:text-amber-600 dark:text-white/90">{r.label}</h3><p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{r.desc}</p></div></Link>))}</div>
            </div>
        </HendhysLayout>
    );
}
