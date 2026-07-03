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

    const deletePrediksi = (id) => {
        if (confirm('Yakin ingin menghapus prediksi ini?')) {
            router.delete(route('hendhys.productions.prediksi.destroy', id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <HendhysLayout pageTitle="Produksi Hendhys">
            <Head title="Produksi" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Catatan Produksi</h2>
                    <div className="flex gap-2">
                        <Link href={route('hendhys.productions.prediksi.create')} className="inline-flex items-center gap-2 rounded-xl bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                            <Icon name="event_note" className="text-[20px]" /> Buat Prediksi
                        </Link>
                        <Link href={route('hendhys.productions.create')} className="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700">
                            <Icon name="add" className="text-[20px]" /> Input Aktual
                        </Link>
                    </div>
                </div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[200px] flex-1"><Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" /><input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari no produksi..." className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" /></div>
                            <input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" />
                            <input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" />
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                        </form>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500 dark:text-gray-400 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Produksi</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 text-center font-medium">Status</th>
                                    <th className="px-6 py-4 text-center font-medium">Jumlah Item</th>
                                    <th className="px-6 py-4 font-medium">Operator</th>
                                    <th className="px-6 py-4 text-center font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : productions.data.length === 0 ? <EmptyState colSpan={6} icon="factory" message="Belum ada catatan produksi." />
                                    : productions.data.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">
                                                {p.production_number}
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{p.date}</td>
                                            <td className="px-6 py-4 text-center">
                                                {p.is_prediksi ? (
                                                    <span className="rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700 border border-blue-200">PREDIKSI</span>
                                                ) : (
                                                    <span className="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">AKTUAL</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-center"><span className="rounded-lg bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">{p.total_items}</span></td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{p.creator}</td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-3">
                                                    {p.is_prediksi ? (
                                                        <>
                                                            <Link href={route('hendhys.productions.prediksi.edit', p.id)} className="text-sm font-medium text-blue-600 hover:text-blue-800">Edit</Link>
                                                            <Link href={route('hendhys.productions.create', { date: p.date })} className="text-sm font-medium text-emerald-600 hover:text-emerald-800">Input Aktual</Link>
                                                            <button onClick={() => deletePrediksi(p.id)} className="text-sm font-medium text-red-600 hover:text-red-800">Hapus</button>
                                                        </>
                                                    ) : (
                                                        <Link href={route('hendhys.productions.show', p.id)} className="text-sm font-medium text-amber-600 hover:text-amber-800 dark:text-amber-400">Detail</Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>))}
                            </tbody>
                        </table>
                    </div>
                    {productions.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={productions.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
