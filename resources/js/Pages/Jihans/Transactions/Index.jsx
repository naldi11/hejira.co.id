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
                <Link href={route('jihans.pos.index')} className="flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Kasir
                </Link>
                <form onSubmit={reload} className="flex w-full gap-2 sm:w-auto">
                    <div className="relative flex-1 sm:w-64">
                        <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari No. Transaksi atau Pelanggan..."
                            className="w-full rounded-lg border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                    </div>
                    <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                    {filters.search && <Link href={route('jihans.transactions.index')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
                </form>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-6 py-4 font-medium">Tanggal</th>
                                <th className="px-6 py-4 font-medium">No. Transaksi</th>
                                <th className="px-6 py-4 font-medium">Pelanggan</th>
                                <th className="px-6 py-4 text-right font-medium">Total Tagihan</th>
                                <th className="px-6 py-4 font-medium">Kasir</th>
                                <th className="px-6 py-4 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? <SkeletonTableRows rows={8} columns={6} />
                                : transactions.data.length === 0 ? <EmptyState colSpan={6} icon="receipt_long" message="Belum ada riwayat transaksi." />
                                : transactions.data.map((t) => {
                                    const [date, time] = dateParts(t.created_at);
                                    return (
                                        <tr key={t.id} className="transition-colors hover:bg-gray-50">
                                            <td className="whitespace-nowrap px-6 py-4 text-gray-600">{date}<br /><span className="text-xs text-gray-400">{time}</span></td>
                                            <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800">{t.transaction_number}</span></td>
                                            <td className="px-6 py-4">
                                                <p className="font-medium text-gray-800">{t.customer_name}</p>
                                                <p className="text-xs capitalize text-gray-500">{t.customer_type}</p>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <p className="font-bold text-gray-900">{formatRupiah(t.grand_total)}</p>
                                                <p className="mt-0.5 text-xs capitalize text-green-600">{t.status}</p>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500">{t.creator ?? '-'}</td>
                                            <td className="px-6 py-4 text-right">
                                                <a href={route('jihans.transactions.show', t.id)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-medium text-orange-600 transition-colors hover:bg-orange-100">
                                                    <Icon name="receipt" className="text-[16px]" /> Struk
                                                </a>
                                            </td>
                                        </tr>
                                    );
                                })}
                        </tbody>
                    </table>
                </div>
                {transactions.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={transactions.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
