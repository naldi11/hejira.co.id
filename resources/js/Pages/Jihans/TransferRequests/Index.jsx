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

export default function JihansTransferRequestsIndex({ requests, incomingTransfers, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.transfer-requests.index'),
            { search: form.search || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['requests', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Request Bahan Baku ke Gudang">
            <Head title="Request ke Gudang" />

            {incomingTransfers.length > 0 && (
                <div className="mb-8 overflow-hidden rounded-2xl border border-orange-200 bg-orange-50/40 dark:border-orange-900/50 dark:bg-orange-950/20 shadow-theme-xs">
                    <div className="flex items-center justify-between border-b border-orange-200 dark:border-orange-900/40 bg-orange-100 dark:bg-orange-950/40 px-6 py-4">
                        <h3 className="flex items-center gap-2 font-bold text-orange-950 dark:text-orange-300">
                            <Icon name="local_shipping" className="animate-pulse text-[20px]" /> Pengiriman Masuk Belum Diterima
                        </h3>
                        <span className="rounded-full bg-orange-600 px-2.5 py-1 text-xs font-bold text-white dark:bg-orange-800">{incomingTransfers.length} Pengiriman</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-orange-200 bg-orange-500/5 dark:bg-orange-950/10 text-[10px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-400 dark:border-orange-900/40">
                                <tr>
                                    <th className="px-6 py-3 font-semibold">No. Transfer (DO)</th>
                                    <th className="px-6 py-3 font-semibold">Tanggal Kirim</th>
                                    <th className="px-6 py-3 font-semibold">Referensi Request</th>
                                    <th className="px-6 py-3 font-semibold">Pengirim</th>
                                    <th className="px-6 py-3 text-right font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-orange-100 dark:divide-orange-900/20">
                                {incomingTransfers.map((do_) => (
                                    <tr key={do_.id} className="transition-colors hover:bg-orange-100/30 dark:hover:bg-orange-950/10">
                                        <td className="px-6 py-4 font-mono font-bold text-orange-950 dark:text-orange-300">{do_.transfer_number}</td>
                                        <td className="px-6 py-4 text-orange-900 dark:text-orange-400">{do_.date}</td>
                                        <td className="px-6 py-4">
                                            {do_.request_number ? (
                                                <span className="font-mono font-semibold text-orange-900 dark:text-orange-400">{do_.request_number}</span>
                                            ) : (
                                                <span className="text-xs font-semibold italic text-orange-600 dark:text-orange-500">Transfer Langsung</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-orange-900 dark:text-orange-400">{do_.creator}</td>
                                        <td className="px-6 py-4 text-right">
                                            <a href={route('jihans.transfer-requests.receive-form', do_.id)} className="inline-flex items-center gap-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all">
                                                <Icon name="check_box" className="text-[14px]" /> Konfirmasi Terima
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Request ke Gudang</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Daftar permintaan bahan baku dan transfer masuk dari Gudang Utama</p>
                </div>
                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <Link href={route('jihans.transfer-requests.create')} className="inline-flex items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                        <Icon name="add" className="text-[18px]" /> Buat Request Baru
                    </Link>
                    <form onSubmit={reload} className="flex flex-wrap flex-1 gap-2 sm:flex-initial">
                        <select
                            value={form.status}
                            onChange={(e) => setForm({ ...form, status: e.target.value })}
                            className="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                        >
                            <option value="" className="dark:bg-gray-800">Semua Status</option>
                            <option value="pending" className="dark:bg-gray-800">Pending</option>
                            <option value="approved" className="dark:bg-gray-800">Approved</option>
                            <option value="partial" className="dark:bg-gray-800">Partial</option>
                            <option value="rejected" className="dark:bg-gray-800">Rejected</option>
                            <option value="completed" className="dark:bg-gray-800">Completed</option>
                        </select>
                        <div className="relative flex-1 sm:w-64">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                            <input
                                type="text"
                                value={form.search}
                                onChange={(e) => setForm({ ...form, search: e.target.value })}
                                placeholder="No. Request..."
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
                                <th className="px-6 py-4 font-semibold">No. Request</th>
                                <th className="px-6 py-4 font-semibold">Tanggal</th>
                                <th className="px-6 py-4 font-semibold">Status</th>
                                <th className="px-6 py-4 font-semibold">Catatan</th>
                                <th className="px-6 py-4 font-semibold">Oleh</th>
                                <th className="px-6 py-4 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                            {loading ? <SkeletonTableRows rows={6} columns={6} />
                                : requests.data.length === 0 ? <EmptyState colSpan={6} icon="receipt_long" message="Tidak ada data transfer request." />
                                : requests.data.map((req) => (
                                    <tr key={req.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                        <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800 dark:text-white/90">{req.request_number}</span></td>
                                        <td className="whitespace-nowrap px-6 py-4 text-gray-600 dark:text-gray-300">{formatDate(req.date)}</td>
                                        <td className="px-6 py-4"><StatusBadge status={req.status} /></td>
                                        <td className="max-w-xs truncate px-6 py-4 text-gray-500 dark:text-gray-400">{req.notes || '-'}</td>
                                        <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{req.creator ?? '-'}</td>
                                        <td className="px-6 py-4 text-right">
                                            <Link href={route('jihans.transfer-requests.show', req.id)} className="text-sm font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                                                Lihat Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                        </tbody>
                    </table>
                </div>
                {requests.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={requests.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
