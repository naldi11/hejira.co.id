import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;

export default function TransferOutIndex({ transfers, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', to_entity: filters.to_entity ?? '' });
    const hasFilter = form.search || form.to_entity;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.transfer-out.index'),
            { search: form.search || undefined, to_entity: form.to_entity || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['transfers', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <GudangLayout title="Transfer Keluar" pageTitle="Gudang — Transfer Keluar">
            <Head title="Transfer Keluar" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Transfer Keluar Barang</h2>
                        <p className="text-sm font-medium text-slate-500">Pengiriman barang dari Gudang Utama ke Cabang (Hendhys) atau Produksi (Jihans)</p>
                    </div>
                    <Link href={route('gudang.transfer-out.create')} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="add" className="text-[20px]" /> Buat Transfer Baru
                    </Link>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Dokumen (DO)..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
                            </div>
                            <select value={form.to_entity} onChange={(e) => setForm({ ...form, to_entity: e.target.value })} className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10">
                                <option value="">Semua Tujuan</option>
                                <option value="hendhys">Hendhys (Cabang)</option>
                                <option value="jihans">Jihans (Produksi)</option>
                            </select>
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && <Link href={route('gudang.transfer-out.index')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Tanggal</th>
                                    <th className="px-6 py-4">No. Transfer (DO)</th>
                                    <th className="px-6 py-4">Tujuan</th>
                                    <th className="px-6 py-4">Referensi Request</th>
                                    <th className="px-6 py-4">Dibuat Oleh</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : transfers.data.length === 0 ? <EmptyState colSpan={6} icon="output" message="Belum ada data Transfer Keluar." />
                                    : transfers.data.map((trf) => (
                                        <tr key={trf.id} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4 text-sm text-slate-500">{formatDate(trf.date)}</td>
                                            <td className="px-6 py-4 text-sm font-black text-slate-800">{trf.transfer_number}</td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-2 text-sm text-slate-700">
                                                    <span className={`h-2 w-2 rounded-full ${trf.to_entity === 'hendhys' ? 'bg-blue-500' : 'bg-purple-500'}`} />
                                                    <span className="font-bold capitalize">{trf.to_entity}</span>
                                                    <span className="text-xs text-slate-400">({trf.branch ?? 'Produksi'})</span>
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {trf.request ? (
                                                    <Link href={route('gudang.transfer-requests.show', trf.request.id)} className="text-xs font-medium text-indigo-600 hover:underline">{trf.request.request_number}</Link>
                                                ) : (
                                                    <span className="text-xs italic text-slate-400">Tanpa Request</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-slate-500">{trf.creator ?? '-'}</td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('gudang.transfer-out.show', trf.id)} className="text-xs font-medium text-indigo-600 hover:text-indigo-800">Lihat Detail</Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {transfers.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={transfers.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
