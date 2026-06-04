import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatRupiah } from '@/lib/format';

const route = window.route;

export default function HendhysTransactionsIndex({ transactions, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('hendhys.transactions.index'), { search: search || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['transactions', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <HendhysLayout pageTitle="Riwayat Transaksi">
            <Head title="Riwayat Transaksi" />
            <div className="space-y-6">
                <h2 className="text-2xl font-bold tracking-tight text-gray-800">Riwayat Transaksi</h2>
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari no transaksi atau pelanggan..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                            {search && <Link href={route('hendhys.transactions.index')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
                        </form>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Transaksi</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 font-medium">Pelanggan</th>
                                    <th className="px-6 py-4 text-right font-medium">Total</th>
                                    <th className="px-6 py-4 text-center font-medium">Status</th>
                                    <th className="px-6 py-4 font-medium">Kasir</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : transactions.data.length === 0 ? <EmptyState colSpan={6} icon="receipt_long" message="Belum ada transaksi." />
                                    : transactions.data.map((t) => (
                                        <tr key={t.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 font-bold text-gray-800">{t.transaction_number}</td>
                                            <td className="px-6 py-4 text-gray-600">{t.date}</td>
                                            <td className="px-6 py-4 text-gray-600">{t.customer_name}</td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-800">{formatRupiah(t.grand_total)}</td>
                                            <td className="px-6 py-4 text-center"><span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${t.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}`}>{t.status}</span></td>
                                            <td className="px-6 py-4 text-gray-500">{t.creator}</td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {transactions.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={transactions.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
