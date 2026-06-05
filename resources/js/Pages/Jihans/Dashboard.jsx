import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';

const route = window.route;

const STAT_CARDS = [
    { key: 'produksi_hari_ini', label: 'Produksi Hari Ini', icon: 'factory', color: 'bg-orange-100 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400', suffix: 'batch' },
    { key: 'omset_hari_ini', label: 'Omset Hari Ini', icon: 'payments', color: 'bg-green-100 text-green-600 dark:bg-green-500/10 dark:text-green-400', money: true },
    { key: 'pending_count', label: 'Transaksi Pending', icon: 'schedule', color: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400', suffix: 'hold' },
    { key: 'request_pending', label: 'Request Pending', icon: 'sync_alt', color: 'bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
];

export default function JihansDashboard({ stats, recentTransactions, lowStocks }) {
    return (
        <JihansLayout pageTitle="Dashboard Jihan's Food">
            <Head title="Dashboard" />

            <div className="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                {STAT_CARDS.map((c) => (
                    <div key={c.key} className="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className={`flex h-12 w-12 items-center justify-center rounded-full ${c.color}`}><Icon name={c.icon} className="text-[24px]" /></div>
                        <div>
                            <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{c.label}</p>
                            <p className={`font-bold text-gray-800 dark:text-white/90 ${c.money ? 'text-xl' : 'text-2xl'}`}>
                                {c.money ? formatRupiah(stats[c.key]) : formatQty(stats[c.key])}
                                {c.suffix && <span className="text-sm font-normal text-gray-500 dark:text-gray-400"> {c.suffix}</span>}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Penjualan Terakhir</h3>
                        <Link href={route('jihans.pos.index')} className="text-sm font-medium text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">Ke Kasir →</Link>
                    </div>
                    {recentTransactions.length === 0 ? (
                        <div className="p-5 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi</div>
                    ) : (
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {recentTransactions.map((trx) => (
                                <div key={trx.id} className="flex items-center justify-between p-4 hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                    <div>
                                        <p className="font-medium text-gray-800 dark:text-white/90">{trx.transaction_number}</p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">{formatDate(trx.date)} • {trx.customer_name}</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-bold text-gray-900 dark:text-white/90">{formatRupiah(trx.grand_total)}</p>
                                        <span className="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase text-green-700 dark:bg-green-500/10 dark:text-green-400">Paid</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Stok Menipis (Butuh Request)</h3>
                        <Link href={route('jihans.stock.index')} className="text-sm font-medium text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">Lihat Stok →</Link>
                    </div>
                    {lowStocks.length === 0 ? (
                        <div className="p-5 text-center text-sm text-gray-500 dark:text-gray-400">Semua stok aman</div>
                    ) : (
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {lowStocks.map((st) => (
                                <div key={st.id} className="flex items-center justify-between p-4 hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-orange-100 bg-orange-50 dark:border-orange-500/20 dark:bg-orange-500/10"><Icon name="inventory_2" className="text-[20px] text-orange-500 dark:text-orange-400" /></div>
                                        <div>
                                            <p className="font-medium text-gray-800 dark:text-white/90">{st.name}</p>
                                            <p className="text-xs capitalize text-gray-500 dark:text-gray-400">{st.code} • {(st.jenis ?? '').replace('_', ' ')}</p>
                                        </div>
                                    </div>
                                    <p className="text-lg font-bold text-red-600 dark:text-red-400">{formatQty(st.current_stock)}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </JihansLayout>
    );
}
