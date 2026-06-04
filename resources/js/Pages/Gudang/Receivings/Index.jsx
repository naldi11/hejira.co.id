import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;

export default function ReceivingsIndex({ receivings, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const hasFilter = form.search || form.date_from || form.date_to;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.receiving.index'),
            { search: form.search || undefined, date_from: form.date_from || undefined, date_to: form.date_to || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['receivings', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const dateClass = 'rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10';

    return (
        <GudangLayout title="Penerimaan Barang" pageTitle="Penerimaan Barang">
            <Head title="Penerimaan Barang (GRN)" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Penerimaan Barang (GRN)</h2>
                        <p className="text-sm font-medium text-slate-500">Log masuk barang dari supplier berdasarkan dokumen PO</p>
                    </div>
                    <Link href={route('gudang.receiving.create')} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="archive" className="text-[20px]" /> Buat GRN Baru
                    </Link>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. GRN atau Supplier..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
                            </div>
                            <div className="flex items-center gap-2">
                                <input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} className={dateClass} />
                                <Icon name="trending_flat" className="text-slate-300" />
                                <input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} className={dateClass} />
                            </div>
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && <Link href={route('gudang.receiving.index')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Data Penerimaan</th>
                                    <th className="px-6 py-4">Supplier</th>
                                    <th className="px-6 py-4">Referensi Dokumen</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={4} />
                                    : receivings.data.length === 0 ? <EmptyState colSpan={4} icon="move_to_inbox" message="Belum ada data penerimaan barang." />
                                    : receivings.data.map((grn) => (
                                        <tr key={grn.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="text-sm font-black tracking-tight text-slate-800 transition-colors group-hover:text-indigo-600">{grn.grn_number}</span>
                                                    <span className="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">{formatDate(grn.date)}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500"><Icon name="local_shipping" className="text-[18px]" /></div>
                                                    <span className="text-xs font-black uppercase tracking-tight text-slate-700">{grn.supplier}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {grn.po ? (
                                                    <span className="flex items-center gap-2">
                                                        <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">PO:</span>
                                                        <Link href={route('gudang.po.show', grn.po.id)} className="text-xs font-black tabular-nums text-indigo-500 hover:underline">{grn.po.po_number}</Link>
                                                    </span>
                                                ) : (
                                                    <span className="text-xs font-bold italic text-slate-300">Tanpa PO (Manual)</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('gudang.receiving.show', grn.id)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-600 transition-all hover:bg-white hover:text-indigo-600">Detail</Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {receivings.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={receivings.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
