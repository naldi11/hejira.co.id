import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import { formatQty } from '@/lib/format';
import AdjustModal from './AdjustModal';
import Button from '@/Components/ui/button/Button';

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
            <div className="relative min-w-[280px] flex-1">
                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Cari nama produk atau kode..."
                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800"
                />
            </div>

            <label className="flex h-11 cursor-pointer items-center gap-3 rounded-lg border border-gray-300 bg-transparent px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50/50 dark:border-gray-700 dark:text-gray-250 dark:hover:bg-gray-900/30">
                <input
                    type="checkbox"
                    checked={lowStock}
                    onChange={(e) => setLowStock(e.target.checked)}
                    className="h-4.5 w-4.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900"
                />
                <span>Stok Menipis</span>
            </label>

            <Button type="submit" size="sm">
                Cari
            </Button>

            {hasFilter && (
                <Link
                    href={route('gudang.stock.index')}
                    className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    title="Reset Filter"
                >
                    <Icon name="refresh" />
                </Link>
            )}
        </form>
    );
}

/** One product card in the stock grid. */
function StockCard({ item, onAdjust }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between hover:border-brand-300 dark:hover:border-brand-800 hover:shadow-md transition-all">
            <div>
                {/* Header: Name & Code */}
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <h3 
                            className="font-bold text-gray-800 dark:text-white/90 truncate hover:text-brand-500 dark:hover:text-brand-400 hover:underline cursor-pointer" 
                            onClick={() => onAdjust(item)} 
                            title={item.name}
                        >
                            {item.name}
                        </h3>
                        <p className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                            {item.code}
                        </p>
                    </div>
                    <span className="shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {item.category ?? 'Umum'}
                    </span>
                </div>

                <p className="mt-2 text-xs font-semibold text-gray-450 dark:text-gray-500 uppercase tracking-wider">
                    Tipe: {(item.jenis ?? '').replace('_', ' ')}
                </p>

                {/* Divider */}
                <div className="my-4 border-t border-gray-100 dark:border-gray-800" />

                {/* Stock Metrics Split Grid */}
                <div className="grid grid-cols-3 gap-2.5">
                    {/* Stok Bagus / Siap Jual */}
                    <div className={`rounded-xl border p-2.5 text-center ${item.is_low ? 'border-rose-100 bg-rose-50/50 text-rose-700 dark:border-rose-900/30 dark:bg-rose-500/10 dark:text-rose-400' : 'border-emerald-100 bg-emerald-50/50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-500/10 dark:text-emerald-400'}`}>
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Siap Jual</span>
                        <span className="block text-lg font-bold tabular-nums leading-none mb-1">{formatQty(item.current_stock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-500">{item.unit ?? 'PCS'}</span>
                    </div>

                    {/* Retur Rusak */}
                    <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550 mb-1">Retur Rusak</span>
                        <span className={`block text-lg font-bold tabular-nums leading-none mb-1 ${item.returned_defect_stock > 0 ? 'text-rose-600 dark:text-rose-455' : 'text-gray-400 dark:text-gray-650'}`}>{formatQty(item.returned_defect_stock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-550">{item.unit ?? 'PCS'}</span>
                    </div>

                    {/* Retur Expired */}
                    <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-455 dark:text-gray-550 mb-1">Expired</span>
                        <span className={`block text-lg font-bold tabular-nums leading-none mb-1 ${item.returned_expired_stock > 0 ? 'text-amber-600 dark:text-amber-455' : 'text-gray-400 dark:text-gray-650'}`}>{formatQty(item.returned_expired_stock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-550">{item.unit ?? 'PCS'}</span>
                    </div>
                </div>

                {/* Safety Stock / Minimum */}
                <div className="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Safety Stock (Minimum):</span>
                    <span className="font-semibold text-gray-700 dark:text-gray-300">{formatQty(item.stock_min)} {item.unit}</span>
                </div>
            </div>

            {/* Action footer */}
            <div className="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <Button
                    type="button"
                    onClick={() => onAdjust(item)}
                    size="sm"
                    variant="outline"
                    startIcon={<Icon name="edit_note" className="text-[18px]" />}
                    className="w-full"
                >
                    Opname
                </Button>
            </div>
        </div>
    );
}

/** Animated Skeleton loader for grid cards */
function SkeletonCards() {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 animate-pulse">
            {[...Array(6)].map((_, i) => (
                <div key={i} className="rounded-2xl border border-gray-150 bg-gray-50/20 p-5 dark:border-gray-800 dark:bg-white/[0.01]">
                    <div className="flex justify-between items-start mb-3">
                        <div className="w-2/3">
                            <div className="h-4 bg-gray-200 dark:bg-gray-800 rounded w-full mb-2" />
                            <div className="h-3 bg-gray-100 dark:bg-gray-850 rounded w-1/3" />
                        </div>
                        <div className="h-5 bg-gray-200 dark:bg-gray-800 rounded-full w-14" />
                    </div>
                    <div className="h-3 bg-gray-100 dark:bg-gray-850 rounded w-1/4 mt-3" />
                    <div className="h-px bg-gray-100 dark:bg-gray-800 my-4" />
                    <div className="grid grid-cols-3 gap-2.5">
                        <div className="h-16 bg-gray-100 dark:bg-gray-850 rounded-xl" />
                        <div className="h-16 bg-gray-100 dark:bg-gray-850 rounded-xl" />
                        <div className="h-16 bg-gray-100 dark:bg-gray-850 rounded-xl" />
                    </div>
                    <div className="h-8 bg-gray-100 dark:bg-gray-800 rounded-xl mt-5" />
                </div>
            ))}
        </div>
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
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Stok Gudang Utama</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Monitoring saldo inventori dan penyesuaian fisik (Stock Opname)</p>
                    </div>
                    <Link href={route('gudang.stock.movements')}>
                        <Button variant="outline" size="sm" startIcon={<Icon name="history" className="text-[18px]" />}>
                            KARTU STOK
                        </Button>
                    </Link>
                </div>

                {/* Filter Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <StockFilters initial={filters} onReload={reload} />
                </div>

                {/* Grid Content */}
                {loading ? (
                    <SkeletonCards />
                ) : rows.length === 0 ? (
                    <div className="rounded-2xl border border-gray-200 bg-white p-16 text-center dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <div className="flex flex-col items-center">
                            <Icon name="inventory_2" className="mb-4 text-[56px] text-gray-300 dark:text-gray-650" />
                            <p className="font-bold italic text-gray-400 dark:text-gray-550">Tidak ada data produk ditemukan.</p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {rows.map((item) => (
                            <StockCard key={item.id} item={item} onAdjust={setAdjusting} />
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {stocks.meta?.links && (
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <Pagination links={stocks.meta.links} />
                    </div>
                )}
            </div>

            <AdjustModal product={adjusting} onClose={() => setAdjusting(null)} />
        </GudangLayout>
    );
}
