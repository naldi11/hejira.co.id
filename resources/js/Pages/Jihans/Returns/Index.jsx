import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;

export default function JihansReturnsIndex({ returns, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.returns-to-gudang.index'),
            { search: form.search || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['returns', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Return ke Gudang Utama">
            <Head title="Return ke Gudang" />

            <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Return ke Gudang</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Pengembalian bahan baku / barang dari Jihan's ke Gudang Utama</p>
                </div>
                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <Link href={route('jihans.returns-to-gudang.create')} className="inline-flex items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                        <Icon name="add" className="text-[18px]" /> Buat Retur Baru
                    </Link>
                    <form onSubmit={reload} className="flex flex-wrap flex-1 gap-2 sm:flex-initial">
                        <select
                            value={form.status}
                            onChange={(e) => setForm({ ...form, status: e.target.value })}
                            className="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                        >
                            <option value="" className="dark:bg-gray-800">Semua Status</option>
                            <option value="sent" className="dark:bg-gray-800">Dikirim</option>
                            <option value="received" className="dark:bg-gray-800">Diterima</option>
                        </select>
                        <div className="relative flex-1 sm:w-56">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                            <input
                                type="text"
                                value={form.search}
                                onChange={(e) => setForm({ ...form, search: e.target.value })}
                                placeholder="No. Retur..."
                                className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 py-2 pl-9 pr-4 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:placeholder-gray-500 transition-all"
                            />
                        </div>
                        <button type="submit" className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                            Filter
                        </button>
                    </form>
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                            <tr>
                                <th className="px-6 py-4 font-semibold">No. Retur</th>
                                <th className="px-6 py-4 font-semibold">Tanggal</th>
                                <th className="px-6 py-4 text-center font-semibold">Jml Item</th>
                                <th className="px-6 py-4 text-center font-semibold">Status</th>
                                <th className="px-6 py-4 font-semibold">Penerima</th>
                                <th className="px-6 py-4 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                            {loading ? <SkeletonTableRows rows={6} columns={6} />
                                : returns.data.length === 0 ? <EmptyState colSpan={6} icon="assignment_return" message="Belum ada data retur ke gudang." />
                                : returns.data.map((ret) => (
                                    <tr key={ret.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                        <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800 dark:text-white/90">{ret.return_number}</span></td>
                                        <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{formatDate(ret.date)}</td>
                                        <td className="px-6 py-4 text-center text-gray-800 dark:text-white/90 font-medium">{ret.details_count} jenis</td>
                                        <td className="px-6 py-4 text-center"><StatusBadge status={ret.status} /></td>
                                        <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{ret.receiver ?? '-'}</td>
                                        <td className="px-6 py-4 text-right">
                                            <Link href={route('jihans.returns-to-gudang.show', ret.id)} className="text-sm font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                                                Lihat Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                        </tbody>
                    </table>
                </div>
                {returns.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={returns.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
