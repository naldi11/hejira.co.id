import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
const route = window.route;
export default function ProductionsIndex({ productions, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('hendhys.productions.index'), p, { preserveState: true, preserveScroll: true, replace: true, only: ['productions', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) }); };
    return (
        <HendhysLayout pageTitle="Produksi Hendhys">
            <Head title="Produksi" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Catatan Produksi</h2>
                    <Link href={route('hendhys.productions.create')} className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700"><Icon name="add" className="text-[20px]" /> Catat Produksi</Link>
                </div>
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[200px] flex-1"><Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" /><input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari no produksi..." className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" /></div>
                            <input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm" />
                            <input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm" />
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                        </form>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500"><tr><th className="px-6 py-4 font-medium">No. Produksi</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 text-center font-medium">Jumlah Item</th><th className="px-6 py-4 font-medium">Operator</th><th className="px-6 py-4 text-center font-medium">Aksi</th></tr></thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={6} columns={5} />
                                    : productions.data.length === 0 ? <EmptyState colSpan={5} icon="factory" message="Belum ada catatan produksi." />
                                    : productions.data.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 font-bold text-gray-800">{p.production_number}</td>
                                            <td className="px-6 py-4 text-gray-600">{p.date}</td>
                                            <td className="px-6 py-4 text-center"><span className="rounded-lg bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">{p.total_items}</span></td>
                                            <td className="px-6 py-4 text-gray-500">{p.creator}</td>
                                            <td className="px-6 py-4 text-center"><Link href={route('hendhys.productions.show', p.id)} className="text-sm font-medium text-amber-600 hover:text-amber-800">Detail</Link></td>
                                        </tr>))}
                            </tbody>
                        </table>
                    </div>
                    {productions.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={productions.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
