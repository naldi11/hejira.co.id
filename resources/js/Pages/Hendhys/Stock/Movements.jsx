import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';

const route = window.route;
const SOURCES = ['transfer_gudang', 'production', 'transfer_to_branch', 'receive_from_pusat', 'return_from_branch', 'return_to_pusat', 'return_gudang', 'pos_sale', 'adjustment'];

export default function HendhysStockMovements({ movements, branches, products, isPusat, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', branch_id: filters.branch_id ?? '', product_id: filters.product_id ?? '', type: filters.type ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });

    const reload = (e) => {
        e?.preventDefault();
        const params = {};
        Object.entries(form).forEach(([k, v]) => { if (v) params[k] = v; });
        router.get(route('hendhys.stock.movements'), params,
            { preserveState: true, preserveScroll: true, replace: true, only: ['movements', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <HendhysLayout pageTitle="Kartu Stok Hendhys">
            <Head title="Kartu Stok" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">Kartu Stok (Pergerakan)</h2>
                        <p className="text-sm text-gray-500">Riwayat mutasi stok masuk dan keluar</p>
                    </div>
                    <Link href={route('hendhys.stock.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                        <Icon name="inventory" className="text-[20px]" /> Stok Tersedia
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="relative min-w-[200px] flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Cari produk..."
                                    className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            </div>
                            {isPusat && (
                                <select value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Semua Lokasi</option>
                                    <option value="pusat">Pusat</option>
                                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                            )}
                            <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">Semua Tipe</option>
                                <option value="in">Masuk</option>
                                <option value="out">Keluar</option>
                            </select>
                            <input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            <input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} className="rounded-lg border-gray-300 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            <button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                            {Object.values(form).some(v => v) && <Link href={route('hendhys.stock.movements')} className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-red-600 hover:bg-gray-200">Reset</Link>}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Waktu</th>
                                    <th className="px-6 py-4 font-medium">Produk</th>
                                    <th className="px-6 py-4 text-center font-medium">Tipe</th>
                                    <th className="px-6 py-4 text-center font-medium">Qty</th>
                                    <th className="px-6 py-4 font-medium">Sumber</th>
                                    <th className="px-6 py-4 font-medium">Operator</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? <SkeletonTableRows rows={8} columns={6} />
                                    : movements.data.length === 0 ? <EmptyState colSpan={6} icon="history" message="Belum ada pergerakan stok." />
                                    : movements.data.map((m) => (
                                        <tr key={m.id} className="hover:bg-gray-50">
                                            <td className="whitespace-nowrap px-6 py-4 text-xs text-gray-500">{m.created_at}</td>
                                            <td className="px-6 py-4 font-medium text-gray-800">{m.product}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${m.type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{m.type === 'in' ? 'Masuk' : 'Keluar'}</span>
                                            </td>
                                            <td className="px-6 py-4 text-center font-black tabular-nums text-gray-800">{m.quantity}</td>
                                            <td className="px-6 py-4"><span className="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{(m.source ?? '').replace(/_/g, ' ')}</span></td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{m.operator}</td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {movements.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={movements.meta.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
