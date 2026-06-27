import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';

const route = window.route;

const JENIS = ['frozen', 'tortilla', 'bakery', 'bahan_baku', 'aksesoris', 'minuman', 'snack', 'selai', 'property', 'lainnya'];

export default function JihansStockIndex({ stocks, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', jenis: filters.jenis ?? '' });
    const [activeTab, setActiveTab] = useState('cabang'); // cabang or gudang
    const hasFilter = form.search || form.jenis;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.stock.index'),
            { search: form.search || undefined, jenis: form.jenis || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['stocks', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Stok Tersedia Jihan's">
            <Head title="Stok Tersedia" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Stok Tersedia</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Saldo inventori produk siap jual di Jihan's Food</p>
                    </div>
                    <Link href={route('jihans.stock.movements')} className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm transition-all hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <Icon name="history" className="text-[18px]" /> Kartu Stok
                    </Link>
                </div>

                {/* Tabs */}
                <div className="flex gap-2">
                    <button
                        onClick={() => setActiveTab('cabang')}
                        className={`flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition ${activeTab === 'cabang' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                    >
                        <Icon name="storefront" className="text-[18px]" /> Stok Cabang Jihan's
                    </button>
                    <button
                        onClick={() => setActiveTab('gudang')}
                        className={`flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition ${activeTab === 'gudang' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                    >
                        <Icon name="warehouse" className="text-[18px]" /> Stok Gudang Utama
                    </button>
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
                                    placeholder="Cari nama produk atau kode..."
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 py-2 pl-9 pr-4 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                            </div>
                            <select
                                value={form.jenis}
                                onChange={(e) => setForm({ ...form, jenis: e.target.value })}
                                className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 py-2 px-3 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 capitalize"
                            >
                                <option value="" className="dark:bg-gray-800">Semua Jenis</option>
                                {JENIS.map((j) => (
                                    <option key={j} value={j} className="dark:bg-gray-800">
                                        {j.replace('_', ' ')}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                                Filter
                            </button>
                            {hasFilter && (
                                <Link href={route('jihans.stock.index')} className="rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 transition-colors">
                                    Reset
                                </Link>
                            )}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Info Produk</th>
                                    <th className="px-6 py-4 font-semibold">Kategori</th>
                                    <th className="px-6 py-4 text-center font-semibold">Safety Stock</th>
                                    {activeTab === 'cabang' ? (
                                        <th className="px-6 py-4 text-center font-semibold">Stok Tersedia</th>
                                    ) : (
                                        <th className="px-6 py-4 text-center font-semibold">Stok Gudang</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={4} />
                                    : stocks.data.length === 0 ? <EmptyState colSpan={4} icon="inventory_2" message="Tidak ada data stok." />
                                    : stocks.data.map((item) => (
                                        <tr key={item.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-gray-800 dark:text-white/90">{item.name}</span>
                                                    <span className="font-mono text-xs text-gray-400 dark:text-gray-550 mt-0.5">{item.code}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="text-xs font-bold text-gray-600 dark:text-gray-300">{item.category ?? '-'}</span>
                                                    <span className="text-[10px] capitalize text-gray-400 dark:text-gray-550 mt-0.5">{(item.jenis ?? '').replace('_', ' ')}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="rounded-full bg-gray-100 dark:bg-gray-850 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                    {formatQty(item.stock_min)}
                                                </span>
                                            </td>
                                            {activeTab === 'cabang' ? (
                                                <td className="px-6 py-4 text-center">
                                                    <div className={`inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 ${item.is_low ? 'border-red-100 bg-red-50 text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400' : 'border-green-100 bg-green-50 text-green-600 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-400'}`}>
                                                        <span className="text-sm font-black tabular-nums">{formatQty(item.current_stock)}</span>
                                                        <span className="text-[10px] font-bold uppercase">{item.unit ?? 'PCS'}</span>
                                                    </div>
                                                </td>
                                            ) : (
                                                <td className="px-6 py-4 text-center">
                                                    <div className="inline-flex items-center gap-2 rounded-xl border border-gray-150 bg-gray-50/30 px-3 py-1.5 dark:border-gray-800 dark:bg-white/[0.01]">
                                                        <span className="text-sm font-black tabular-nums text-gray-800 dark:text-white/90">{formatQty(item.gudang_stock)}</span>
                                                        <span className="text-[10px] font-bold uppercase text-gray-500">{item.unit ?? 'PCS'}</span>
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {stocks.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={stocks.meta.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
