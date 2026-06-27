import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
const route = window.route;
export default function TransferRequestsIndex({ requests, incomingTransfers, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('hendhys.transfer-requests.index'), p, { preserveState: true, preserveScroll: true, replace: true, only: ['requests', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) }); };
    return (
        <HendhysLayout pageTitle="Request ke Gudang">
            <Head title="Request ke Gudang" />
            <div className="space-y-6">
                {incomingTransfers?.length > 0 && (
                    <div className="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900/30 dark:bg-green-950/10">
                        <h3 className="mb-2 font-semibold text-green-800 dark:text-green-400"><Icon name="local_shipping" className="mr-1 align-middle text-[20px]" /> Transfer Masuk dari Gudang</h3>
                        <div className="space-y-1">
                            {incomingTransfers.map((t) => (
                                <div key={t.id} className="flex items-center justify-between rounded-lg bg-white p-3 text-sm dark:bg-white/[0.03]">
                                    <span className="font-medium text-gray-800 dark:text-white/90">{t.transfer_number} — dari {t.creator}</span>
                                    <div className="flex items-center gap-3">
                                        <span className="text-gray-500 dark:text-gray-400">{t.date}</span>
                                        <Link 
                                            href={route('hendhys.transfer-requests.receive-form-gudang', t.id)} 
                                            className="rounded-lg bg-green-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-green-700 transition"
                                        >
                                            Terima Barang
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Request Stok ke Gudang</h2>
                    <Link href={route('hendhys.transfer-requests.create')} className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700"><Icon name="add" className="text-[20px]" /> Buat Request</Link>
                </div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[200px] flex-1"><Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" /><input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari no request..." className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" /></div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500"><option value="">Semua Status</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="completed">Completed</option><option value="rejected">Rejected</option></select>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                        </form>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-white/[0.02]"><tr><th className="px-6 py-4 font-medium">No. Request</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 text-center font-medium">Status</th><th className="px-6 py-4 font-medium">Dibuat Oleh</th><th className="px-6 py-4 text-center font-medium">Aksi</th></tr></thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : requests.data.length === 0 ? <EmptyState colSpan={5} icon="sync_alt" message="Belum ada request." />
                                    : requests.data.map((r) => (
                                        <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{r.request_number}</td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{r.date}</td>
                                            <td className="px-6 py-4 text-center"><span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${r.status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400' : r.status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : r.status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'}`}>{r.status}</span></td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{r.creator}</td>
                                            <td className="px-6 py-4 text-center"><Link href={route('hendhys.transfer-requests.show', r.id)} className="text-sm font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400">Detail</Link></td>
                                        </tr>))}
                            </tbody>
                        </table>
                    </div>
                    {requests.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={requests.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
