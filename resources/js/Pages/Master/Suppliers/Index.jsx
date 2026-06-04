import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';

const route = window.route;

export default function SuppliersIndex({ suppliers, filters , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.status !== '';

    const reload = (e) => {
        e?.preventDefault();
        router.get(route(routePrefix + 'suppliers.index'),
            { search: form.search || undefined, status: form.status !== '' ? form.status : undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['suppliers', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const destroy = (s) => { if (window.confirm(`Hapus supplier ${s.name}?`)) router.delete(route(routePrefix + 'suppliers.destroy', s.id), { preserveScroll: true }); };

    return (
        <Layout title="Daftar Supplier" pageTitle="Master Data — Supplier">
            <Head title="Supplier" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black text-slate-900">Daftar Supplier</h2>
                        <p className="mt-1 text-sm font-medium text-slate-500">{suppliers.meta?.total ?? suppliers.data.length} mitra supplier terdaftar</p>
                    </div>
                    <Link href={route(routePrefix + 'suppliers.create')} className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="add" className="text-[18px]" /> Tambah Supplier
                    </Link>
                </div>

                <div className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <form onSubmit={reload} className="flex flex-wrap gap-4">
                        <div className="relative min-w-[280px] flex-1">
                            <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau telepon..."
                                className="w-full rounded-2xl border-2 border-slate-50 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10" />
                        </div>
                        <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="min-w-[180px] cursor-pointer rounded-2xl border-2 border-slate-50 bg-slate-50 px-4 py-3 text-sm outline-none transition-all focus:border-indigo-500 focus:bg-white">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        <button type="submit" className="flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-slate-800"><Icon name="filter_list" className="text-[18px]" /> Cari</button>
                        {hasFilter && <Link href={route(routePrefix + 'suppliers.index')} className="flex items-center gap-2 rounded-2xl bg-rose-50 px-6 py-3 text-xs font-bold uppercase tracking-widest text-rose-600 transition-all hover:bg-rose-100"><Icon name="close" className="text-[18px]" /> Reset</Link>}
                    </form>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Kode</th>
                                    <th className="px-6 py-4">Nama Supplier</th>
                                    <th className="px-6 py-4">Kontak Personal</th>
                                    <th className="px-6 py-4">Telepon</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : suppliers.data.length === 0 ? <EmptyState colSpan={6} icon="local_shipping" message="Tidak ada data supplier." />
                                    : suppliers.data.map((s) => (
                                        <tr key={s.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4"><span className="rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs font-bold text-slate-400">{s.code}</span></td>
                                            <td className="px-6 py-4 text-sm font-black text-slate-900">{s.name}</td>
                                            <td className="px-6 py-4 text-sm font-bold text-slate-500">{s.contact_person ?? '-'}</td>
                                            <td className="px-6 py-4 text-sm font-bold text-slate-500">{s.phone ?? '-'}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-[10px] font-black uppercase tracking-widest ${s.is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-slate-200 bg-slate-100 text-slate-500'}`}>
                                                    <span className={`h-1.5 w-1.5 rounded-full ${s.is_active ? 'bg-emerald-500' : 'bg-slate-400'}`} />{s.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'suppliers.edit', s.id)} className="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-indigo-50 hover:text-indigo-600"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(s)} className="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {suppliers.meta?.links && <div className="border-t border-slate-100 px-6 py-4"><Pagination links={suppliers.meta.links} /></div>}
                </div>
            </div>
        </Layout>
    );
}
