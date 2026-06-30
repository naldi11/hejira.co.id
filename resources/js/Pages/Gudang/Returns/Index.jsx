import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

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

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <GudangLayout title="Penerimaan Retur" pageTitle="Gudang — Penerimaan Retur">
            <Head title="Penerimaan Retur" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Penerimaan Retur Barang</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Penerimaan kembali barang retur dari Hendhys Produksi atau Jihan's Food ke Gudang Utama</p>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari No. Retur..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
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
                            <Button type="submit" size="sm">Filter</Button>
                            {hasFilter && <Link href={route('gudang.returns.index')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Tanggal</th>
                                    <th className="px-6 py-4.5">No. Retur</th>
                                    <th className="px-6 py-4.5">Asal Entitas</th>
                                    <th className="px-6 py-4.5 text-center">Jumlah Item</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={6} columns={6} />
                                    : returns.data.length === 0 ? <EmptyState colSpan={6} icon="keyboard_return" message="Belum ada data retur barang masuk." />
                                    : returns.data.map((ret) => (
                                        <tr key={ret.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-500 dark:text-gray-400 tabular-nums">{formatDate(ret.date)}</td>
                                            <td className="px-6 py-4.5 text-sm font-bold text-brand-500 dark:text-brand-400 group-hover:underline">{ret.return_number}</td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-2">
                                                    <span className={`h-2 w-2 rounded-full ${ret.from_entity === 'hendhys' ? 'bg-blue-500' : 'bg-purple-500'}`} />
                                                    <span className="text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">{ret.from_entity}</span>
                                                    <span className="text-[10px] font-semibold text-gray-400 dark:text-gray-500">({ret.branch ?? (ret.from_entity === 'hendhys' ? 'Pusat' : 'Produksi')})</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-center text-xs font-bold text-gray-700 dark:text-gray-300">{ret.details_count} Jenis</td>
                                            <td className="px-6 py-4.5 text-center"><StatusBadge status={ret.status} /></td>
                                            <td className="px-6 py-4.5 text-right">
                                                <Link href={route('gudang.returns.show', ret.id)} className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-brand-400">
                                                    {ret.status === 'sent' ? 'Proses Penerimaan' : 'Detail'}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>

                    {returns.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={returns.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
