import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function HendhysStockIndex({ stocks, branches, branchStocks, selectedBranchId, isPusat, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', branch_id: filters.branch_id ?? '' });

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('hendhys.stock.index'),
            { search: form.search || undefined, branch_id: form.branch_id || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['stocks', 'branchStocks', 'filters', 'selectedBranchId'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <HendhysLayout pageTitle="Stok Tersedia Hendhys">
            <Head title="Stok Tersedia" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">Stok {isPusat ? 'Pusat' : 'Cabang'}</h2>
                        <p className="text-sm text-gray-500">Saldo inventori produk Hendhys Brownies</p>
                    </div>
                    <Link href={route('hendhys.stock.movements')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                        <Icon name="history" className="text-[20px]" /> Kartu Stok
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama produk atau kode..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                            {form.search && <Link href={route('hendhys.stock.index')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
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

                {/* Branch stock section for pusat */}
                {isPusat && branches && (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-100 bg-amber-50/40 p-5">
                            <h3 className="font-semibold text-gray-800">Stok per Cabang</h3>
                        </div>
                        <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                            <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                                <select value={form.branch_id} onChange={(e) => { setForm({ ...form, branch_id: e.target.value }); }} className="rounded-lg border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Semua Cabang</option>
                                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                                <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter Cabang</button>
                            </form>
                        </div>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Produk</th>
                                        <th className="px-6 py-4 text-center font-medium">Stok</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {branchStocks?.data?.length === 0 ? <EmptyState colSpan={2} icon="store" message="Tidak ada stok cabang." />
                                        : branchStocks?.data?.map((item, idx) => (
                                            <tr key={`branch-${item.id}-${idx}`} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <span className="font-bold text-gray-800">{item.name}</span>
                                                    <span className="ml-2 font-mono text-xs text-gray-400">{item.code}</span>
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className="font-black tabular-nums text-gray-800">{formatQty(item.current_stock)}</span>
                                                    <span className="ml-1 text-[10px] font-bold uppercase text-gray-400">{item.unit ?? 'PCS'}</span>
                                                </td>
                                            </tr>
                                        ))}
                                </tbody>
                            </table>
                        </div>
                        {branchStocks?.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={branchStocks.meta.links} /></div>}
                    </div>
                )}
            </div>
        </HendhysLayout>
    );
}
