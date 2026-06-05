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
import StatusBadge from '@/Components/StatusBadge';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatRupiah } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

function ImportModal({ show, onClose, routePrefix = 'master.' }) {
    const { setData, post, processing, errors, reset } = useForm({ file: null });
    const submit = (e) => {
        e.preventDefault();
        post(route(routePrefix + 'products.import'), { forceFormData: true, onSuccess: () => { reset(); onClose(); } });
    };
    return (
        <Modal show={show} onClose={onClose} title="Import Produk" subtitle="Unggah file Excel/CSV" icon="upload_file" maxWidth="max-w-md">
            <form onSubmit={submit} className="space-y-6">
                <a href={route(routePrefix + 'products.template')} className="inline-flex items-center gap-1.5 text-sm font-bold text-brand-500 hover:underline">
                    <Icon name="download" className="text-[18px]" /> Unduh template terlebih dahulu
                </a>
                <input type="file" required accept=".xlsx,.xls,.csv" onChange={(e) => setData('file', e.target.files[0])} 
                    className="w-full h-11 rounded-lg border border-dashed border-gray-300 bg-gray-50/50 px-4 py-2.5 text-xs text-gray-500 hover:border-brand-500 cursor-pointer dark:bg-gray-900/50 dark:border-gray-700" />
                {errors.file && <p className="text-xs font-bold text-rose-600 dark:text-rose-455">{errors.file}</p>}
                <Button type="submit" disabled={processing} className="w-full">
                    {processing ? 'Mengunggah...' : 'Import Sekarang'}
                </Button>
            </form>
        </Modal>
    );
}

export default function ProductsIndex({ products, filters , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.status;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route(routePrefix + 'products.index'),
            { search: form.search || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['products', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };
    const destroy = (p) => { if (window.confirm(`Hapus produk ${p.name}?`)) router.delete(route(routePrefix + 'products.destroy', p.id), { preserveScroll: true }); };

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <Layout title="Daftar Produk" pageTitle="Master Data — Produk">
            <Head title="Produk" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Daftar Produk</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{products.meta?.total ?? products.data.length} produk terdaftar</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button onClick={() => setImporting(true)} variant="outline" size="sm" startIcon={<Icon name="upload_file" className="text-[18px]" />}>
                            IMPORT
                        </Button>
                        <Link href={route(routePrefix + 'products.create')}>
                            <Button size="sm" startIcon={<Icon name="add" className="text-[18px]" />}>
                                TAMBAH PRODUK
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[280px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau barcode..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                            <Button type="submit" size="sm">Cari</Button>
                            {hasFilter && <Link href={route(routePrefix + 'products.index')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Produk</th>
                                    <th className="px-6 py-4.5">Kategori</th>
                                    <th className="px-6 py-4.5 text-right">HPP</th>
                                    <th className="px-6 py-4.5 text-right">Harga Jual</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : products.data.length === 0 ? <EmptyState colSpan={6} icon="inventory_2" message="Tidak ada data produk." />
                                    : products.data.map((p) => (
                                        <tr key={p.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                                                        {p.image_url ? <img src={p.image_url} alt="" className="h-full w-full object-cover" /> : <Icon name="inventory_2" className="text-[20px]" />}
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="text-sm font-bold text-gray-800 dark:text-white/90 group-hover:underline cursor-pointer">{p.name}</span>
                                                        <span className="mt-1 font-mono text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{p.code}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-700 dark:text-gray-300">{p.category ?? '—'}</td>
                                            <td className="px-6 py-4.5 text-right text-xs font-bold tabular-nums text-gray-600 dark:text-gray-400">{formatRupiah(p.hpp)}</td>
                                            <td className="px-6 py-4.5 text-right text-xs font-bold tabular-nums text-gray-800 dark:text-white/90">{formatRupiah(p.selling_price)}</td>
                                            <td className="px-6 py-4.5 text-center">
                                                <StatusBadge status={p.status === 'active' ? 'completed' : 'cancelled'} label={p.status === 'active' ? 'Aktif' : 'Off'} />
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'products.edit', p.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-455 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-brand-400">
                                                        <Icon name="edit" className="text-[18px]" />
                                                    </Link>
                                                    <button onClick={() => destroy(p)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-455 transition hover:bg-white hover:text-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-rose-455">
                                                        <Icon name="delete" className="text-[18px]" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {products.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={products.meta.links} /></div>}
                </div>
            </div>

            <ImportModal show={importing} onClose={() => setImporting(false)} routePrefix={routePrefix} />
        </Layout>
    );
}
