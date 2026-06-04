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
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Return ke Gudang</h2>
                    <p className="text-sm text-gray-500">Pengembalian bahan baku / barang dari Jihan's ke Gudang Utama</p>
                </div>
                <div className="flex w-full gap-2 sm:w-auto">
                    <Link href={route('jihans.returns-to-gudang.create')} className="flex items-center gap-2 rounded-lg bg-orange-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-orange-900">
                        <Icon name="add" className="text-[18px]" /> Buat Retur Baru
                    </Link>
                    <form onSubmit={reload} className="flex flex-1 gap-2">
                        <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Semua Status</option><option value="sent">Dikirim</option><option value="received">Diterima</option>
                        </select>
                        <div className="relative flex-1 sm:w-56">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                            <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="No. Retur..." className="w-full rounded-lg border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                        </div>
                        <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                    </form>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                            <tr><th className="px-6 py-4 font-medium">No. Retur</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 text-center font-medium">Jml Item</th><th className="px-6 py-4 text-center font-medium">Status</th><th className="px-6 py-4 font-medium">Penerima</th><th className="px-6 py-4 text-right font-medium">Aksi</th></tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? <SkeletonTableRows rows={6} columns={6} />
                                : returns.data.length === 0 ? <EmptyState colSpan={6} icon="assignment_return" message="Belum ada data retur ke gudang." />
                                : returns.data.map((ret) => (
                                    <tr key={ret.id} className="transition-colors hover:bg-gray-50">
                                        <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800">{ret.return_number}</span></td>
                                        <td className="px-6 py-4 text-gray-600">{formatDate(ret.date)}</td>
                                        <td className="px-6 py-4 text-center">{ret.details_count} jenis</td>
                                        <td className="px-6 py-4 text-center"><StatusBadge status={ret.status} /></td>
                                        <td className="px-6 py-4 text-gray-500">{ret.receiver ?? '-'}</td>
                                        <td className="px-6 py-4 text-right"><Link href={route('jihans.returns-to-gudang.show', ret.id)} className="text-sm font-medium text-orange-600 hover:text-orange-900">Lihat Detail</Link></td>
                                    </tr>
                                ))}
                        </tbody>
                    </table>
                </div>
                {returns.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={returns.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
