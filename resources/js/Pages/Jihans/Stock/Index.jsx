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
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">Stok Tersedia</h2>
                        <p className="text-sm text-gray-500">Saldo inventori produk siap jual di Jihan's Food</p>
                    </div>
                    <Link href={route('jihans.stock.movements')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                        <Icon name="history" className="text-[20px]" /> Kartu Stok
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama produk atau kode..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                            </div>
                            <select value={form.jenis} onChange={(e) => setForm({ ...form, jenis: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm capitalize focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Semua Jenis</option>
                                {JENIS.map((j) => <option key={j} value={j}>{j.replace('_', ' ')}</option>)}
                            </select>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                            {hasFilter && <Link href={route('jihans.stock.index')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Info Produk</th>
                                    <th className="px-6 py-4 font-medium">Kategori</th>
                                    <th className="px-6 py-4 text-center font-medium">Safety Stock</th>
                                    <th className="px-6 py-4 text-center font-medium">Stok Tersedia</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={8} columns={4} />
                                    : stocks.data.length === 0 ? <EmptyState colSpan={4} icon="inventory_2" message="Tidak ada data stok." />
                                    : stocks.data.map((item) => (
                                        <tr key={item.id} className="transition-colors hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-gray-800">{item.name}</span>
                                                    <span className="font-mono text-xs text-gray-400">{item.code}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="text-xs font-bold text-gray-600">{item.category ?? '-'}</span>
                                                    <span className="text-[10px] capitalize text-gray-400">{(item.jenis ?? '').replace('_', ' ')}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-center"><span className="rounded-lg bg-gray-100 px-2 py-1 text-xs font-bold text-gray-500">{formatQty(item.stock_min)}</span></td>
                                            <td className="px-6 py-4 text-center">
                                                <div className={`inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 ${item.is_low ? 'border-red-100 bg-red-50 text-red-600' : 'border-green-100 bg-green-50 text-green-600'}`}>
                                                    <span className="text-sm font-black tabular-nums">{formatQty(item.current_stock)}</span>
                                                    <span className="text-[10px] font-bold uppercase">{item.unit ?? 'PCS'}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {stocks.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={stocks.meta.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
