import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
    const [expanded, setExpanded] = useState(false);

    return (
        <div 
            onClick={() => setExpanded(!expanded)}
            className="rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition cursor-pointer hover:border-blue-200 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700"
        >
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
            
            <div className="flex items-center justify-between mt-2">
                <p className="text-[11px] text-slate-400">Kasir: {row.user}</p>
                <button className="text-[10px] font-bold text-blue-500 hover:text-blue-600 flex items-center gap-1">
                    {expanded ? 'Tutup Detail' : 'Lihat Detail'}
                    <Icon name={expanded ? 'expand_less' : 'expand_more'} className="text-[14px]" />
                </button>
            </div>

            {expanded && row.details && (
                <div className="mt-4 pt-4 border-t border-slate-100 dark:border-gray-800 space-y-3">
                    {row.details.map((detail, idx) => (
                        <div key={idx} className="flex justify-between items-start text-sm">
                            <div className="flex-1 min-w-0 pr-4">
                                <p className="font-medium text-slate-800 dark:text-slate-200 truncate">{detail.product_name}</p>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    {formatQty(detail.quantity)} x {formatRupiah(detail.price)}
                                </p>
                            </div>
                            <p className="font-semibold text-slate-900 dark:text-slate-100 shrink-0">
                                {formatRupiah(detail.total)}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

/* ─── Shift Card ─────────────────────────────────────────────────────────── */
function ShiftCard({ row, showBranch }) {
    const [expanded, setExpanded] = useState(false);
    const isClosed = row.status === 'closed';

    const totalOmset = (row.payment_summary?.tunai || 0) + 
                       (row.payment_summary?.transfer || 0) + 
                       (row.payment_summary?.kartu_debit || 0) + 
                       (row.payment_summary?.kartu_kredit || 0);

    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-amber-200 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700">
            <div 
                onClick={() => setExpanded(!expanded)}
                className="flex items-start justify-between gap-2 cursor-pointer"
            >
                <div className="min-w-0">
                    <div className="flex items-center gap-2 flex-wrap mb-1">
                        <span className="font-bold text-slate-800 dark:text-white/90 text-sm">Shift {row.user}</span>
                        <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${isClosed ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700'}`}>
                            {isClosed ? 'Selesai' : 'Aktif'}
                        </span>
                    </div>
                    {showBranch && <p className="text-xs text-slate-400 mt-0.5">{row.type_unit}</p>}
                    <div className="flex gap-4 mt-1">
                        <div>
                            <p className="text-[10px] text-slate-400">Buka</p>
                            <p className="text-xs font-semibold text-slate-600 dark:text-slate-300">{new Date(row.opened_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}</p>
                        </div>
                        {isClosed && (
                            <div>
                                <p className="text-[10px] text-slate-400">Tutup</p>
                                <p className="text-xs font-semibold text-slate-600 dark:text-slate-300">{new Date(row.closed_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}</p>
                            </div>
                        )}
                    </div>
                </div>
                <div className="text-right shrink-0">
                    <p className="text-[10px] text-slate-400">Expected Cash</p>
                    <p className="font-black text-emerald-600 dark:text-emerald-400">{formatRupiah(row.expected_cash)}</p>
                    <p className="text-[10px] text-slate-400 mt-2">Total Omset</p>
                    <p className="font-bold text-blue-600 dark:text-blue-400 text-sm">{formatRupiah(totalOmset)}</p>
                    {isClosed && (
                        <p className={`text-[11px] font-bold mt-2 ${row.discrepancy === 0 ? 'text-slate-400' : row.discrepancy > 0 ? 'text-blue-500' : 'text-rose-500'}`}>
                            Selisih: {formatRupiah(row.discrepancy)}
                        </p>
                    )}
                </div>
            </div>

            {expanded && (
                <div className="mt-4 pt-4 border-t border-slate-100 dark:border-gray-800 text-sm">
                    <div className="grid grid-cols-2 gap-y-2 gap-x-4">
                        <div className="flex justify-between">
                            <span className="text-slate-500">Tunai (Cash)</span>
                            <span className="font-semibold text-slate-800 dark:text-white">{formatRupiah(row.payment_summary?.tunai)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-500">Transfer</span>
                            <span className="font-semibold text-slate-800 dark:text-white">{formatRupiah(row.payment_summary?.transfer)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-500">Kartu Debit</span>
                            <span className="font-semibold text-slate-800 dark:text-white">{formatRupiah(row.payment_summary?.kartu_debit)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-500">Kartu Kredit</span>
                            <span className="font-semibold text-slate-800 dark:text-white">{formatRupiah(row.payment_summary?.kartu_kredit)}</span>
                        </div>
                        <div className="flex justify-between col-span-2 border-t border-slate-100 dark:border-gray-800 pt-2 mt-1">
                            <span className="font-bold text-slate-800 dark:text-white">Modal Awal</span>
                            <span className="font-bold text-amber-600">{formatRupiah(row.starting_cash)}</span>
                        </div>
                        {isClosed && (
                            <div className="flex justify-between col-span-2">
                                <span className="font-bold text-slate-800 dark:text-white">Uang Aktual di Laci</span>
                                <span className="font-bold text-slate-800 dark:text-white">{formatRupiah(row.actual_cash)}</span>
                            </div>
                        )}
                        {row.note && (
                            <div className="col-span-2 mt-2 p-2 bg-slate-50 dark:bg-gray-800 rounded-lg text-xs">
                                <span className="font-semibold text-slate-600 dark:text-gray-300 block mb-0.5">Catatan Kasir:</span>
                                <span className="italic text-slate-500 dark:text-gray-400">{row.note}</span>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Detail({ mode, unit, title, subtitle, list, shifts, filter, trends }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTab, setActiveTab] = useState('transactions');

    const handleFilterChange = (newFilter) => {
        router.get(route('owner.dashboard.detail'), { mode, unit, filter: newFilter }, { preserveState: true, preserveScroll: true });
    };

    const getStockCardType = () => {
        if (unit === 'movements') return 'movements';
        if (unit === 'po') return 'po';
        if (unit?.startsWith('hendhys_cabang_')) return 'cabang';
        return 'default';
    };

    const getFilteredTransactions = () => {
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

    const getFilteredShifts = () => {
        if (!shifts) return [];
        if (!searchQuery) return shifts;
        const q = searchQuery.toLowerCase();
        return shifts.filter(item => 
            (item.user && item.user.toLowerCase().includes(q)) ||
            (item.note && item.note.toLowerCase().includes(q))
        );
    };

    const filteredTransactions = getFilteredTransactions();
    const filteredShifts = getFilteredShifts();

    // Chart dimensions
    const chartHeight = 120;
    const chartWidth = 500;

    return (
        <OwnerLayout pageTitle={`Detail: ${title}`}>
            <Head title={`Detail — ${title}`} />

            <div className="space-y-6 max-w-3xl mx-auto">
                {/* Header Card */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] p-5 rounded-2xl shadow-sm gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('owner.dashboard')}
                            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        >
                            <Icon name="arrow_back" className="text-[20px]" />
                        </Link>
                        <div>
                            <h2 className="font-black text-slate-800 dark:text-white text-lg">{title}</h2>
                            <p className="text-xs text-slate-400 mt-0.5">{subtitle}</p>
                        </div>
                    </div>
                    {mode === 'omset' && (
                        <div className="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100/80 dark:bg-gray-900/50 self-start sm:self-auto overflow-x-auto">
                            {[
                                { id: 'all', label: 'Semua' },
                                { id: 'today', label: 'Hari Ini' },
                                { id: 'week', label: 'Minggu Ini' },
                                { id: 'month', label: 'Bulan Ini' }
                            ].map(f => (
                                <button
                                    key={f.id}
                                    onClick={() => handleFilterChange(f.id)}
                                    className={`px-3 py-1.5 text-xs font-semibold rounded-lg whitespace-nowrap transition ${
                                        filter === f.id
                                            ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-400'
                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    }`}
                                >
                                    {f.label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Sales Chart */}
                {mode === 'omset' && trends && trends.length > 0 && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="mb-4 font-bold text-slate-800 dark:text-white/95 flex items-center gap-2">
                            <Icon name="trending_up" className="text-blue-500" /> Tren Omset 
                            {filter === 'today' ? ' (Hari Ini)' : filter === 'week' ? ' (Minggu Ini)' : filter === 'month' ? ' (Bulan Ini)' : ' (Tahun Ini)'}
                        </h3>
                        <div className="w-full">
                            <svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} className="w-full h-auto overflow-visible">
                                <line x1="20" y1="10" x2={chartWidth - 20} y2="10" stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight / 2} x2={chartWidth - 20} y2={chartHeight / 2} stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="20" y1={chartHeight - 10} x2={chartWidth - 20} y2={chartHeight - 10} stroke="#cbd5e1" />
                                
                                {(() => {
                                    const maxVal = Math.max(...trends.map(t => t.total), 1);
                                    const pts = trends.map((t, idx) => ({
                                        x: (idx / (trends.length - 1)) * (chartWidth - 40) + 20,
                                        y: chartHeight - (t.total / maxVal) * (chartHeight - 20) - 10,
                                        ...t
                                    }));
                                    const path = pts.map(p => `${p.x},${p.y}`).join(' ');
                                    return (
                                        <>
                                            <path d={`M 20,${chartHeight - 10} L ${path} L ${pts[pts.length - 1].x},${chartHeight - 10} Z`} fill="rgba(59, 130, 246, 0.1)" />
                                            <polyline fill="none" stroke="#3b82f6" strokeWidth="3" points={path} />
                                            {pts.map((p, idx) => (
                                                <g key={idx} className="group">
                                                    <circle cx={p.x} cy={p.y} r="5" fill="#3b82f6" className="cursor-pointer" />
                                                    <text x={p.x} y={chartHeight - 2} textAnchor="middle" className="text-[8px] fill-slate-400 font-semibold">{p.date}</text>
                                                    <text x={p.x} y={p.y - 10} textAnchor="middle" className="hidden group-hover:block text-[9px] font-bold fill-slate-850">{formatQty(p.total / 1000)}k</text>
                                                </g>
                                            ))}
                                        </>
                                    );
                                })()}
                            </svg>
                        </div>
                    </div>
                )}

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

                {/* Tabs for Omset Mode */}
                {mode === 'omset' && (
                    <div className="flex gap-2">
                        <button 
                            onClick={() => setActiveTab('transactions')}
                            className={`flex-1 py-2 text-sm font-bold rounded-xl transition flex justify-center items-center gap-1.5 ${activeTab === 'transactions' ? 'bg-slate-800 text-white shadow-md dark:bg-white dark:text-slate-900' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50 dark:bg-white/[0.02] dark:border-gray-800'}`}
                        >
                            <Icon name="receipt" className="text-[16px]" /> Transaksi
                        </button>
                        <button 
                            onClick={() => setActiveTab('shifts')}
                            className={`flex-1 py-2 text-sm font-bold rounded-xl transition flex justify-center items-center gap-1.5 ${activeTab === 'shifts' ? 'bg-amber-500 text-white shadow-md' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50 dark:bg-white/[0.02] dark:border-gray-800'}`}
                        >
                            <Icon name="assessment" className="text-[16px]" /> Laci Kasir (Shift)
                        </button>
                    </div>
                )}

                {/* List Container */}
                <div className="space-y-3">
                    {mode === 'stock' ? (
                        filteredTransactions.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-20 text-slate-400 bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] rounded-2xl">
                                <Icon name="search_off" className="text-[48px]" />
                                <p className="text-sm">Tidak ada data yang cocok dengan pencarian.</p>
                            </div>
                        ) : filteredTransactions.map((row, idx) => (
                            <StockItemCard key={idx} row={row} type={getStockCardType()} />
                        ))
                    ) : activeTab === 'transactions' ? (
                        filteredTransactions.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-20 text-slate-400 bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] rounded-2xl">
                                <Icon name="search_off" className="text-[48px]" />
                                <p className="text-sm">Tidak ada transaksi yang cocok.</p>
                            </div>
                        ) : filteredTransactions.map((row, idx) => (
                            <TransactionCard key={idx} row={row} showBranch={unit === 'all_transactions'} />
                        ))
                    ) : (
                        filteredShifts.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-20 text-slate-400 bg-white border border-slate-200 dark:border-gray-800 dark:bg-white/[0.02] rounded-2xl">
                                <Icon name="search_off" className="text-[48px]" />
                                <p className="text-sm">Tidak ada shift yang cocok.</p>
                            </div>
                        ) : filteredShifts.map((row, idx) => (
                            <ShiftCard key={idx} row={row} showBranch={unit === 'all_transactions'} />
                        ))
                    )}
                </div>
            </div>
        </OwnerLayout>
    );
}
