import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';
import AdjustModal from './AdjustModal';

const route = window.route;

/** Filter bar — search + low-stock toggle. Submits via a partial Inertia reload. */
function StockFilters({ initial, onReload }) {
    const [search, setSearch] = useState(initial.search ?? '');
    const [lowStock, setLowStock] = useState(initial.low_stock === '1');
    const hasFilter = (initial.search ?? '') !== '' || initial.low_stock === '1';

    const submit = (e) => {
        e.preventDefault();
        onReload({ search: search || undefined, low_stock: lowStock ? '1' : undefined });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-4">
            <div className="relative min-w-[300px] flex-1">
                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Cari nama produk atau kode..."
                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                />
            </div>

            <label className="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition-all hover:bg-slate-50">
                <input
                    type="checkbox"
                    checked={lowStock}
                    onChange={(e) => setLowStock(e.target.checked)}
                    className="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span className="text-sm font-bold text-slate-600">Stok Menipis</span>
            </label>

            <button type="submit" className="rounded-2xl bg-slate-900 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">
                Terapkan
            </button>

            {hasFilter && (
                <Link
                    href={route('gudang.stock.index')}
                    className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"
                    title="Reset Filter"
                >
                    <Icon name="refresh" />
                </Link>
            )}
        </form>
    );
}

/** One product row in the stock table. */
function StockRow({ item, onAdjust }) {
    return (
        <tr className="group transition-colors hover:bg-slate-50/50">
            <td className="px-6 py-4">
                <div className="flex flex-col">
                    <span className="text-sm font-black tracking-tight text-slate-800 transition-colors group-hover:text-indigo-600">{item.name}</span>
                    <span className="mt-0.5 font-mono text-[10px] font-bold uppercase tracking-widest text-slate-400">{item.code}</span>
                </div>
            </td>
            <td className="px-6 py-4">
                <div className="flex flex-col">
                    <span className="text-xs font-bold text-slate-600">{item.category ?? '-'}</span>
                    <span className="text-[10px] font-medium capitalize text-slate-400">{(item.jenis ?? '').replace('_', ' ')}</span>
                </div>
            </td>
            <td className="px-6 py-4 text-center">
                <span className="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-500">{formatQty(item.stock_min)}</span>
            </td>
            <td className="px-6 py-4 text-center">
                <div className={`inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 ${item.is_low ? 'border-rose-100 bg-rose-50 text-rose-600' : 'border-emerald-100 bg-emerald-50 text-emerald-600'}`}>
                    <span className="text-sm font-black tabular-nums">{formatQty(item.current_stock)}</span>
                    <span className="text-[10px] font-bold uppercase">{item.unit ?? 'PCS'}</span>
                </div>
            </td>
            <td className="px-6 py-4 text-right">
                <button
                    type="button"
                    onClick={() => onAdjust(item)}
                    className="inline-flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-indigo-600 transition-all hover:bg-indigo-600 hover:text-white"
                >
                    <Icon name="edit_note" className="text-[16px]" />
                    Opname
                </button>
            </td>
        </tr>
    );
}

export default function StockIndex({ stocks, units, filters }) {
    const [loading, setLoading] = useState(false);
    const [adjusting, setAdjusting] = useState(null); // selected product or null

    const reload = (params) => {
        router.get(route('gudang.stock.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['stocks', 'filters'],
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    const rows = stocks.data;

    return (
        <GudangLayout title="Stok Gudang Utama" pageTitle="Inventori Gudang">
            <Head title="Stok Gudang Utama" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Stok Gudang Utama</h2>
                        <p className="text-sm font-medium text-slate-500">Monitoring saldo inventori dan penyesuaian fisik (Stock Opname)</p>
                    </div>
                    <Link
                        href={route('gudang.stock.movements')}
                        className="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50"
                    >
                        <Icon name="history" className="text-[20px]" />
                        Kartu Stok
                    </Link>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <StockFilters initial={filters} onReload={reload} />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50">
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Info Produk</th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Kategori</th>
                                    <th className="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Safety Stock</th>
                                    <th className="px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Stok Akhir</th>
                                    <th className="px-6 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? (
                                    <SkeletonTableRows rows={8} columns={5} />
                                ) : rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-12 text-center">
                                            <div className="flex flex-col items-center">
                                                <Icon name="inventory_2" className="mb-4 text-[64px] text-slate-200" />
                                                <p className="font-bold italic text-slate-400">Tidak ada data produk ditemukan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((item) => <StockRow key={item.id} item={item} onAdjust={setAdjusting} />)
                                )}
                            </tbody>
                        </table>
                    </div>

                    {stocks.meta?.links && (
                        <div className="border-t border-slate-100 p-6">
                            <Pagination links={stocks.meta.links} />
                        </div>
                    )}
                </div>
            </div>

            <AdjustModal product={adjusting} onClose={() => setAdjusting(null)} />
        </GudangLayout>
    );
}
