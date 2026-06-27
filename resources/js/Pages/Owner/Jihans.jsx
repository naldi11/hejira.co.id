import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import { formatRupiah, formatQty, formatDate } from '@/lib/format';

export default function OwnerJihans({ stats, transactions, stocks, filters }) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        status: filters.status ?? '',
    });

    const [stockSearch, setStockSearch] = useState('');

    const handleFilter = (e) => {
        e?.preventDefault();
        router.get(route('owner.jihans'), {
            search: form.search || undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            status: form.status || undefined,
        }, { preserveState: true, replace: true });
    };

    const filteredStocks = stocks.filter(s => 
        s.name.toLowerCase().includes(stockSearch.toLowerCase()) || 
        s.code.toLowerCase().includes(stockSearch.toLowerCase())
    );

    return (
        <OwnerLayout pageTitle="Dashboard Jihan's Food">
            <Head title="Owner — Jihan's" />

            <div className="space-y-6">
                {/* Stats cards */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-950/20 dark:text-orange-400"><Icon name="payments" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Pendapatan</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-white/95">{formatRupiah(stats.total_revenue)}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-950/20 dark:text-green-400"><Icon name="today" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Pendapatan Hari Ini</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-white/95">{formatRupiah(stats.revenue_today)}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/20 dark:text-indigo-400"><Icon name="factory" className="text-[22px]" /></div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Produksi Hari Ini</p>
                        <p className="mt-1 text-2xl font-bold text-slate-800 dark:text-white/95">{formatQty(stats.production_today)} <span className="text-sm font-normal text-slate-400">batch</span></p>
                    </div>
                </div>

                {/* Stock List Section */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <div>
                            <h3 className="font-bold text-slate-800 dark:text-white/95 text-lg flex items-center gap-2"><Icon name="inventory" className="text-orange-500" /> Stok Produk Jihan's Food</h3>
                            <p className="text-xs text-slate-400">Daftar stok tersedia seluruh produk yang terdaftar</p>
                        </div>
                        <div className="relative min-w-[240px]">
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
                                {filteredStocks.length === 0 ? <EmptyState colSpan={3} icon="inventory" message="Produk tidak ditemukan." />
                                    : filteredStocks.map((s, idx) => (
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

                {/* Transaction History Section */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 className="font-bold text-slate-800 dark:text-white/95 text-lg flex items-center gap-2 mb-4"><Icon name="receipt_long" className="text-orange-500" /> Riwayat Transaksi Jihan's Food</h3>

                    <form onSubmit={handleFilter} className="flex flex-wrap gap-3 mb-4">
                        <input 
                            type="text"
                            placeholder="Cari No. Transaksi/Pelanggan..."
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
            </div>
        </OwnerLayout>
    );
}
