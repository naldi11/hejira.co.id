import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

const route = window.route;
const axios = window.axios;

export default function JihansPendingIndex({ pendings, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');

    // Resume a held transaction: load its cart into localStorage, remove the hold,
    // then open the POS (which reads `jihans_resume_cart` on mount).
    const resume = async (p) => {
        try {
            const { data } = await axios.get(route('jihans.pending.show', p.id));
            const items = (data.details ?? []).map((d) => ({
                product_id: d.product_id,
                product_name: d.product_name ?? d.product?.name,
                product_code: d.product?.code,
                barcode: d.product?.barcode,
                price: Number(d.price),
                quantity: Number(d.quantity),
                discount: 0,
                unit_name: d.product?.unit?.abbreviation,
                max_stock: 999999,
            }));
            localStorage.setItem('jihans_resume_cart', JSON.stringify({
                items, customerId: data.customer_id ?? '', customerName: data.customer_name ?? '',
                customerType: data.customer_type ?? 'Pelanggan Retail', notes: data.notes ?? '',
            }));
            await axios.delete(route('jihans.pending.destroy', p.id), { headers: { Accept: 'application/json' } });
            window.location.href = route('jihans.pos.index');
        } catch {
            alert('Gagal memuat transaksi pending.');
        }
    };

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('jihans.pending.index'),
            { search: search || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['pendings', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const destroy = (p) => {
        if (window.confirm(`Hapus transaksi pending ${p.pending_number}?`)) {
            router.delete(route('jihans.pending.destroy', p.id), { preserveScroll: true });
        }
    };

    return (
        <JihansLayout pageTitle="Transaksi Pending (Hold)">
            <Head title="Transaksi Pending" />

            <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Transaksi Pending</h2>
                    <p className="text-sm text-gray-500">Daftar transaksi yang ditahan (hold) untuk dilanjutkan nanti</p>
                </div>
                <div className="flex w-full gap-2 sm:w-auto">
                    <Link href={route('jihans.pos.index')} className="flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-orange-700">
                        <Icon name="point_of_sale" className="text-[18px]" /> Buka Kasir
                    </Link>
                    <form onSubmit={reload} className="flex flex-1 gap-2">
                        <div className="relative flex-1 sm:w-64">
                            <Icon name="search" className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari No. Hold / Pelanggan..." className="w-full rounded-lg border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                        </div>
                        <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                    </form>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                            <tr><th className="px-6 py-4 font-medium">No. Hold</th><th className="px-6 py-4 font-medium">Tanggal</th><th className="px-6 py-4 font-medium">Pelanggan</th><th className="px-6 py-4 text-center font-medium">Jml Item</th><th className="px-6 py-4 font-medium">Kasir</th><th className="px-6 py-4 text-right font-medium">Aksi</th></tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? <SkeletonTableRows rows={6} columns={6} />
                                : pendings.data.length === 0 ? <EmptyState colSpan={6} icon="schedule" message="Tidak ada transaksi pending." />
                                : pendings.data.map((p) => (
                                    <tr key={p.id} className="transition-colors hover:bg-gray-50">
                                        <td className="px-6 py-4"><span className="font-mono font-semibold text-gray-800">{p.pending_number}</span></td>
                                        <td className="px-6 py-4 text-gray-600">{formatDate(p.date)}</td>
                                        <td className="px-6 py-4">
                                            <p className="font-medium text-gray-800">{p.customer_name}</p>
                                            <p className="text-xs capitalize text-gray-500">{p.customer_type}</p>
                                        </td>
                                        <td className="px-6 py-4 text-center"><span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-700">{p.items_count}</span></td>
                                        <td className="px-6 py-4 text-gray-500">{p.creator ?? '-'}</td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => resume(p)} className="inline-flex items-center gap-1 rounded border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-medium text-orange-700 transition-colors hover:bg-orange-100">
                                                    <Icon name="play_arrow" className="text-[16px]" /> Lanjutkan
                                                </button>
                                                <button onClick={() => destroy(p)} className="inline-flex items-center gap-1 rounded border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-100">
                                                    <Icon name="delete" className="text-[16px]" /> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                        </tbody>
                    </table>
                </div>
                {pendings.meta?.links && <div className="border-t border-gray-100 p-4"><Pagination links={pendings.meta.links} /></div>}
            </div>
        </JihansLayout>
    );
}
