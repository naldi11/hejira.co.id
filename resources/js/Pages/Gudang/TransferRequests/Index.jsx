import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const STAT_CARDS = [
    { key: 'pending', label: 'Menunggu Review', suffix: 'Dokumen', icon: 'pending_actions', color: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400', pulse: true },
    { key: 'approved', label: 'Siap Dikirim', suffix: 'Dokumen', icon: 'task_alt', color: 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' },
    { key: 'completed', label: 'Selesai / Terkirim', suffix: 'Bulan Ini', icon: 'local_shipping', color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' },
];

const ENTITY_META = {
    hendhys: { icon: 'cake', color: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
    jihans: { icon: 'bakery_dining', color: 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400' },
};

function Row({ req }) {
    const meta = ENTITY_META[req.from_entity] ?? { icon: 'inventory', color: 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-405' };
    return (
        <tr className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
            <td className="px-6 py-4.5">
                <div className="flex flex-col">
                    <span className="text-sm font-bold tracking-tight text-brand-500 dark:text-brand-400 group-hover:underline">{req.request_number}</span>
                    <span className="mt-1 text-[10px] font-semibold text-gray-400 dark:text-gray-550">{formatDate(req.date)}</span>
                </div>
            </td>
            <td className="px-6 py-4.5">
                <div className="flex items-center gap-3">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${meta.color}`}>
                        <Icon name={meta.icon} className="text-[18px]" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xs font-bold uppercase text-gray-700 dark:text-gray-300">{req.from_entity}</span>
                        <span className="text-[10px] font-semibold text-gray-450 dark:text-gray-550">{req.branch ?? 'Produksi Pusat'}</span>
                    </div>
                </div>
            </td>
            <td className="px-6 py-4.5 text-center"><StatusBadge status={req.status} /></td>
            <td className="px-6 py-4.5">
                <div className="flex items-center gap-2">
                    <div className="flex h-7 w-7 items-center justify-center rounded-full bg-gray-105 text-[10px] font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {(req.requester ?? '?').charAt(0).toUpperCase()}
                    </div>
                    <span className="text-xs font-semibold text-gray-600 dark:text-gray-400">{req.requester ?? '-'}</span>
                </div>
            </td>
            <td className="px-6 py-4.5 text-right">
                <Link
                    href={route('gudang.transfer-requests.show', req.id)}
                    className={`inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                        req.status === 'pending'
                            ? 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600'
                            : 'border border-gray-200 bg-gray-50 text-gray-600 hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-brand-400'
                    }`}
                >
                    {req.status === 'pending' && <Icon name="visibility" className="text-[16px]" />}
                    {req.status === 'pending' ? 'Review' : 'Detail'}
                </Link>
            </td>
        </tr>
    );
}

export default function TransferRequestsIndex({ requests, counts, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '', from_entity: filters.from_entity ?? '' });
    const hasFilter = form.search || form.status || form.from_entity;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.transfer-requests.index'),
            { search: form.search || undefined, status: form.status || undefined, from_entity: form.from_entity || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['requests', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <GudangLayout title="Transfer Requests" pageTitle="Permintaan Barang">
            <Head title="Transfer Requests" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Permintaan Transfer Stok</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Review dan persetujuan permintaan barang dari unit bisnis</p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 md:gap-6">
                    {STAT_CARDS.map((c) => (
                        <div key={c.key} className="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                            <div className={`flex h-12 w-12 items-center justify-center rounded-xl shadow-inner ${c.color}`}>
                                <Icon name={c.icon} className={`text-[24px] ${c.pulse ? 'animate-pulse' : ''}`} />
                            </div>
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500">{c.label}</p>
                                <p className="text-lg font-bold tabular-nums text-gray-800 dark:text-white/90 mt-1">
                                    {counts[c.key]} <span className="text-xs font-semibold text-gray-400 dark:text-gray-500">{c.suffix}</span>
                                </p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Request..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu</option>
                                <option value="approved">Disetujui</option>
                                <option value="partial">Sebagian</option>
                                <option value="completed">Selesai</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                            <select value={form.from_entity} onChange={(e) => setForm({ ...form, from_entity: e.target.value })} className={selectClass}>
                                <option value="">Asal Request</option>
                                <option value="hendhys">Hendhys Brownies</option>
                                <option value="jihans">Jihan's Food</option>
                            </select>
                            <Button type="submit" size="sm">Filter</Button>
                            {hasFilter && (
                                <Link href={route('gudang.transfer-requests.index')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>
                            )}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Data Dokumen</th>
                                    <th className="px-6 py-4.5">Unit Bisnis</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5">Peminta</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : requests.data.length === 0 ? <EmptyState colSpan={5} icon="move_to_inbox" message="Belum ada dokumen Transfer Request." />
                                    : requests.data.map((req) => <Row key={req.id} req={req} />)}
                            </tbody>
                        </table>
                    </div>

                    {requests.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={requests.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
