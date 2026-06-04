import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;

export default function ReturnsIndex({ returns, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', entity: filters.entity ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.entity || form.status;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.returns.index'),
            { search: form.search || undefined, entity: form.entity || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['returns', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const selectClass = 'rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10';

    return (
        <GudangLayout title="Penerimaan Retur" pageTitle="Gudang — Penerimaan Retur">
            <Head title="Penerimaan Retur" />

            <div className="space-y-6">
                <div>
                    <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Penerimaan Retur Barang</h2>
                    <p className="text-sm font-medium text-slate-500">Penerimaan kembali barang retur dari Hendhys Pusat atau Jihan's Food ke Gudang Utama</p>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Retur..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
                            </div>
                            <select value={form.entity} onChange={(e) => setForm({ ...form, entity: e.target.value })} className={selectClass}>
                                <option value="">Semua Asal</option>
                                <option value="hendhys">Hendhys (Pusat)</option>
                                <option value="jihans">Jihans (Food)</option>
                            </select>
                            <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={selectClass}>
                                <option value="">Semua Status</option>
                                <option value="sent">Dalam Perjalanan</option>
                                <option value="received">Diterima Gudang</option>
                            </select>
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && <Link href={route('gudang.returns.index')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Tanggal</th>
                                    <th className="px-6 py-4">No. Retur</th>
                                    <th className="px-6 py-4">Asal Entitas</th>
                                    <th className="px-6 py-4 text-center">Jumlah Item</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : returns.data.length === 0 ? <EmptyState colSpan={6} icon="keyboard_return" message="Belum ada data retur barang masuk." />
                                    : returns.data.map((ret) => (
                                        <tr key={ret.id} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4 text-sm text-slate-500">{formatDate(ret.date)}</td>
                                            <td className="px-6 py-4 text-sm font-black text-slate-800">{ret.return_number}</td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-2 text-sm text-slate-700">
                                                    <span className={`h-2 w-2 rounded-full ${ret.from_entity === 'hendhys' ? 'bg-blue-500' : 'bg-purple-500'}`} />
                                                    <span className="font-bold capitalize">{ret.from_entity}</span>
                                                    <span className="text-xs text-slate-400">({ret.branch ?? (ret.from_entity === 'hendhys' ? 'Pusat' : 'Produksi')})</span>
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-center text-sm text-slate-600">{ret.details_count} jenis</td>
                                            <td className="px-6 py-4 text-center"><StatusBadge status={ret.status} /></td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('gudang.returns.show', ret.id)} className="rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-black text-indigo-700 transition-all hover:bg-indigo-100">
                                                    {ret.status === 'sent' ? 'Proses Penerimaan' : 'Lihat Detail'}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {returns.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={returns.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
