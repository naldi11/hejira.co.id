import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';

const route = window.route;

const SOURCE_LABELS = {
    transfer_gudang: 'Transfer dari Gudang',
    production: 'Hasil Produksi',
    receive_from_gudang: 'Terima dari Gudang',
    return_gudang: 'Retur ke Gudang',
    pos_sale: 'Penjualan POS',
    adjustment: 'Penyesuaian',
};

export default function JihansStockMovements({ movements, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', type: filters.type ?? '' });
    const hasFilter = form.search || form.type;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.stock.movements'),
            { search: form.search || undefined, type: form.type || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['movements', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Histori Pergerakan Stok">
            <Head title="Kartu Stok" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Histori Pergerakan Stok</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Log keluar masuk barang di Jihan's Food</p>
                    </div>
                    <Link href={route('jihans.stock.index')} className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm transition-all hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        Kembali ke Stok
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                                <input
                                    type="text"
                                    value={form.search}
                                    onChange={(e) => setForm({ ...form, search: e.target.value })}
                                    placeholder="Cari nama produk..."
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 py-2 pl-9 pr-4 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                            </div>
                            <select
                                value={form.type}
                                onChange={(e) => setForm({ ...form, type: e.target.value })}
                                className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-855 py-2 px-3 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                            >
                                <option value="" className="dark:bg-gray-800">Semua Tipe</option>
                                <option value="in" className="dark:bg-gray-800">Masuk (In)</option>
                                <option value="out" className="dark:bg-gray-800">Keluar (Out)</option>
                            </select>
                            <button type="submit" className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                                Filter
                            </button>
                            {hasFilter && (
                                <Link href={route('jihans.stock.movements')} className="rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 transition-colors">
                                    Reset
                                </Link>
                            )}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Waktu</th>
                                    <th className="px-6 py-4 font-semibold">Produk</th>
                                    <th className="px-6 py-4 font-semibold">Tipe</th>
                                    <th className="px-6 py-4 text-right font-semibold">Qty</th>
                                    <th className="px-6 py-4 font-semibold">Sumber</th>
                                    <th className="px-6 py-4 font-semibold">Operator</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : movements.data.length === 0 ? <EmptyState colSpan={6} icon="history" message="Belum ada histori pergerakan stok." />
                                    : movements.data.map((m) => {
                                        const isIn = m.type === 'in';
                                        return (
                                            <tr key={m.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-400 font-medium">{m.created_at}</td>
                                                <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{m.product}</td>
                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${isIn ? 'bg-green-50 dark:bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400'}`}>
                                                        <Icon name={isIn ? 'south_west' : 'north_east'} className="text-[14px]" />{isIn ? 'Masuk' : 'Keluar'}
                                                    </span>
                                                </td>
                                                <td className={`px-6 py-4 text-right font-bold ${isIn ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                                                    {isIn ? '+' : '-'}{formatQty(m.quantity)}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">{SOURCE_LABELS[m.source] ?? m.source}</td>
                                                <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">{m.operator}</td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                    </div>
                    {movements.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={movements.meta.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
