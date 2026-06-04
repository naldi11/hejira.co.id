import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;

const STAT_CARDS = [
    { key: 'pending', label: 'Menunggu Review', suffix: 'Dokumen', icon: 'pending_actions', color: 'bg-amber-50 text-amber-600', pulse: true },
    { key: 'approved', label: 'Siap Dikirim', suffix: 'Dokumen', icon: 'task_alt', color: 'bg-indigo-50 text-indigo-600' },
    { key: 'completed', label: 'Selesai / Terkirim', suffix: 'Bulan Ini', icon: 'local_shipping', color: 'bg-emerald-50 text-emerald-600' },
];

const ENTITY_META = {
    hendhys: { icon: 'cake', color: 'bg-amber-100 text-amber-700' },
    jihans: { icon: 'bakery_dining', color: 'bg-orange-100 text-orange-700' },
};

function Row({ req }) {
    const meta = ENTITY_META[req.from_entity] ?? { icon: 'inventory', color: 'bg-slate-100 text-slate-600' };
    return (
        <tr className="group transition-colors hover:bg-slate-50/50">
            <td className="px-6 py-4">
                <div className="flex flex-col">
                    <span className="text-sm font-black tracking-tight text-slate-800 transition-colors group-hover:text-indigo-600">{req.request_number}</span>
                    <span className="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">{formatDate(req.date)}</span>
                </div>
            </td>
            <td className="px-6 py-4">
                <div className="flex items-center gap-3">
                    <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${meta.color}`}>
                        <Icon name={meta.icon} className="text-[18px]" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xs font-black uppercase tracking-tight text-slate-700">{req.from_entity}</span>
                        <span className="text-[10px] font-bold text-slate-400">{req.branch ?? 'Produksi Pusat'}</span>
                    </div>
                </div>
            </td>
            <td className="px-6 py-4 text-center"><StatusBadge status={req.status} /></td>
            <td className="px-6 py-4">
                <div className="flex items-center gap-2">
                    <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-black text-slate-500">
                        {(req.requester ?? '?').charAt(0)}
                    </div>
                    <span className="text-xs font-bold text-slate-600">{req.requester ?? '-'}</span>
                </div>
            </td>
            <td className="px-6 py-4 text-right">
                <Link
                    href={route('gudang.transfer-requests.show', req.id)}
                    className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black uppercase tracking-widest transition-all ${
                        req.status === 'pending'
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
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

    const selectClass = 'rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10';

    return (
        <GudangLayout title="Transfer Requests" pageTitle="Permintaan Barang">
            <Head title="Transfer Requests" />

            <div className="space-y-6">
                <div>
                    <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Permintaan Transfer Stok</h2>
                    <p className="text-sm font-medium text-slate-500">Review dan persetujuan permintaan barang dari unit bisnis</p>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    {STAT_CARDS.map((c) => (
                        <div key={c.key} className="flex items-center gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div className={`flex h-14 w-14 items-center justify-center rounded-2xl shadow-inner ${c.color}`}>
                                <Icon name={c.icon} className={`text-[32px] ${c.pulse ? 'animate-pulse' : ''}`} />
                            </div>
                            <div>
                                <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">{c.label}</p>
                                <p className="text-2xl font-black tabular-nums text-slate-900">{counts[c.key]} <span className="text-xs font-bold text-slate-400">{c.suffix}</span></p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Request..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
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
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && (
                                <Link href={route('gudang.transfer-requests.index')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>
                            )}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Data Dokumen</th>
                                    <th className="px-6 py-4">Unit Bisnis</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4">Peminta</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : requests.data.length === 0 ? <EmptyState colSpan={5} icon="move_to_inbox" message="Belum ada dokumen Transfer Request." />
                                    : requests.data.map((req) => <Row key={req.id} req={req} />)}
                            </tbody>
                        </table>
                    </div>

                    {requests.meta?.links && <div className="border-t border-slate-100 bg-slate-50/30 p-6"><Pagination links={requests.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
