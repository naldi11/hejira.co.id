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
import Button from '@/Components/ui/button/Button';

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

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <Layout title="Daftar Supplier" pageTitle="Master Data — Supplier">
            <Head title="Supplier" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Daftar Supplier</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{suppliers.meta?.total ?? suppliers.data.length} mitra supplier terdaftar</p>
                    </div>
                    <Link href={route(routePrefix + 'suppliers.create')}>
                        <Button size="sm" startIcon={<Icon name="add" className="text-[18px]" />}>
                            TAMBAH SUPPLIER
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[280px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau telepon..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                            <Button type="submit" size="sm">Cari</Button>
                            {hasFilter && <Link href={route(routePrefix + 'suppliers.index')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Kode</th>
                                    <th className="px-6 py-4.5">Nama Supplier</th>
                                    <th className="px-6 py-4.5">Kontak Personal</th>
                                    <th className="px-6 py-4.5">Telepon</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : suppliers.data.length === 0 ? <EmptyState colSpan={6} icon="local_shipping" message="Tidak ada data supplier." />
                                    : suppliers.data.map((s) => (
                                        <tr key={s.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5">
                                                <span className="rounded-md bg-gray-100 dark:bg-gray-850 px-2.5 py-1 font-mono text-xs font-bold text-gray-500 dark:text-gray-400">
                                                    {s.code}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4.5 font-bold text-gray-800 dark:text-white/90 group-hover:underline">{s.name}</td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-600 dark:text-gray-400">{s.contact_person ?? '—'}</td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-600 dark:text-gray-400">{s.phone ?? '—'}</td>
                                            <td className="px-6 py-4.5 text-center">
                                                <span className={`inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ${s.is_active ? 'border-emerald-250 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-900/30' : 'border-gray-200 bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'}`}>
                                                    <span className={`h-1.5 w-1.5 rounded-full ${s.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />{s.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'suppliers.edit', s.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-brand-400">
                                                        <Icon name="edit" className="text-[18px]" />
                                                    </Link>
                                                    <button onClick={() => destroy(s)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition hover:bg-white hover:text-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-rose-455">
                                                        <Icon name="delete" className="text-[18px]" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {suppliers.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={suppliers.meta.links} /></div>}
                </div>
            </div>
        </Layout>
    );
}
