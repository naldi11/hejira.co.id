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
                <div className="mb-8 overflow-hidden rounded-2xl border border-orange-200 bg-orange-50 shadow-sm">
                    <div className="flex items-center justify-between border-b border-orange-200 bg-orange-100 px-6 py-4">
                        <h3 className="flex items-center gap-2 font-bold text-orange-950"><Icon name="local_shipping" className="animate-pulse text-[20px]" /> Pengiriman Masuk Belum Diterima</h3>
                        <span className="rounded-full bg-orange-800 px-2.5 py-1 text-xs font-bold text-white">{incomingTransfers.length} Pengiriman</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-orange-200 bg-orange-50/50 text-xs font-bold uppercase text-orange-900">
                                <tr><th className="px-6 py-3">No. Transfer (DO)</th><th className="px-6 py-3">Tanggal Kirim</th><th className="px-6 py-3">Referensi Request</th><th className="px-6 py-3">Pengirim</th><th className="px-6 py-3 text-right">Aksi</th></tr>
                            </thead>
                            <tbody className="divide-y divide-orange-100">
                                {incomingTransfers.map((do_) => (
                                    <tr key={do_.id} className="transition-colors hover:bg-orange-100/30">
                                        <td className="px-6 py-4 font-mono font-bold text-orange-950">{do_.transfer_number}</td>
                                        <td className="px-6 py-4 text-orange-900">{do_.date}</td>
                                        <td className="px-6 py-4">{do_.request_number ? <span className="font-mono font-semibold text-orange-900">{do_.request_number}</span> : <span className="text-xs font-semibold italic text-orange-600">Transfer Langsung</span>}</td>
                                        <td className="px-6 py-4 text-orange-900">{do_.creator}</td>
                                        <td className="px-6 py-4 text-right">
                                            <a href={route('jihans.transfer-requests.receive-form', do_.id)} className="inline-flex items-center gap-1.5 rounded-lg bg-orange-800 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-orange-900">
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
                <Link href={route('jihans.transfer-requests.create')} className="flex items-center gap-2 rounded-lg bg-orange-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-orange-900">
                    <Icon name="add" className="text-[18px]" /> Buat Request Baru
                </Link>
                <form onSubmit={reload} className="flex w-full gap-2 sm:w-auto">
                    <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option><option value="approved">Approved</option><option value="partial">Partial</option><option value="rejected">Rejected</option><option value="completed">Completed</option>
                    </select>
                    <div className="relative flex-1 sm:w-64">
                        <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                        <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="No. Request..." className="w-full rounded-lg border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                    </div>
                    <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                </form>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                            <tr><th className="px-6 py-4 font-medium">No. Request</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 font-medium">Status</th><th className="px-6 py-4 font-medium">Catatan</th><th className="px-6 py-4 font-medium">Oleh</th><th className="px-6 py-4 text-right font-medium">Aksi</th></tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? <SkeletonTableRows rows={6} columns={6} />
                                : requests.data.length === 0 ? <EmptyState colSpan={6} icon="receipt_long" message="Tidak ada data transfer request." />
                                : requests.data.map((req) => (
                                    <tr key={req.id} className="transition-colors hover:bg-gray-50">
                                        <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800">{req.request_number}</span></td>
                                        <td className="whitespace-nowrap px-6 py-4 text-gray-600">{formatDate(req.date)}</td>
                                        <td className="px-6 py-4"><StatusBadge status={req.status} /></td>
                                        <td className="max-w-xs truncate px-6 py-4 text-gray-500">{req.notes || '-'}</td>
                                        <td className="px-6 py-4 text-gray-500">{req.creator ?? '-'}</td>
                                        <td className="px-6 py-4 text-right"><Link href={route('jihans.transfer-requests.show', req.id)} className="text-sm font-medium text-orange-600 hover:text-orange-900">Lihat Detail</Link></td>
                                    </tr>
                                ))}
                        </tbody>
                    </table>
                </div>
                {requests.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={requests.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
