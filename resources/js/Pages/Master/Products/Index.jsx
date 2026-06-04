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

const route = window.route;

function ImportModal({ show, onClose }) {
    const { setData, post, processing, errors, reset } = useForm({ file: null });
    const submit = (e) => {
        e.preventDefault();
        post(route(routePrefix + 'products.import'), { forceFormData: true, onSuccess: () => { reset(); onClose(); } });
    };
    return (
        <Modal show={show} onClose={onClose} title="Import Produk" subtitle="Unggah file Excel/CSV" icon="upload_file">
            <form onSubmit={submit} className="space-y-6">
                <a href={route(routePrefix + 'products.template')} className="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:underline"><Icon name="download" className="text-[18px]" /> Unduh template terlebih dahulu</a>
                <input type="file" required accept=".xlsx,.xls,.csv" onChange={(e) => setData('file', e.target.files[0])} className="w-full rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm" />
                {errors.file && <p className="text-xs font-bold text-rose-600">{errors.file}</p>}
                <button type="submit" disabled={processing} className="w-full rounded-2xl bg-slate-900 px-8 py-4 text-sm font-black uppercase tracking-widest text-white transition-all hover:bg-indigo-600 disabled:opacity-50">{processing ? 'Mengunggah...' : 'Import Sekarang'}</button>
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

    return (
        <Layout title="Daftar Produk" pageTitle="Master Data — Produk">
            <Head title="Produk" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black text-slate-900">Daftar Produk</h2>
                        <p className="mt-1 text-sm font-medium text-slate-500">{products.meta?.total ?? products.data.length} produk terdaftar</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <button onClick={() => setImporting(true)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-bold uppercase tracking-widest text-slate-600 transition-all hover:bg-slate-50"><Icon name="upload_file" className="text-[18px]" /> Import</button>
                        <Link href={route(routePrefix + 'products.create')} className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-indigo-600/20 transition-all hover:bg-indigo-700"><Icon name="add" className="text-[18px]" /> Tambah Produk</Link>
                    </div>
                </div>

                <div className="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <form onSubmit={reload} className="flex flex-wrap gap-4">
                        <div className="relative min-w-[280px] flex-1">
                            <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama, kode, atau barcode..."
                                className="w-full rounded-2xl border-2 border-slate-50 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10" />
                        </div>
                        <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="min-w-[180px] cursor-pointer rounded-2xl border-2 border-slate-50 bg-slate-50 px-4 py-3 text-sm outline-none transition-all focus:border-indigo-500 focus:bg-white">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                        <button type="submit" className="flex items-center gap-2 rounded-2xl bg-slate-900 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-slate-800"><Icon name="filter_list" className="text-[18px]" /> Cari</button>
                        {hasFilter && <Link href={route(routePrefix + 'products.index')} className="flex items-center gap-2 rounded-2xl bg-rose-50 px-6 py-3 text-xs font-bold uppercase tracking-widest text-rose-600 transition-all hover:bg-rose-100"><Icon name="close" className="text-[18px]" /> Reset</Link>}
                    </form>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Produk</th>
                                    <th className="px-6 py-4">Kategori</th>
                                    <th className="px-6 py-4 text-right">HPP</th>
                                    <th className="px-6 py-4 text-right">Harga Jual</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : products.data.length === 0 ? <EmptyState colSpan={6} icon="inventory_2" message="Tidak ada data produk." />
                                    : products.data.map((p) => (
                                        <tr key={p.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100 text-slate-300">
                                                        {p.image_url ? <img src={p.image_url} alt="" className="h-full w-full object-cover" /> : <Icon name="inventory_2" className="text-[20px]" />}
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="text-sm font-black text-slate-800">{p.name}</span>
                                                        <span className="font-mono text-[10px] font-bold uppercase tracking-widest text-slate-400">{p.code}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-bold text-slate-500">{p.category ?? '-'}</td>
                                            <td className="px-6 py-4 text-right text-sm font-bold tabular-nums text-slate-600">{formatRupiah(p.hpp)}</td>
                                            <td className="px-6 py-4 text-right text-sm font-black tabular-nums text-slate-900">{formatRupiah(p.selling_price)}</td>
                                            <td className="px-6 py-4 text-center"><StatusBadge status={p.status === 'active' ? 'completed' : 'cancelled'} label={p.status === 'active' ? 'Aktif' : 'Off'} /></td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'products.edit', p.id)} className="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-indigo-50 hover:text-indigo-600"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(p)} className="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {products.meta?.links && <div className="border-t border-slate-100 px-6 py-4"><Pagination links={products.meta.links} /></div>}
                </div>
            </div>

            <ImportModal show={importing} onClose={() => setImporting(false)} />
        </Layout>
    );
}
