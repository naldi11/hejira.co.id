import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import Button from '@/Components/ui/button/Button';

const route = window.route;

function ImportModal({ show, onClose, routePrefix = 'master.' }) {
    const { setData, post, processing, errors, reset } = useForm({ file: null });
    const submit = (e) => {
        e.preventDefault();
        post(route(routePrefix + 'customers.import'), { forceFormData: true, onSuccess: () => { reset(); onClose(); } });
    };
    return (
        <Modal show={show} onClose={onClose} title="Import Customer" subtitle="Unggah file Excel/CSV" icon="upload_file">
            <form onSubmit={submit} className="space-y-6">
                <a href={route(routePrefix + 'customers.template')} className="inline-flex items-center gap-2 text-sm font-bold text-brand-500 hover:underline">
                    <Icon name="download" className="text-[18px]" /> Unduh template terlebih dahulu
                </a>
                <div>
                    <input type="file" required accept=".xlsx,.xls,.csv" onChange={(e) => setData('file', e.target.files[0])}
                        className="w-full h-11 rounded-lg border border-dashed border-gray-300 bg-gray-50/50 px-4 py-2.5 text-xs text-gray-500 hover:border-brand-500 cursor-pointer dark:bg-gray-900/50 dark:border-gray-700" />
                    {errors.file && <p className="mt-1 text-xs font-bold text-rose-600 dark:text-rose-455">{errors.file}</p>}
                </div>
                <Button type="submit" disabled={processing} className="w-full">
                    {processing ? 'Mengunggah...' : 'Import Sekarang'}
                </Button>
            </form>
        </Modal>
    );
}

export default function CustomersIndex({ customers, filters , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.status !== '';

    const reload = (e) => {
        e?.preventDefault();
        router.get(route(routePrefix + 'customers.index'),
            { search: form.search || undefined, status: form.status !== '' ? form.status : undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['customers', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };
    const destroy = (c) => { if (window.confirm(`Hapus customer ${c.name}?`)) router.delete(route(routePrefix + 'customers.destroy', c.id), { preserveScroll: true }); };

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 dark:text-white outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <Layout title="Daftar Customer" pageTitle="Master Data — Customer">
            <Head title="Customer" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Daftar Customer</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{customers.meta?.total ?? (customers.data ?? customers ?? []).length} pelanggan terdaftar</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <button onClick={() => setImporting(true)} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-50 dark:hover:bg-white/[0.02] shadow-sm">
                            <Icon name="upload_file" className="text-[18px]" /> Import
                        </button>
                        <Link href={route(routePrefix + 'customers.create')}>
                            <Button size="sm" startIcon={<Icon name="add" className="text-[18px]" />}>
                                TAMBAH CUSTOMER
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap gap-4">
                            <div className="relative min-w-[280px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau telepon..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 dark:text-white outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900/50" />
                            </div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="" className="dark:bg-gray-800">Semua Status</option>
                                <option value="1" className="dark:bg-gray-800">Aktif</option>
                                <option value="0" className="dark:bg-gray-800">Nonaktif</option>
                            </select>
                            <button type="submit" className="rounded-lg bg-gray-800 dark:bg-gray-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-widest transition-all hover:bg-gray-900 dark:hover:bg-gray-600"><Icon name="filter_list" className="text-[18px] inline-block mr-1" /> Cari</button>
                            {hasFilter && <Link href={route(routePrefix + 'customers.index')} className="flex items-center gap-2 rounded-lg bg-rose-50 dark:bg-rose-950/20 px-6 py-2.5 text-xs font-bold uppercase tracking-widest text-rose-600 dark:text-rose-455 transition-all hover:bg-rose-100"><Icon name="close" className="text-[18px]" /> Reset</Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4">Kode</th>
                                    <th className="px-6 py-4">Nama</th>
                                    <th className="px-6 py-4">Telepon</th>
                                    <th className="px-6 py-4">Kota</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : (customers.data ?? customers ?? []).length === 0 ? <EmptyState colSpan={6} icon="groups" message="Tidak ada data customer." />
                                    : (customers.data ?? customers ?? []).map((c) => (
                                        <tr key={c.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4"><span className="rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1 font-mono text-xs font-bold text-gray-500 dark:text-gray-400">{c.code}</span></td>
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{c.name}</td>
                                            <td className="px-6 py-4 text-gray-650 dark:text-gray-400">{c.phone ?? '-'}</td>
                                            <td className="px-6 py-4 text-gray-655 dark:text-gray-400">{c.city ?? '-'}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex items-center gap-1.5 rounded-xl border px-3 py-1 text-[10px] font-bold uppercase tracking-widest ${c.is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-605 dark:border-emerald-950/40 dark:bg-emerald-950/20 dark:text-emerald-400' : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400'}`}>
                                                    <span className={`h-1.5 w-1.5 rounded-full ${c.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />{c.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'customers.edit', c.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition-all hover:bg-indigo-50 hover:text-indigo-650 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-indigo-400"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(c)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition-all hover:bg-rose-50 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-rose-455"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {customers.meta?.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={customers.meta.links} /></div>}
                </div>
            </div>

            <ImportModal show={importing} onClose={() => setImporting(false)} routePrefix={routePrefix} />
        </Layout>
    );
}
