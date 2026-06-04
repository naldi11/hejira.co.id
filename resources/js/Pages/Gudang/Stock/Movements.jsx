import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';

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

    const selectClass = 'rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10';

    return (
        <GudangLayout title="Histori Pergerakan Stok" pageTitle="Histori Pergerakan Stok">
            <Head title="Kartu Stok" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Histori Pergerakan Stok</h2>
                        <p className="text-sm font-medium text-slate-500">Log keluar masuk barang dan penyesuaian di Gudang Utama</p>
                    </div>
                    <Link href={route('gudang.stock.index')} className="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50">Kembali ke Data Stok</Link>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[250px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[20px] text-slate-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama produk..."
                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm transition-all focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" />
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
                            <button type="submit" className="rounded-2xl bg-slate-900 px-8 py-3 text-sm font-black uppercase tracking-widest text-white shadow-lg shadow-slate-900/10 transition-all hover:bg-indigo-600">Filter</button>
                            {hasFilter && <Link href={route('gudang.stock.movements')} className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 transition-all hover:bg-rose-100"><Icon name="refresh" /></Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-4 py-4">Waktu</th>
                                    <th className="px-4 py-4">Produk</th>
                                    <th className="px-4 py-4">Tipe</th>
                                    <th className="px-4 py-4 text-right">Qty</th>
                                    <th className="px-4 py-4">Sumber</th>
                                    <th className="px-4 py-4">Keterangan</th>
                                    <th className="px-4 py-4">Operator</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {loading ? <SkeletonTableRows rows={8} columns={7} />
                                    : movements.data.length === 0 ? <EmptyState colSpan={7} icon="history" message="Belum ada histori pergerakan stok." />
                                    : movements.data.map((m) => {
                                        const isIn = m.type === 'in';
                                        return (
                                            <tr key={m.id} className="transition-colors hover:bg-slate-50/50">
                                                <td className="px-4 py-3 text-xs text-slate-500">{m.created_at}</td>
                                                <td className="px-4 py-3 font-medium text-slate-800">{m.product}</td>
                                                <td className="px-4 py-3">
                                                    <span className={`inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold ${isIn ? 'bg-green-50 text-green-600' : 'bg-rose-50 text-rose-600'}`}>
                                                        <Icon name={isIn ? 'south_west' : 'north_east'} className="text-[14px]" />{isIn ? 'Masuk' : 'Keluar'}
                                                    </span>
                                                </td>
                                                <td className={`px-4 py-3 text-right font-bold ${isIn ? 'text-green-600' : 'text-rose-600'}`}>{isIn ? '+' : '-'}{formatQty(m.quantity)}</td>
                                                <td className="px-4 py-3">
                                                    <span className="block font-mono text-xs text-slate-500">{SOURCE_LABELS[m.source] ?? m.source}</span>
                                                    {m.reference_id && <span className="text-xs text-indigo-600">ID: {m.reference_id}</span>}
                                                </td>
                                                <td className="px-4 py-3 text-xs text-slate-500">{m.notes || '-'}</td>
                                                <td className="px-4 py-3 text-xs text-slate-500">{m.operator}</td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                    </div>

                    {movements.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={movements.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
