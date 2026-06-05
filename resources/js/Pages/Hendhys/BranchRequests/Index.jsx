import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
const route = window.route;
export default function BranchRequestsIndex({ requests, filters }) {
    const { auth } = usePage().props;
    const isCabang = auth?.user?.branch?.type === 'cabang';
    const [loading, setLoading] = useState(false);

    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('hendhys.branch-requests.index'), p, { preserveState: true, preserveScroll: true, replace: true, onStart: () => setLoading(true), onFinish: () => setLoading(false) }); };
    return (
        <HendhysLayout pageTitle="Request Stok Cabang">
            <Head title="Request Cabang" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Request Stok dari Cabang</h2>
                    {isCabang && (
                        <Link href={route('hendhys.branch-requests.create')} className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700"><Icon name="add" className="text-[20px]" /> Buat Request</Link>
                    )}
                </div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.01] p-4"><form onSubmit={reload} className="flex flex-wrap items-center gap-3"><div className="relative min-w-[200px] flex-1"><Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" /><input type="text" value={form.search} onChange={(e) => setForm({...form, search: e.target.value})} placeholder="Cari..." className="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 py-2 pl-10 pr-4 text-sm" /></div><select value={form.status} onChange={(e) => setForm({...form, status: e.target.value})} className="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 py-2 text-sm"><option value="">Semua</option><option value="pending">Pending</option><option value="completed">Completed</option><option value="partial">Partial</option><option value="rejected">Rejected</option></select><button type="submit" className="rounded-lg bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button></form></div>
                    <div className="custom-scrollbar overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-white/[0.02] text-gray-500 dark:text-gray-400"><tr><th className="px-6 py-4 font-medium">No. Request</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 font-medium">Cabang</th><th className="px-6 py-4 text-center font-medium">Status</th><th className="px-6 py-4 text-center font-medium">Aksi</th></tr></thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">{loading ? <SkeletonTableRows rows={6} columns={5} /> : requests.data.length === 0 ? <EmptyState colSpan={5} icon="move_to_inbox" message="Belum ada request." /> : requests.data.map((r) => (<tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]"><td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{r.request_number}</td><td className="px-6 py-4 text-gray-600 dark:text-gray-300">{r.date}</td><td className="px-6 py-4 text-gray-600 dark:text-gray-300">{r.branch ?? 'Pusat'}</td><td className="px-6 py-4 text-center"><span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${r.status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400' : r.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'}`}>{r.status}</span></td><td className="px-6 py-4 text-center"><Link href={route('hendhys.branch-requests.show', r.id)} className="text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300">Detail</Link></td></tr>))}</tbody></table></div>
                    {requests.meta?.links && <div className="border-t border-gray-100 dark:border-gray-800 p-4"><Pagination links={requests.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}

