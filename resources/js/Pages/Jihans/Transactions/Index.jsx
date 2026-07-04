import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatRupiah } from '@/lib/format';

const route = window.route;

function dateParts(iso) {
    if (!iso) return ['-', ''];
    const d = new Date(iso);
    return [d.toLocaleDateString('id-ID'), d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })];
}

export default function JihansTransactionsIndex({ transactions, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.transactions.index'),
            { search: search || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['transactions', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Riwayat Transaksi Kasir">
            <Head title="Riwayat Transaksi" />

            <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Riwayat Transaksi</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Daftar semua transaksi penjualan di kasir Jihan's Food</p>
                </div>
                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <Link href={route('jihans.pos.index')} className="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                        <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Kasir
                    </Link>
                    <form onSubmit={reload} className="flex flex-1 gap-2 sm:flex-initial">
                        <div className="relative flex-1 sm:w-64">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari No. Transaksi atau Pelanggan..."
                                className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 py-2 pl-9 pr-4 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:placeholder-gray-500 transition-all"
                            />
                        </div>
                        <button type="submit" className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            Cari
                        </button>
                        {filters.search && (
                            <Link href={route('jihans.transactions.index')} className="rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 transition-colors">
                                Reset
                            </Link>
                        )}
                    </form>
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                            <tr>
                                <th className="px-6 py-4 font-semibold">Tanggal</th>
                                <th className="px-6 py-4 font-semibold">No. Transaksi</th>
                                <th className="px-6 py-4 font-semibold">Pelanggan</th>
                                <th className="px-6 py-4 text-right font-semibold">Total Tagihan</th>
                                <th className="px-6 py-4 font-semibold">Kasir</th>
                                <th className="px-6 py-4 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                            {loading ? <SkeletonTableRows rows={8} columns={6} />
                                : transactions.data.length === 0 ? <EmptyState colSpan={6} icon="receipt_long" message="Belum ada riwayat transaksi." />
                                : transactions.data.map((t) => {
                                    const [date, time] = dateParts(t.created_at);
                                    return (
                                        <tr key={t.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="whitespace-nowrap px-6 py-4 text-gray-600 dark:text-gray-300">{date}<br /><span className="text-xs text-gray-400 dark:text-gray-500">{time}</span></td>
                                            <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800 dark:text-white/90">{t.transaction_number}</span></td>
                                            <td className="px-6 py-4">
                                                <p className="font-medium text-gray-800 dark:text-white/90">{t.customer_name}</p>
                                                <p className="text-xs capitalize text-gray-500 dark:text-gray-400">{t.customer_type}</p>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <p className="font-bold text-gray-950 dark:text-white">{formatRupiah(t.grand_total)}</p>
                                                <p className="mt-0.5 text-xs capitalize text-green-600 dark:text-green-400">{t.status}</p>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{t.creator ?? '-'}</td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route('jihans.pos.edit', t.id)} className="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                                        <Icon name="edit" className="text-[16px]" /> Edit
                                                    </Link>
                                                    <a href={route('jihans.transactions.show', t.id)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-medium text-orange-600 transition-colors hover:bg-orange-100 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-400 dark:hover:bg-orange-500/20">
                                                        <Icon name="receipt" className="text-[16px]" /> Struk
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                        </tbody>
                    </table>
                </div>
                {transactions.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={transactions.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
