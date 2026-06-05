import { Head } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah, formatQty, formatDate } from '@/lib/format';

export default function OwnerJihans({ stats, recentTransactions, topProducts }) {
    return (
        <OwnerLayout pageTitle="Dashboard Jihan's Food">
            <Head title="Owner — Jihan's" />

            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-600"><Icon name="payments" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Pendapatan</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800">{formatRupiah(stats.total_revenue)}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600"><Icon name="today" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Pendapatan Hari Ini</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800">{formatRupiah(stats.revenue_today)}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600"><Icon name="factory" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Produksi Hari Ini</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800">{formatQty(stats.production_today)} <span className="text-sm font-normal text-slate-400">batch</span></p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex items-center gap-2 border-b border-slate-100 p-4"><Icon name="receipt_long" className="text-[20px] text-orange-500" /><h3 className="font-bold text-slate-800">Transaksi Terakhir</h3></div>
                        <table className="w-full text-left text-sm">
                            <tbody className="divide-y divide-slate-100">
                                {recentTransactions.length === 0 ? <EmptyState colSpan={3} icon="receipt_long" message="Belum ada transaksi." />
                                    : recentTransactions.map((t) => (
                                        <tr key={t.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3"><p className="font-mono font-medium text-slate-800">{t.transaction_number}</p><p className="text-xs text-slate-400">{formatDate(t.date)} · {t.customer_name}</p></td>
                                            <td className="px-4 py-3 text-right font-bold text-slate-900">{formatRupiah(t.grand_total)}</td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex items-center gap-2 border-b border-slate-100 p-4"><Icon name="local_fire_department" className="text-[20px] text-red-500" /><h3 className="font-bold text-slate-800">Produk Terlaris</h3></div>
                        <table className="w-full text-left text-sm">
                            <tbody className="divide-y divide-slate-100">
                                {topProducts.length === 0 ? <EmptyState colSpan={2} icon="inventory_2" message="Belum ada penjualan." />
                                    : topProducts.map((p, i) => (
                                        <tr key={i} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 font-medium text-slate-700"><span className="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{i + 1}</span>{p.name}</td>
                                            <td className="px-4 py-3 text-right font-bold tabular-nums text-slate-900">{formatQty(p.total_sold)} terjual</td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
