import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate, formatRupiah } from '@/lib/format';

const route = window.route;

export default function PurchaseOrdersIndex({ orders, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.status;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.po.index'),
            { search: form.search || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['orders', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <GudangLayout title="Purchase Order" pageTitle="Purchase Order">
            <Head title="Purchase Order" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Pesanan Pembelian (PO)</h2>
                        <p className="text-sm font-medium text-slate-500">Kelola pesanan stok barang ke supplier/vendor</p>
                    </div>
                    <Link href={route('gudang.po.create')} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="add_shopping_cart" className="text-[20px]" /> Buat PO Baru
                    </Link>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. PO atau Supplier..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
                            </div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="sent">Terkirim</option>
                                <option value="partial">Sebagian</option>
                                <option value="received">Diterima</option>
                                <option value="cancelled">Batal</option>
                            </select>
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && <Link href={route('gudang.po.index')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Dokumen</th>
                                    <th className="px-6 py-4">Supplier</th>
                                    <th className="px-6 py-4 text-right">Nilai Pesanan</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : orders.data.length === 0 ? <EmptyState colSpan={5} icon="shopping_basket" message="Belum ada data Purchase Order." />
                                    : orders.data.map((po) => (
                                        <tr key={po.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <Link href={route('gudang.po.show', po.id)} className="flex flex-col">
                                                    <span className="text-sm font-black tracking-tight text-indigo-600 underline-offset-4 group-hover:underline">{po.po_number}</span>
                                                    <span className="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">{formatDate(po.date)}</span>
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><Icon name="factory" className="text-[18px]" /></div>
                                                    <span className="text-xs font-black uppercase tracking-tight text-slate-700">{po.supplier}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm font-black tabular-nums text-slate-900">{formatRupiah(po.total_amount)}</td>
                                            <td className="px-6 py-4 text-center"><StatusBadge status={po.status} /></td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('gudang.po.show', po.id)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-600 transition-all hover:bg-white hover:text-indigo-600">Detail</Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {orders.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={orders.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
