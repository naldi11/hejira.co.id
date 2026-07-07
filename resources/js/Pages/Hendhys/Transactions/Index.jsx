import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatRupiah } from '@/lib/format';
import { FilterTransactionsModal } from '@/Components/FilterTransactionsModal';

const route = window.route;

export default function HendhysTransactionsIndex({ transactions, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const [showFilterModal, setShowFilterModal] = useState(false);

    const hasActiveFilters = !!(filters.start_date || filters.end_date || filters.shift_id);

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('hendhys.transactions.index'), 
            { 
                search: search || undefined,
                start_date: filters.start_date || undefined,
                end_date: filters.end_date || undefined,
                shift_id: filters.shift_id || undefined
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['transactions', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) }
        );
    };

    const handleApplyFilter = (newFilters) => {
        setShowFilterModal(false);
        router.get(route('hendhys.transactions.index'),
            { 
                search: search || undefined,
                ...newFilters
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['transactions', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) }
        );
    };

    return (
        <HendhysLayout pageTitle="Riwayat Transaksi">
            <Head title="Riwayat Transaksi" />
            <div className="space-y-6">
                <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Riwayat Transaksi</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Daftar semua transaksi penjualan di kasir Hendhys</p>
                    </div>
                    <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                        <Link href={route('hendhys.pos.index')} className="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                            <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Kasir
                        </Link>
                    </div>
                </div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" />
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari no transaksi atau pelanggan..."
                                    className="w-full rounded-lg border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                            <button 
                                type="button" 
                                onClick={() => setShowFilterModal(true)}
                                className={`relative flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium border ${hasActiveFilters ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'}`}
                            >
                                <Icon name="filter_list" className="mr-1.5 text-[18px]" /> Filter
                                {hasActiveFilters && (
                                    <span className="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full bg-amber-500"></span>
                                )}
                            </button>
                            {(search || hasActiveFilters) && (
                                <Link href={route('hendhys.transactions.index')} className="rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-gray-200 dark:text-red-400 dark:bg-gray-800 dark:hover:bg-gray-700">Reset</Link>
                            )}
                        </form>
                    </div>
                    <FilterTransactionsModal 
                        show={showFilterModal} 
                        onClose={() => setShowFilterModal(false)}
                        filters={filters}
                        onApply={handleApplyFilter}
                        entity="hendhys"
                    />
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Transaksi</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 font-medium">Pelanggan</th>
                                    <th className="px-6 py-4 text-right font-medium">Total</th>
                                    <th className="px-6 py-4 text-center font-medium">Status</th>
                                    <th className="px-6 py-4 font-medium">Kasir</th>
                                    <th className="px-6 py-4 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={7} />
                                    : transactions.data.length === 0 ? <EmptyState colSpan={7} icon="receipt_long" message="Belum ada transaksi." />
                                    : transactions.data.map((t) => (
                                        <tr key={t.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{t.transaction_number}</td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">
                                                <div>{t.date}</div>
                                                <div className="text-xs text-gray-400">{t.time}</div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{t.customer_name}</td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-800 dark:text-white/90">{formatRupiah(t.grand_total)}</td>
                                            <td className="px-6 py-4 text-center"><span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${t.status === 'paid' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'}`}>{t.status}</span></td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{t.creator}</td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <a href={`${route('hendhys.transactions.show', t.id)}?paper_size=58`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">
                                                        <Icon name="receipt" className="text-[16px]" /> Struk 58mm
                                                    </a>
                                                    <a href={`${route('hendhys.transactions.show', t.id)}?paper_size=80`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">
                                                        <Icon name="receipt" className="text-[16px]" /> Struk 80mm
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {transactions.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={transactions.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
