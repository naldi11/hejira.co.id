import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const SOURCE_LABELS = {
    purchase_receiving: 'Penerimaan (GRN)',
    transfer_out: 'Transfer Keluar',
    adjustment: 'Penyesuaian (SO)',
    return_receiving: 'Penerimaan Retur',
    receiving_edit: 'Koreksi GRN',
};

export default function StockMovements({ movements, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', type: filters.type ?? '', source: filters.source ?? '' });
    const hasFilter = form.search || form.type || form.source;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.stock.movements'),
            { search: form.search || undefined, type: form.type || undefined, source: form.source || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['movements', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const selectClass = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';

    return (
        <GudangLayout title="Histori Pergerakan Stok" pageTitle="Histori Pergerakan Stok">
            <Head title="Kartu Stok" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Histori Pergerakan Stok</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Log keluar masuk barang dan penyesuaian di Gudang Utama</p>
                    </div>
                    <Link href={route('gudang.stock.index')}>
                        <Button variant="outline" size="sm" startIcon={<Icon name="arrow_back" className="text-[18px]" />}>
                            KEMBALI KE STOK
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama produk..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800" />
                            </div>
                            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={selectClass}>
                                <option value="">Semua Tipe</option>
                                <option value="in">Masuk (In)</option>
                                <option value="out">Keluar (Out)</option>
                            </select>
                            <select value={form.source} onChange={(e) => setForm({ ...form, source: e.target.value })} className={selectClass}>
                                <option value="">Semua Sumber</option>
                                <option value="purchase_receiving">Penerimaan Barang (GRN)</option>
                                <option value="transfer_out">Transfer Keluar (DO)</option>
                                <option value="adjustment">Penyesuaian (SO)</option>
                            </select>
                            <Button type="submit" size="sm">Filter</Button>
                            {hasFilter && <Link href={route('gudang.stock.movements')} className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4">Waktu</th>
                                    <th className="px-6 py-4">Produk</th>
                                    <th className="px-4 py-4">Tipe</th>
                                    <th className="px-4 py-4 text-right">Qty</th>
                                    <th className="px-6 py-4">Sumber</th>
                                    <th className="px-6 py-4">Keterangan</th>
                                    <th className="px-6 py-4">Operator</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? <SkeletonTableRows rows={8} columns={7} />
                                    : movements.data.length === 0 ? <EmptyState colSpan={7} icon="history" message="Belum ada histori pergerakan stok." />
                                    : movements.data.map((m) => {
                                        const isIn = m.type === 'in';
                                        return (
                                            <tr key={m.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 tabular-nums">{m.created_at}</td>
                                                <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{m.product}</td>
                                                <td className="px-4 py-4">
                                                    <span className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold ${isIn ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30'}`}>
                                                        <Icon name={isIn ? 'south_west' : 'north_east'} className="text-[14px]" />{isIn ? 'Masuk' : 'Keluar'}
                                                    </span>
                                                </td>
                                                <td className={`px-4 py-4 text-right font-bold tabular-nums ${isIn ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-605 dark:text-rose-400'}`}>{isIn ? '+' : '-'}{formatQty(m.quantity)}</td>
                                                <td className="px-6 py-4">
                                                    <span className="block font-semibold text-xs text-gray-700 dark:text-gray-300">{SOURCE_LABELS[m.source] ?? m.source}</span>
                                                    {m.doc_number ? (
                                                        <span className="text-[10px] font-bold text-brand-500 dark:text-brand-400">Doc: {m.doc_number}</span>
                                                    ) : m.reference_id ? (
                                                        <span className="text-[10px] font-bold text-brand-500 dark:text-brand-400">ID Ref: {m.reference_id}</span>
                                                    ) : null}
                                                </td>
                                                <td className="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-400">{m.notes || '-'}</td>
                                                <td className="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-400">{m.operator}</td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                    </div>

                    {movements.meta?.links && <div className="border-t border-gray-150 p-5 dark:border-gray-800"><Pagination links={movements.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
