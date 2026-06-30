import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { formatQty } from '@/lib/format';

const route = window.route;

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
                    <div className="h-px bg-gray-100 dark:bg-gray-800 my-4" />
                    <div className="grid grid-cols-2 gap-2.5">
                        <div className="h-16 bg-gray-150 dark:bg-gray-800 rounded-xl" />
                        <div className="h-16 bg-gray-150 dark:bg-gray-800 rounded-xl" />
                    </div>
                </div>
            ))}
        </div>
    );
}

function StockCard({ item, isPusat, activeTab }) {
    const isCabangTab = activeTab === 'cabang';
    const mainStock = isCabangTab ? item.current_stock : item.parent_stock;

    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between hover:border-amber-300 dark:hover:border-amber-800 hover:shadow-md transition-all">
            <div>
                {/* Header: Name & Code */}
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <h3 className="font-bold text-gray-800 dark:text-white/90 truncate" title={item.name}>
                            {item.name}
                        </h3>
                        <p className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-550">
                            {item.code}
                        </p>
                    </div>
                    <span className="shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {item.category ?? 'Brownies'}
                    </span>
                </div>

                <p className="mt-2 text-xs font-semibold text-gray-455 dark:text-gray-500 uppercase tracking-wider">
                    Tipe: {(item.jenis ?? '').replace('_', ' ')}
                </p>

                {/* Divider */}
                <div className="my-4 border-t border-gray-100 dark:border-gray-800" />

                {/* Stock Metrics Split Grid */}
                <div className="grid grid-cols-2 gap-2.5">
                    {/* Stok Bagus / Siap Jual */}
                    <div className={`rounded-xl border p-2.5 text-center ${item.is_low && isCabangTab ? 'border-rose-100 bg-rose-50/50 text-rose-700 dark:border-rose-900/30 dark:bg-rose-500/10 dark:text-rose-400' : 'border-emerald-100 bg-emerald-50/50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-500/10 dark:text-emerald-400'}`}>
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            {isCabangTab ? 'Siap Jual' : (isPusat ? 'Stok Gudang' : 'Stok Pusat')}
                        </span>
                        <span className="block text-lg font-bold tabular-nums leading-none mb-1">{formatQty(mainStock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-550">{item.unit ?? 'PCS'}</span>
                    </div>

                    {/* Stok Retur / Safety Stock */}
                    {isCabangTab ? (
                        <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                            <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-455 dark:text-gray-550 mb-1">Stok Retur</span>
                            <span className={`block text-lg font-bold tabular-nums leading-none mb-1 ${item.return_stock > 0 ? 'text-amber-600 dark:text-amber-455' : 'text-gray-400 dark:text-gray-650'}`}>{formatQty(item.return_stock)}</span>
                            <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-550">{item.unit ?? 'PCS'}</span>
                        </div>
                    ) : (
                        <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01] flex flex-col justify-center">
                            <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-455 dark:text-gray-550 mb-1">Safety Stock</span>
                            <span className="block text-sm font-bold text-gray-700 dark:text-gray-300">{formatQty(item.stock_min)} {item.unit}</span>
                        </div>
                    )}
                </div>

                {/* Info Text */}
                {isCabangTab && (
                    <div className="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Safety Stock (Minimum):</span>
                        <span className="font-semibold text-gray-700 dark:text-gray-300">{formatQty(item.stock_min)} {item.unit}</span>
                    </div>
                )}
            </div>
        </div>
    );
}

function BranchStockCard({ item }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between hover:border-amber-300 dark:hover:border-amber-800 hover:shadow-md transition-all">
            <div>
                {/* Header: Name & Code */}
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <h3 className="font-bold text-gray-800 dark:text-white/90 truncate" title={item.name}>
                            {item.name}
                        </h3>
                        <p className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-550">
                            {item.code}
                        </p>
                    </div>
                    <span className="shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {item.category ?? 'Brownies'}
                    </span>
                </div>

                {/* Divider */}
                <div className="my-4 border-t border-gray-100 dark:border-gray-800" />

                {/* Stock Metrics Split Grid */}
                <div className="grid grid-cols-2 gap-2.5">
                    {/* Stok Cabang */}
                    <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-550 dark:text-gray-455 mb-1">Stok Cabang</span>
                        <span className="block text-lg font-bold tabular-nums leading-none mb-1 text-gray-850 dark:text-white/90">{formatQty(item.current_stock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-500">{item.unit ?? 'PCS'}</span>
                    </div>

                    {/* Retur Cabang */}
                    <div className="rounded-xl border border-gray-150 bg-gray-50/30 p-2.5 text-center dark:border-gray-800 dark:bg-white/[0.01]">
                        <span className="block text-[9px] font-bold uppercase tracking-wider text-gray-455 dark:text-gray-550 mb-1">Retur Cabang</span>
                        <span className={`block text-lg font-bold tabular-nums leading-none mb-1 ${item.return_stock > 0 ? 'text-amber-600 dark:text-amber-455' : 'text-gray-400 dark:text-gray-650'}`}>{formatQty(item.return_stock)}</span>
                        <span className="block text-[8px] font-bold uppercase text-gray-400 dark:text-gray-550">{item.unit ?? 'PCS'}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function HendhysStockIndex({ stocks, branches, branchStocks, selectedBranchId, isPusat, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', branch_id: filters.branch_id ?? '' });
    const [activeTab, setActiveTab] = useState('cabang'); // cabang or gudang

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('hendhys.stock.index'),
            { search: form.search || undefined, branch_id: form.branch_id || undefined },
            { 
                preserveState: true, 
                preserveScroll: true, 
                replace: true, 
                only: ['stocks', 'branchStocks', 'filters', 'selectedBranchId'], 
                onStart: () => setLoading(true), 
                onFinish: () => setLoading(false) 
            }
        );
    };

    return (
        <HendhysLayout pageTitle="Stok Tersedia Hendhys">
            <Head title="Stok Tersedia" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Stok {isPusat ? 'Pusat' : 'Cabang'}</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Saldo inventori produk Hendhys Brownies</p>
                    </div>
                    <Link href={route('hendhys.stock.movements')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-theme-xs transition-all hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="history" className="text-[20px]" /> Kartu Stok
                    </Link>
                </div>

                {/* Tabs */}
                <div className="flex gap-2">
                    <button
                        onClick={() => setActiveTab('cabang')}
                        className={`flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition ${activeTab === 'cabang' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                    >
                        <Icon name="storefront" className="text-[18px]" /> {isPusat ? 'Stok Hendhys Produksi' : 'Stok Cabang Sendiri'}
                    </button>
                    <button
                        onClick={() => setActiveTab('gudang')}
                        className={`flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition ${activeTab === 'gudang' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                    >
                        <Icon name="warehouse" className="text-[18px]" /> {isPusat ? 'Stok Gudang Utama' : 'Stok Hendhys Produksi'}
                    </button>
                </div>

                {/* Filter Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                        <div className="relative min-w-[260px] flex-1">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-550" />
                            <input 
                                type="text" 
                                value={form.search} 
                                onChange={(e) => setForm({ ...form, search: e.target.value })} 
                                placeholder="Cari nama produk atau kode..."
                                className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-10 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-amber-350 focus:ring-3 focus:ring-amber-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-amber-800" 
                            />
                        </div>
                        <button type="submit" className="h-11 rounded-xl bg-amber-600 px-6 py-2 text-sm font-bold text-white shadow-sm hover:bg-amber-700 transition-all">Filter</button>
                        {form.search && (
                            <Link href={route('hendhys.stock.index')} className="flex h-11 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-5 text-sm font-bold text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                Reset
                            </Link>
                        )}
                    </form>
                </div>

                {/* Grid Content */}
                {loading ? (
                    <SkeletonCards />
                ) : stocks.data.length === 0 ? (
                    <div className="rounded-2xl border border-gray-200 bg-white p-16 text-center dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <EmptyState colSpan={1} icon="inventory_2" message="Tidak ada data stok." />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        {stocks.data.map((item) => (
                            <StockCard key={item.id} item={item} isPusat={isPusat} activeTab={activeTab} />
                        ))}
                    </div>
                )}

                {stocks.meta?.links && (
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <Pagination links={stocks.meta.links} />
                    </div>
                )}

                {/* Branch stock section for pusat */}
                {isPusat && branches && (
                    <div className="space-y-6 mt-8">
                        <div>
                            <h3 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Stok per Cabang</h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400 font-medium">Monitoring saldo inventori produk Brownies di setiap cabang aktif</p>
                        </div>

                        {/* Branch Filter Card */}
                        <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                            <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                                <select 
                                    value={form.branch_id} 
                                    onChange={(e) => setForm({ ...form, branch_id: e.target.value })} 
                                    className="h-11 rounded-lg border border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 bg-white dark:bg-gray-850 dark:text-white"
                                >
                                    <option value="">Semua Cabang</option>
                                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                                <button type="submit" className="h-11 rounded-xl bg-amber-600 px-6 py-2 text-sm font-bold text-white shadow-sm hover:bg-amber-700 transition-all">Filter Cabang</button>
                            </form>
                        </div>

                        {/* Branch Grid Content */}
                        {branchStocks?.data?.length === 0 ? (
                            <div className="rounded-2xl border border-gray-200 bg-white p-16 text-center dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                                <EmptyState colSpan={1} icon="store" message="Tidak ada stok cabang." />
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                {branchStocks?.data?.map((item, idx) => (
                                    <BranchStockCard key={`branch-${item.id}-${idx}`} item={item} />
                                ))}
                            </div>
                        )}

                        {branchStocks?.meta?.links && (
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                                <Pagination links={branchStocks.meta.links} />
                            </div>
                        )}
                    </div>
                )}
            </div>
        </HendhysLayout>
    );
}
