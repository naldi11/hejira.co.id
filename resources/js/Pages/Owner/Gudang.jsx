import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { formatQty, formatDate, formatRupiah } from '@/lib/format';

const COUNTS = [
    { key: 'po_pending', label: 'PO Berjalan', icon: 'shopping_cart', color: 'bg-blue-100 text-blue-600' },
    { key: 'po_received', label: 'PO Selesai', icon: 'task_alt', color: 'bg-green-100 text-green-600' },
    { key: 'receive_month', label: 'Penerimaan Bulan Ini', icon: 'input', color: 'bg-teal-100 text-teal-600' },
    { key: 'transfer_month', label: 'Distribusi Bulan Ini', icon: 'output', color: 'bg-indigo-100 text-indigo-600' },
    { key: 'pending_requests', label: 'Request Butuh Approval', icon: 'pending_actions', color: 'bg-amber-100 text-amber-600' },
];

export default function OwnerGudang({ stats, stocks, movements, purchaseOrders, filters }) {
    const [activeTab, setActiveTab] = useState('stocks'); // stocks, movements, po
    const [searchFilters, setSearchFilters] = useState({
        stock_search: filters.stock_search ?? '',
        movement_search: filters.movement_search ?? '',
        po_search: filters.po_search ?? '',
    });

    const handleSearch = (e, tab) => {
        e.preventDefault();
        router.get(route('owner.gudang'), {
            stock_search: activeTab === 'stocks' ? searchFilters.stock_search : undefined,
            movement_search: activeTab === 'movements' ? searchFilters.movement_search : undefined,
            po_search: activeTab === 'po' ? searchFilters.po_search : undefined,
        }, { preserveState: true, replace: true });
    };

    return (
        <OwnerLayout pageTitle="Dashboard Gudang Tempua">
            <Head title="Owner — Gudang" />

            <div className="space-y-6">
                {/* Stats Counters */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    {COUNTS.map((c) => (
                        <div key={c.key} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className={`mb-3 flex h-10 w-10 items-center justify-center rounded-lg ${c.color}`}><Icon name={c.icon} className="text-[20px]" /></div>
                            <p className="text-2xl font-bold text-slate-800 dark:text-white/95">{formatQty(stats[c.key])}</p>
                            <p className="mt-1 text-xs text-slate-400">{c.label}</p>
                        </div>
                    ))}
                </div>

                {/* Tabs & Content */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 dark:border-gray-850 pb-4 mb-4">
                        <div className="flex gap-2">
                            {[
                                { id: 'stocks', label: 'Stok Sisa', icon: 'inventory' },
                                { id: 'movements', label: 'Riwayat Mutasi', icon: 'swap_horiz' },
                                { id: 'po', label: 'PO Supplier', icon: 'shopping_bag' }
                            ].map(tab => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg transition ${activeTab === tab.id ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-650 dark:bg-gray-850 dark:text-gray-300'}`}
                                >
                                    <Icon name={tab.icon} className="text-[18px]" /> {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Search Input based on active tab */}
                        <form onSubmit={(e) => handleSearch(e, activeTab)} className="relative min-w-[260px]">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]" />
                            {activeTab === 'stocks' && (
                                <input
                                    type="text"
                                    placeholder="Cari stok..."
                                    value={searchFilters.stock_search}
                                    onChange={(e) => setSearchFilters({...searchFilters, stock_search: e.target.value})}
                                    className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                            )}
                            {activeTab === 'movements' && (
                                <input
                                    type="text"
                                    placeholder="Cari mutasi..."
                                    value={searchFilters.movement_search}
                                    onChange={(e) => setSearchFilters({...searchFilters, movement_search: e.target.value})}
                                    className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                            )}
                            {activeTab === 'po' && (
                                <input
                                    type="text"
                                    placeholder="Cari PO / supplier..."
                                    value={searchFilters.po_search}
                                    onChange={(e) => setSearchFilters({...searchFilters, po_search: e.target.value})}
                                    className="w-full pl-9 pr-4 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                                />
                            )}
                        </form>
                    </div>

                    {/* Active Tab Content */}
                    <div className="custom-scrollbar overflow-x-auto">
                        {activeTab === 'stocks' && (
                            <>
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                        <tr>
                                            <th className="px-4 py-3">Kode</th>
                                            <th className="px-4 py-3">Nama Produk</th>
                                            <th className="px-4 py-3 text-right">Stok Gudang</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                        {stocks.data.length === 0 ? <EmptyState colSpan={3} icon="inventory" message="Stok tidak ditemukan." />
                                            : stocks.data.map((s, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-4 py-3 font-mono font-bold text-xs text-slate-500">{s.product?.code ?? '-'}</td>
                                                    <td className="px-4 py-3 font-medium text-slate-800 dark:text-white/90">{s.product?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatQty(s.quantity)}</td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                                {stocks.meta?.links && <div className="mt-4"><Pagination links={stocks.meta.links} /></div>}
                            </>
                        )}

                        {activeTab === 'movements' && (
                            <>
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
                                {movements.meta?.links && <div className="mt-4"><Pagination links={movements.meta.links} /></div>}
                            </>
                        )}

                        {activeTab === 'po' && (
                            <>
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
                                                    <td className="px-4 py-3 font-bold text-slate-700 dark:text-gray-350">{p.supplier?.name ?? '-'}</td>
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
                                {purchaseOrders.meta?.links && <div className="mt-4"><Pagination links={purchaseOrders.meta.links} /></div>}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
