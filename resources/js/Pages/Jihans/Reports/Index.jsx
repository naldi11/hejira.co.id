import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
const route = window.route;
const REPORTS = [
    { route: 'jihans.reports.laci', label: 'Laci Kasir', desc: 'Rekap penjualan per hari untuk kasir yang sedang login', icon: 'point_of_sale', iconBg: 'bg-gradient-to-br from-green-400 to-emerald-600', color: 'text-white' },
    { route: 'jihans.reports.harian', label: 'Laporan Harian', desc: 'Detail transaksi per pelanggan', icon: 'receipt_long', iconBg: 'bg-gradient-to-br from-blue-400 to-indigo-600', color: 'text-white' },
    { route: 'jihans.reports.mingguan', label: 'Laporan Mingguan', desc: 'Rekap penjualan per minggu', icon: 'date_range', iconBg: 'bg-gradient-to-br from-purple-400 to-violet-600', color: 'text-white' },
    { route: 'jihans.reports.bulanan', label: 'Laporan Bulanan', desc: 'Rekap penjualan per bulan', icon: 'calendar_month', iconBg: 'bg-gradient-to-br from-amber-400 to-orange-500', color: 'text-white' },
    { route: 'jihans.reports.pelanggan', label: 'Statistik Pelanggan', desc: 'Rekap total belanja per pelanggan', icon: 'group', iconBg: 'bg-gradient-to-br from-rose-400 to-red-600', color: 'text-white' },
];
export default function ReportsIndex() {
    return (
        <JihansLayout pageTitle="Laporan Jihans">
            <Head title="Laporan" />
            <div className="space-y-6">
                <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Pilih Jenis Laporan</h2>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {REPORTS.map((r) => (
                        <Link
                            key={r.route}
                            href={route(r.route)}
                            className="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs transition-all hover:border-orange-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-orange-700"
                        >
                            <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${r.iconBg}`}>
                                <Icon name={r.icon} className={`text-[24px] ${r.color}`} />
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-gray-800 group-hover:text-orange-600 dark:text-white/90 dark:group-hover:text-orange-400">{r.label}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{r.desc}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </JihansLayout>
    );
}
