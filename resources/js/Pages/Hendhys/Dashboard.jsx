import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';

const route = window.route;

const STAT_PUSAT = [
    { key: 'omset_hari_ini', label: 'Omset Hari Ini', icon: 'payments', color: 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400', money: true },
    { key: 'produksi_hari_ini', label: 'Produksi Hari Ini', icon: 'factory', color: 'bg-amber-100 text-amber-600 dark:text-amber-400', suffix: 'batch' },
    { key: 'pending_count', label: 'Transaksi Pending', icon: 'schedule', color: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400', suffix: 'hold' },
    { key: 'request_pending_cabang', label: 'Request dari Cabang', icon: 'move_to_inbox', color: 'bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
];

const STAT_CABANG = [
    { key: 'omset_hari_ini', label: 'Omset Hari Ini', icon: 'payments', color: 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400', money: true },
    { key: 'return_bulan_ini', label: 'Return Bulan Ini', icon: 'assignment_return', color: 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400' },
    { key: 'pending_count', label: 'Transaksi Pending', icon: 'schedule', color: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400', suffix: 'hold' },
    { key: 'request_pending', label: 'Request Pending', icon: 'sync_alt', color: 'bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
];

export default function HendhysDashboard({ stats, recentTransactions, lowStocks }) {
    const cards = stats.is_pusat ? STAT_PUSAT : STAT_CABANG;

    return (
        <HendhysLayout pageTitle={`Dashboard ${stats.is_pusat ? 'Pusat' : 'Cabang'}`}>
            <Head title="Dashboard" />

            <div className="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                {cards.map((c) => (
                    <div key={c.key} className="flex items-center gap-4 rounded-2xl border border-amber-100 bg-white p-6 shadow-theme-xs transition-shadow hover:shadow-md dark:border-amber-500/20 dark:bg-white/[0.03]">
                        <div className={`flex h-12 w-12 items-center justify-center rounded-full ${c.color}`}><Icon name={c.icon} className="text-[24px]" /></div>
                        <div>
                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{c.label}</p>
                            <p className={`font-bold text-gray-800 ${c.money ? 'text-xl' : 'text-2xl'}`}>
                                {c.money ? formatRupiah(stats[c.key]) : formatQty(stats[c.key])}
                                {c.suffix && <span className="text-sm font-normal text-gray-500 dark:text-gray-400"> {c.suffix}</span>}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-100 bg-amber-50/40 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Penjualan Terakhir</h3>
                        <Link href={route('hendhys.pos.index')} className="text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400">Ke Kasir →</Link>
                    </div>
                    {recentTransactions.length === 0 ? (
                        <div className="p-5 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi</div>
                    ) : (
                        <div className="flex flex-col">
                            <div className="divide-y divide-gray-100 dark:divide-gray-800">
                                {recentTransactions.map((trx) => (
                                    <div key={trx.id} className="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                        <div>
                                            <p className="font-medium text-gray-800 dark:text-white/90">{trx.transaction_number}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {formatDate(trx.date)} {trx.time} • {trx.customer_name}
                                                <br />
                                                Kasir: <span className="font-medium text-gray-700 dark:text-gray-300">{trx.cashier_name}</span>
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-bold text-gray-900 dark:text-white">{formatRupiah(trx.grand_total)}</p>
                                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase text-green-700 dark:bg-green-500/10 dark:text-green-400">Paid</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="border-t border-gray-100 p-3 text-center dark:border-gray-800">
                                <Link 
                                    href={route('hendhys.transactions.index', { date: new Date().toISOString().split('T')[0] })}
                                    className="text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                >
                                    Lihat Semua Transaksi Hari Ini →
                                </Link>
                            </div>
                        </div>
                    )}
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-100 bg-amber-50/40 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Stok Menipis</h3>
                        <Link href={route('hendhys.stock.index')} className="text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400">Lihat Stok →</Link>
                    </div>
                    {lowStocks.length === 0 ? (
                        <div className="p-5 text-center text-sm text-gray-500 dark:text-gray-400">Semua stok terpantau aman</div>
                    ) : (
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {lowStocks.map((st) => (
                                <div key={st.id} className="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-amber-100 bg-amber-50 dark:bg-amber-500/10 dark:border-amber-500/20"><Icon name="inventory_2" className="text-[20px] text-amber-500 dark:text-amber-400" /></div>
                                        <div>
                                            <p className="font-medium text-gray-800 dark:text-white/90">{st.name}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">{st.code}</p>
                                        </div>
                                    </div>
                                    <p className="text-lg font-bold text-red-600 dark:text-red-400">{formatQty(st.current_stock)}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </HendhysLayout>
    );
}
