import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

/* ─── Stock Card (mobile-first card list item) ───────────────────────────── */
function StockItemCard({ row, type }) {
    if (type === 'movements') {
        return (
            <div className="flex items-start gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <span className={`mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                    row.type === 'in' ? 'bg-green-100 text-green-700 dark:bg-green-950/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400'
                }`}>
                    {row.type === 'in' ? '+' : '-'}
                </span>
                <div className="flex-1 min-w-0">
                    <p className="font-bold text-slate-800 dark:text-white/90 text-sm truncate">{row.product_name}</p>
                    <p className="text-xs text-slate-400 mt-0.5">{row.date} · {row.user}</p>
                    {row.notes && <p className="text-xs text-slate-500 mt-1 truncate">{row.notes}</p>}
                </div>
                <span className={`text-base font-black ${row.type === 'in' ? 'text-green-600' : 'text-red-600'}`}>
                    {formatQty(row.quantity)}
                </span>
            </div>
        );
    }

    if (type === 'po') {
        return (
            <div className="rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="flex items-center justify-between mb-2">
                    <span className="font-mono font-bold text-xs text-slate-600 dark:text-white/80">{row.po_number}</span>
                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                        row.status === 'received' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
                    }`}>{row.status}</span>
                </div>
                <p className="font-semibold text-slate-800 dark:text-white/90 text-sm">{row.supplier}</p>
                <div className="flex items-center justify-between mt-2">
                    <span className="text-xs text-slate-400">{row.date} · {row.user}</span>
                    <span className="font-black text-slate-900 dark:text-white">{formatRupiah(row.total_amount)}</span>
                </div>
            </div>
        );
    }

    if (type === 'cabang') {
        return (
            <div className="rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="font-mono text-xs text-slate-400">{row.code}</p>
                        <p className="font-semibold text-slate-800 dark:text-white/90 text-sm mt-0.5">{row.name}</p>
                    </div>
                    <div className="text-right">
                        <p className="text-[10px] text-slate-400">Baik / Return</p>
                        <p className="font-black text-slate-900 dark:text-white">
                            {formatQty(row.quantity)} <span className="text-xs font-normal text-red-405 dark:text-red-400">/ {formatQty(row.quantity_return)}</span>
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    // Default: stock biasa (gudang, retail, hendhys_pusat)
    return (
        <div className="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <p className="font-mono text-[10px] text-slate-400">{row.code}</p>
                <p className="font-semibold text-slate-800 dark:text-white/90 text-sm mt-0.5">{row.name}</p>
            </div>
            <div className="text-right">
                <p className="text-lg font-black text-slate-900 dark:text-white">{formatQty(row.quantity)}</p>
                <p className="text-[10px] text-slate-400">{row.unit}</p>
            </div>
        </div>
    );
}

/* ─── Transaction Card ───────────────────────────────────────────────────── */
function TransactionCard({ row, showBranch }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-mono font-bold text-xs text-slate-600 dark:text-white/80">{row.transaction_number}</span>
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-100 text-green-700">{row.status}</span>
                    </div>
                    <p className="font-semibold text-slate-800 dark:text-white/90 text-sm mt-1">{row.customer || '-'}</p>
                    {showBranch && <p className="text-xs text-slate-400 mt-0.5">{row.type_unit || row.branch}</p>}
                </div>
                <div className="text-right shrink-0">
                    <p className="font-black text-slate-900 dark:text-white">{formatRupiah(row.grand_total)}</p>
                    <p className="text-[10px] text-slate-400 mt-1">{row.date}</p>
                </div>
            </div>
            <p className="text-[11px] text-slate-400 mt-2">Kasir: {row.user}</p>
        </div>
    );
}

export default function Detail({ mode, unit, title, subtitle, list }) {
    const [searchQuery, setSearchQuery] = useState('');

    const getStockCardType = () => {
        if (unit === 'movements') return 'movements';
        if (unit === 'po') return 'po';
        if (unit?.startsWith('hendhys_cabang_')) return 'cabang';
        return 'default';
    };

    const getFilteredData = () => {
        if (!searchQuery) return list;
        const q = searchQuery.toLowerCase();
        return list.filter(item =>
            (item.name && item.name.toLowerCase().includes(q)) ||
            (item.code && item.code.toLowerCase().includes(q)) ||
            (item.po_number && item.po_number.toLowerCase().includes(q)) ||
            (item.supplier && item.supplier.toLowerCase().includes(q)) ||
            (item.transaction_number && item.transaction_number.toLowerCase().includes(q)) ||
            (item.customer && item.customer.toLowerCase().includes(q)) ||
            (item.notes && item.notes.toLowerCase().includes(q)) ||
            (item.branch && item.branch.toLowerCase().includes(q)) ||
            (item.product_name && item.product_name.toLowerCase().includes(q))
        );
    };

    const filteredData = getFilteredData();

    return (
        <OwnerLayout pageTitle={`Detail: ${title}`}>
            <Head title={`Detail — ${title}`} />

            <div className="space-y-6 max-w-3xl mx-auto">
                {/* Header Card */}
                <div className="flex items-center justify-between bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] p-5 rounded-2xl shadow-sm">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('owner.dashboard')}
                            className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            <Icon name="arrow_back" className="text-[20px]" />
                        </Link>
                        <div>
                            <h2 className="font-black text-slate-800 dark:text-white text-lg">{title}</h2>
                            <p className="text-xs text-slate-400 mt-0.5">{subtitle}</p>
                        </div>
                    </div>
                    <span className="text-xs bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 px-3 py-1.5 rounded-full font-bold uppercase tracking-wider">
                        {mode}
                    </span>
                </div>

                {/* Search input */}
                <div className="relative">
                    <Icon name="search" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]" />
                    <input
                        type="text"
                        placeholder="Cari data..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full rounded-2xl border border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm shadow-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-gray-850 dark:bg-white/[0.02] dark:text-white"
                    />
                </div>

                {/* List Container */}
                <div className="space-y-3">
                    {filteredData.length === 0 ? (
                        <div className="flex flex-col items-center justify-center gap-3 py-20 text-slate-400 bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] rounded-2xl">
                            <Icon name="search_off" className="text-[48px]" />
                            <p className="text-sm">Tidak ada data yang cocok dengan pencarian.</p>
                        </div>
                    ) : mode === 'stock' ? (
                        filteredData.map((row, idx) => (
                            <StockItemCard key={idx} row={row} type={getStockCardType()} />
                        ))
                    ) : (
                        filteredData.map((row, idx) => (
                            <TransactionCard
                                key={idx}
                                row={row}
                                showBranch={unit === 'all_transactions'}
                            />
                        ))
                    )}
                </div>
            </div>
        </OwnerLayout>
    );
}
