import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatQty } from '@/lib/format';

const route = window.route;

const SOURCE_LABELS = {
    transfer_gudang: 'Transfer dari Gudang',
    production: 'Hasil Produksi',
    receive_from_gudang: 'Terima dari Gudang',
    return_gudang: 'Retur ke Gudang',
    pos_sale: 'Penjualan POS',
    adjustment: 'Penyesuaian',
};

export default function JihansStockMovements({ movements, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', type: filters.type ?? '' });
    const hasFilter = form.search || form.type;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.stock.movements'),
            { search: form.search || undefined, type: form.type || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['movements', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <JihansLayout pageTitle="Histori Pergerakan Stok">
            <Head title="Kartu Stok" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">Histori Pergerakan Stok</h2>
                        <p className="text-sm text-gray-500">Log keluar masuk barang di Jihan's Food</p>
                    </div>
                    <Link href={route('jihans.stock.index')} className="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">Kembali ke Stok</Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[260px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari nama produk..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                            </div>
                            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Semua Tipe</option>
                                <option value="in">Masuk (In)</option>
                                <option value="out">Keluar (Out)</option>
                            </select>
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                            {hasFilter && <Link href={route('jihans.stock.movements')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-4 py-4 font-medium">Waktu</th>
                                    <th className="px-4 py-4 font-medium">Produk</th>
                                    <th className="px-4 py-4 font-medium">Tipe</th>
                                    <th className="px-4 py-4 text-right font-medium">Qty</th>
                                    <th className="px-4 py-4 font-medium">Sumber</th>
                                    <th className="px-4 py-4 font-medium">Operator</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : movements.data.length === 0 ? <EmptyState colSpan={6} icon="history" message="Belum ada histori pergerakan stok." />
                                    : movements.data.map((m) => {
                                        const isIn = m.type === 'in';
                                        return (
                                            <tr key={m.id} className="transition-colors hover:bg-gray-50">
                                                <td className="px-4 py-3 text-xs text-gray-500">{m.created_at}</td>
                                                <td className="px-4 py-3 font-medium text-gray-800">{m.product}</td>
                                                <td className="px-4 py-3"><span className={`inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold ${isIn ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'}`}><Icon name={isIn ? 'south_west' : 'north_east'} className="text-[14px]" />{isIn ? 'Masuk' : 'Keluar'}</span></td>
                                                <td className={`px-4 py-3 text-right font-bold ${isIn ? 'text-green-600' : 'text-red-600'}`}>{isIn ? '+' : '-'}{formatQty(m.quantity)}</td>
                                                <td className="px-4 py-3 text-xs text-gray-500">{SOURCE_LABELS[m.source] ?? m.source}</td>
                                                <td className="px-4 py-3 text-xs text-gray-500">{m.operator}</td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                    </div>
                    {movements.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={movements.meta.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
