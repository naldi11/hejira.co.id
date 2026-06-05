import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

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

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <GudangLayout title="Transfer Keluar" pageTitle="Gudang — Transfer Keluar">
            <Head title="Transfer Keluar" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Transfer Keluar Barang</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Pengiriman barang dari Gudang Utama ke Cabang (Hendhys) atau Produksi (Jihans)</p>
                    </div>
                    <Link href={route('gudang.transfer-out.create')}>
                        <Button size="sm" startIcon={<Icon name="add" className="text-[18px]" />}>
                            BUAT TRANSFER
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Dokumen (DO)..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.to_entity} onChange={(e) => setForm({ ...form, to_entity: e.target.value })} className={selectClass}>
                                <option value="">Semua Tujuan</option>
                                <option value="hendhys">Hendhys (Cabang)</option>
                                <option value="jihans">Jihans (Produksi)</option>
                            </select>
                            <Button type="submit" size="sm">Filter</Button>
                            {hasFilter && <Link href={route('gudang.transfer-out.index')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Tanggal</th>
                                    <th className="px-6 py-4.5">No. Transfer (DO)</th>
                                    <th className="px-6 py-4.5">Tujuan</th>
                                    <th className="px-6 py-4.5">Referensi Request</th>
                                    <th className="px-6 py-4.5">Dibuat Oleh</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : transfers.data.length === 0 ? <EmptyState colSpan={6} icon="output" message="Belum ada data Transfer Keluar." />
                                    : transfers.data.map((trf) => (
                                        <tr key={trf.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-500 dark:text-gray-400 tabular-nums">{formatDate(trf.date)}</td>
                                            <td className="px-6 py-4.5 text-sm font-bold text-brand-500 dark:text-brand-400 group-hover:underline">{trf.transfer_number}</td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-2">
                                                    <span className={`h-2 w-2 rounded-full ${trf.to_entity === 'hendhys' ? 'bg-blue-500' : 'bg-purple-500'}`} />
                                                    <span className="text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">{trf.to_entity}</span>
                                                    <span className="text-[10px] font-semibold text-gray-400 dark:text-gray-500">({trf.branch ?? 'Produksi'})</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-650 dark:text-gray-400">
                                                {trf.request ? (
                                                    <Link href={route('gudang.transfer-requests.show', trf.request.id)} className="text-xs font-bold text-brand-500 dark:text-brand-400 hover:underline">{trf.request.request_number}</Link>
                                                ) : (
                                                    <span className="text-xs italic text-gray-400 dark:text-gray-550">Tanpa Request</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-600 dark:text-gray-400">{trf.creator ?? '-'}</td>
                                            <td className="px-6 py-4.5 text-right">
                                                <Link href={route('gudang.transfer-out.show', trf.id)} className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-brand-400">
                                                    Detail
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {transfers.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={transfers.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
