import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate, formatRupiah } from '@/lib/format';
import Button from '@/components/ui/button/Button';

const route = window.route;

export default function PurchaseOrdersIndex({ orders, filters }) {
    const [loading, setLoading] = useState(false);
    const [form, setForm] = useState({ search: filters.search ?? '', status: filters.status ?? '' });
    const hasFilter = form.search || form.status;

    const reload = (e) => {
        e?.preventDefault();
        router.get(route('gudang.po.index'),
            { search: form.search || undefined, status: form.status || undefined },
            { preserveState: true, preserveScroll: true, replace: true, only: ['orders', 'filters'], onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    return (
        <GudangLayout title="Purchase Order" pageTitle="Purchase Order">
            <Head title="Purchase Order" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Pesanan Pembelian (PO)</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Kelola pesanan stok barang ke supplier/vendor</p>
                    </div>
                    <Link href={route('gudang.po.create')}>
                        <Button size="sm" startIcon={<Icon name="add_shopping_cart" className="text-[18px]" />}>
                            BUAT PO BARU
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-4">
                            <div className="relative min-w-[280px] flex-1">
                                <Icon name="search" className="absolute left-4 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                <input
                                    type="text"
                                    value={form.search}
                                    onChange={(e) => setForm({ ...form, search: e.target.value })}
                                    placeholder="Cari No. PO atau Supplier..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800"
                                />
                            </div>
                            <select
                                value={form.status}
                                onChange={(e) => setForm({ ...form, status: e.target.value })}
                                className="h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm font-semibold text-gray-700 outline-hidden transition focus:border-brand-500 dark:border-gray-700 dark:text-gray-200 dark:bg-gray-900/50"
                            >
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="sent">Terkirim</option>
                                <option value="partial">Sebagian</option>
                                <option value="received">Diterima</option>
                                <option value="cancelled">Batal</option>
                            </select>
                            <button
                                type="submit"
                                className="h-11 rounded-lg bg-gray-950 px-6 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-brand-500 dark:hover:bg-brand-600 dark:disabled:bg-brand-300"
                            >
                                Cari
                            </button>
                            {hasFilter && (
                                <Link
                                    href={route('gudang.po.index')}
                                    className="flex h-11 w-11 items-center justify-center rounded-lg border border-gray-255 bg-gray-50 text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    <Icon name="refresh" />
                                </Link>
                            )}
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Dokumen</th>
                                    <th className="px-6 py-4.5">Supplier</th>
                                    <th className="px-6 py-4.5 text-right">Nilai Pesanan</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? (
                                    <SkeletonTableRows rows={6} columns={5} />
                                ) : orders.data.length === 0 ? (
                                    <EmptyState colSpan={5} icon="shopping_basket" message="Belum ada data Purchase Order." />
                                ) : (
                                    orders.data.map((po) => (
                                        <tr key={po.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5">
                                                <Link href={route('gudang.po.show', po.id)} className="flex flex-col">
                                                    <span className="text-sm font-bold tracking-tight text-brand-500 dark:text-brand-400 group-hover:underline">
                                                        {po.po_number}
                                                    </span>
                                                    <span className="mt-1 text-[10px] font-semibold text-gray-400 dark:text-gray-500">
                                                        {formatDate(po.date)}
                                                    </span>
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                        <Icon name="factory" className="text-[16px]" />
                                                    </div>
                                                    <span className="text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                                                        {po.supplier}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-right text-sm font-bold tabular-nums text-gray-800 dark:text-white/90">
                                                {formatRupiah(po.total_amount)}
                                            </td>
                                            <td className="px-6 py-4.5 text-center">
                                                <StatusBadge status={po.status} />
                                            </td>
                                            <td className="px-6 py-4.5 text-right">
                                                <Link
                                                    href={route('gudang.po.show', po.id)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-brand-400"
                                                >
                                                    Detail
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {orders.meta?.links && (
                        <div className="border-t border-gray-150 p-5 dark:border-gray-800">
                            <Pagination links={orders.meta.links} />
                        </div>
                    )}
                </div>
            </div>
        </GudangLayout>
    );
}

