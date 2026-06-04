import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';

const route = window.route;

export default function HendhysPendingIndex({ pendings, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('hendhys.pending.index'), { search: search || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['pendings', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <HendhysLayout pageTitle="Transaksi Pending">
            <Head title="Transaksi Pending" />
            <div className="space-y-6">
                <h2 className="text-2xl font-bold tracking-tight text-gray-800">Transaksi Pending (Hold)</h2>
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari no pending atau pelanggan..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                        </form>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Pending</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 font-medium">Pelanggan</th>
                                    <th className="px-6 py-4 font-medium">Kasir</th>
                                    <th className="px-6 py-4 text-center font-medium">Item</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : pendings.data.length === 0 ? <EmptyState colSpan={5} icon="schedule" message="Tidak ada transaksi pending." />
                                    : pendings.data.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 font-bold text-gray-800">{p.pending_number}</td>
                                            <td className="px-6 py-4 text-gray-600">{p.date}</td>
                                            <td className="px-6 py-4 text-gray-600">{p.customer_name}</td>
                                            <td className="px-6 py-4 text-gray-500">{p.creator}</td>
                                            <td className="px-6 py-4 text-center"><span className="rounded-lg bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">{p.details_count ?? '-'}</span></td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {pendings.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={pendings.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
