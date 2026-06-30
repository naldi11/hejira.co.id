import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { formatRupiah, formatQty, formatDate } from '@/lib/format';

export default function OwnerJihans({ stats, transactions, stocks, gudangStocks, movements, purchaseOrders, filters }) {
    const [activeTab, setActiveTab] = useState('stocks'); // stocks, transactions, gudang_stocks, gudang_movements, gudang_po
    
    // Jihans Transaction Filters State
    const [form, setForm] = useState({
        search: filters.search ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        status: filters.status ?? '',
    });

    // Jihans Stock Search State
    const [stockSearch, setStockSearch] = useState('');

    // Gudang Filters State
    const [gudangFilters, setGudangFilters] = useState({
        stock_search: filters.stock_search ?? '',
        movement_search: filters.movement_search ?? '',
        po_search: filters.po_search ?? '',
    });

    const handleJihansFilter = (e) => {
        e?.preventDefault();
        router.get(route('owner.jihans'), {
            search: form.search || undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            status: form.status || undefined,
        }, { preserveState: true, replace: true });
    };

    const handleGudangSearch = (e, tab) => {
        e?.preventDefault();
        router.get(route('owner.jihans'), {
            stock_search: tab === 'gudang_stocks' ? gudangFilters.stock_search : undefined,
            movement_search: tab === 'gudang_movements' ? gudangFilters.movement_search : undefined,
            po_search: tab === 'gudang_po' ? gudangFilters.po_search : undefined,
        }, { preserveState: true, replace: true });
    };

    const filteredJihansStocks = stocks.filter(s => 
        s.name.toLowerCase().includes(stockSearch.toLowerCase()) || 
        s.code.toLowerCase().includes(stockSearch.toLowerCase())
    );

    return (
        <OwnerLayout pageTitle="Dashboard Jihan's Food & Gudang">
            <Head title="Owner — Jihan's & Gudang" />

            <div className="space-y-6">
                
                {/* Penjualan Jihan's Food Section */}
                <div>
                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                        <Icon name="payments" className="text-[16px] text-orange-500" /> Penjualan & Produksi Jihan's Food
                    </h4>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-455">Total Pendapatan</p>
                            <p className="mt-1 text-xl font-bold text-slate-800 dark:text-white/95">{formatRupiah(stats.total_revenue)}</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-455">Pendapatan Hari Ini</p>
                            <p className="mt-1 text-xl font-bold text-slate-800 dark:text-white/95">{formatRupiah(stats.revenue_today)}</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-455">Produksi Hari Ini</p>
                            <p className="mt-1 text-xl font-bold text-slate-800 dark:text-white/95">{formatQty(stats.production_today)} <span className="text-sm font-normal text-slate-400">batch</span></p>
                        </div>
                    </div>
                </div>

                {/* Logistik Gudang Section */}
                <div>
                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                        <Icon name="warehouse" className="text-[16px] text-teal-600" /> Logistik & PO Gudang
                    </h4>
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/20"><Icon name="shopping_cart" className="text-[18px]" /></div>
                            <p className="text-xl font-bold text-slate-850 dark:text-white">{formatQty(stats.po_pending)}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">PO Berjalan</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-950/20"><Icon name="task_alt" className="text-[18px]" /></div>
                            <p className="text-xl font-bold text-slate-850 dark:text-white">{formatQty(stats.po_received)}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">PO Selesai</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-950/20"><Icon name="input" className="text-[18px]" /></div>
                            <p className="text-xl font-bold text-slate-850 dark:text-white">{formatQty(stats.receive_month)}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">Penerimaan Bulan Ini</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/20"><Icon name="output" className="text-[18px]" /></div>
                            <p className="text-xl font-bold text-slate-850 dark:text-white">{formatQty(stats.transfer_month)}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">Distribusi Bulan Ini</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/20"><Icon name="pending_actions" className="text-[18px]" /></div>
                            <p className="text-xl font-bold text-slate-850 dark:text-white">{formatQty(stats.pending_requests)}</p>
                            <p className="text-[10px] text-slate-400 mt-0.5">Approval Request</p>
                        </div>
                    </div>
                </div>

                {/* Tabs & Content Section */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    
                    {/* Navigation Tabs */}
                    <div className="flex flex-wrap gap-2 border-b border-slate-100 dark:border-gray-800 pb-4 mb-5">
                        {[
                            { id: 'stocks', label: "Stok Jihan's Food", icon: 'cookie', color: 'bg-orange-500' },
                            { id: 'transactions', label: "Transaksi Jihan's Food", icon: 'receipt_long', color: 'bg-orange-500' },
                            { id: 'gudang_stocks', label: 'Stok Sisa Gudang', icon: 'inventory', color: 'bg-teal-600' },
                            { id: 'gudang_movements', label: 'Riwayat Mutasi Gudang', icon: 'swap_horiz', color: 'bg-teal-600' },
                            { id: 'gudang_po', label: 'PO Supplier Gudang', icon: 'shopping_bag', color: 'bg-teal-600' },
                        ].map(t => (
                            <button
                                key={t.id}
                                onClick={() => setActiveTab(t.id)}
                                className={`flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg transition ${activeTab === t.id ? `${t.color} text-white` : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                            >
                                <Icon name={t.icon} className="text-[16px]" /> {t.label}
                            </button>
                        ))}
                    </div>

                    {/* ── Tab 1: Jihans Stocks ── */}
                    {activeTab === 'stocks' && (
                        <div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                <div>
                                    <h4 className="font-bold text-slate-800 dark:text-white text-base">Stok Tersedia Jihan's Food</h4>
                                    <p className="text-xs text-slate-400">Daftar sisa stok yang ada di unit Jihan's Food</p>
                                </div>
                                <div className="relative min-w-[245px]">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                                    <input 
                                        type="text"
                                        placeholder="Cari produk..."
                                        value={stockSearch}
                                        onChange={(e) => setStockSearch(e.target.value)}
                                        className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                    />
                                </div>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">Kode</th>
                                            <th className="px-4 py-3">Nama Produk</th>
                                            <th className="px-4 py-3 text-right">Stok Tersedia</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {filteredJihansStocks.length === 0 ? <EmptyState colSpan={3} icon="inventory" message="Stok tidak ditemukan." />
                                            : filteredJihansStocks.map((s, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-500">{s.code}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white/90">{s.name}</td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatQty(s.quantity)}</td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* ── Tab 2: Jihans Transactions ── */}
                    {activeTab === 'transactions' && (
                        <div>
                            <form onSubmit={handleJihansFilter} className="flex flex-wrap gap-3 mb-4">
                                <input 
                                    type="text"
                                    placeholder="Cari transaksi..."
                                    value={form.search}
                                    onChange={(e) => setForm({...form, search: e.target.value})}
                                    className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white min-w-[200px]"
                                />
                                <select 
                                    value={form.status}
                                    onChange={(e) => setForm({...form, status: e.target.value})}
                                    className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-white dark:bg-gray-850 dark:text-white"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="paid">Paid (Lunas)</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <input 
                                    type="date"
                                    value={form.date_from}
                                    onChange={(e) => setForm({...form, date_from: e.target.value})}
                                    className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                                <input 
                                    type="date"
                                    value={form.date_to}
                                    onChange={(e) => setForm({...form, date_to: e.target.value})}
                                    className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                                <button type="submit" className="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg">Filter</button>
                            </form>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">No. Transaksi</th>
                                            <th className="px-4 py-3">Pelanggan</th>
                                            <th className="px-4 py-3">Tanggal</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                            <th className="px-4 py-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {transactions.data.length === 0 ? <EmptyState colSpan={5} icon="receipt_long" message="Tidak ada data transaksi." />
                                            : transactions.data.map((t) => (
                                                <tr key={t.id} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-medium text-slate-800 dark:text-white">{t.transaction_number}</td>
                                                    <td className="px-4 py-3 text-slate-600 dark:text-gray-300">{t.customer_name}</td>
                                                    <td className="px-4 py-3 text-slate-500">{formatDate(t.date)}</td>
                                                    <td className="px-4 py-3 text-center">
                                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${t.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-950/20 dark:text-green-400' : t.status === 'pending' ? 'bg-amber-100 text-amber-850 dark:bg-amber-950/20 dark:text-amber-400' : 'bg-red-100 text-red-800 dark:bg-red-950/20 dark:text-red-400'}`}>
                                                            {t.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{formatRupiah(t.grand_total)}</td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                            {transactions.meta?.links && <div className="mt-4"><Pagination links={transactions.meta.links} /></div>}
                        </div>
                    )}

                    {/* ── Tab 3: Gudang Stocks ── */}
                    {activeTab === 'gudang_stocks' && (
                        <div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                <div>
                                    <h4 className="font-bold text-slate-800 dark:text-white text-base">Stok Bahan Baku Gudang</h4>
                                    <p className="text-xs text-slate-400">Sisa stok bahan baku yang disimpan di Gudang Tempua</p>
                                </div>
                                <form onSubmit={(e) => handleGudangSearch(e, 'gudang_stocks')} className="relative min-w-[245px]">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                                    <input
                                        type="text"
                                        placeholder="Cari sisa stok..."
                                        value={gudangFilters.stock_search}
                                        onChange={(e) => setGudangFilters({...gudangFilters, stock_search: e.target.value})}
                                        className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                    />
                                </form>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">Kode</th>
                                            <th className="px-4 py-3">Nama Produk</th>
                                            <th className="px-4 py-3 text-right">Stok Gudang</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {gudangStocks.data.length === 0 ? <EmptyState colSpan={3} icon="inventory" message="Stok tidak ditemukan." />
                                            : gudangStocks.data.map((s, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-500">{s.product?.code ?? '-'}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white/90">{s.product?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatQty(s.quantity)}</td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                            {gudangStocks.meta?.links && <div className="mt-4"><Pagination links={gudangStocks.meta.links} /></div>}
                        </div>
                    )}

                    {/* ── Tab 4: Gudang Movements ── */}
                    {activeTab === 'gudang_movements' && (
                        <div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                <div>
                                    <h4 className="font-bold text-slate-800 dark:text-white text-base">Riwayat Mutasi Gudang</h4>
                                    <p className="text-xs text-slate-400">Log mutasi keluar masuk bahan baku Gudang</p>
                                </div>
                                <form onSubmit={(e) => handleGudangSearch(e, 'gudang_movements')} className="relative min-w-[245px]">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                                    <input
                                        type="text"
                                        placeholder="Cari mutasi..."
                                        value={gudangFilters.movement_search}
                                        onChange={(e) => setGudangFilters({...gudangFilters, movement_search: e.target.value})}
                                        className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                    />
                                </form>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">Tanggal</th>
                                            <th className="px-4 py-3">Nama Produk</th>
                                            <th className="px-4 py-3">Tipe</th>
                                            <th className="px-4 py-3 text-right">Qty</th>
                                            <th className="px-4 py-3">Catatan</th>
                                            <th className="px-4 py-3">User</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {movements.data.length === 0 ? <EmptyState colSpan={6} icon="swap_horiz" message="Tidak ada riwayat mutasi." />
                                            : movements.data.map((m, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 text-slate-500 text-xs">{formatDate(m.date ?? m.created_at)}</td>
                                                    <td className="px-4 py-3 font-semibold text-slate-850 dark:text-white/90">{m.product?.name ?? '-'}</td>
                                                    <td className="px-4 py-3">
                                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${m.type === 'in' || m.type === 'receiving' ? 'bg-green-100 text-green-800 dark:bg-green-950/20' : 'bg-red-100 text-red-800 dark:bg-red-950/20'}`}>
                                                            {m.type}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{formatQty(m.quantity)}</td>
                                                    <td className="px-4 py-3 text-slate-500 max-w-[200px] truncate">{m.notes ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-600 dark:text-gray-300 text-xs font-bold">{m.creator?.name ?? '-'}</td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                            {movements.meta?.links && <div className="mt-4"><Pagination links={movements.meta.links} /></div>}
                        </div>
                    )}

                    {/* ── Tab 5: Gudang Purchase Orders ── */}
                    {activeTab === 'gudang_po' && (
                        <div>
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                <div>
                                    <h4 className="font-bold text-slate-800 dark:text-white text-base">Purchase Order Supplier</h4>
                                    <p className="text-xs text-slate-400">Daftar pemesanan barang dari Supplier</p>
                                </div>
                                <form onSubmit={(e) => handleGudangSearch(e, 'gudang_po')} className="relative min-w-[245px]">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                                    <input
                                        type="text"
                                        placeholder="Cari PO / supplier..."
                                        value={gudangFilters.po_search}
                                        onChange={(e) => setGudangFilters({...gudangFilters, po_search: e.target.value})}
                                        className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                    />
                                </form>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">No. PO</th>
                                            <th className="px-4 py-3">Supplier</th>
                                            <th className="px-4 py-3">Tanggal</th>
                                            <th className="px-4 py-3 text-right">Total Estimasi</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {purchaseOrders.data.length === 0 ? <EmptyState colSpan={5} icon="shopping_bag" message="Tidak ada riwayat PO." />
                                            : purchaseOrders.data.map((p, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-medium text-slate-800 dark:text-white">{p.po_number}</td>
                                                    <td className="px-4 py-3 font-bold text-slate-700 dark:text-gray-355">{p.supplier?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-500">{formatDate(p.date)}</td>
                                                    <td className="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">{formatRupiah(p.total_amount ?? p.grand_total ?? 0)}</td>
                                                    <td className="px-4 py-3 text-center">
                                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${p.status === 'received' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}`}>
                                                            {p.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                            {purchaseOrders.meta?.links && <div className="mt-4"><Pagination links={purchaseOrders.meta.links} /></div>}
                        </div>
                    )}

                </div>
            </div>
        </OwnerLayout>
    );
}
