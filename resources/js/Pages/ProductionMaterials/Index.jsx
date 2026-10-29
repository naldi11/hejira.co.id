import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Layout from '../StockOpname/Layout';

const route = window.route;

export default function Index({ transactions, filters, location, branches = [] }) {
    const [form, setForm] = useState({
        search: '',
        per_page: '20',
        status: '',
        branch_id: '',
        date_from: '',
        date_to: '',
        ...filters,
    });
    const set = (key, value) => setForm({ ...form, [key]: value });

    const hasFilter = Boolean(
        form.search ||
            form.status ||
            form.branch_id ||
            form.date_from ||
            form.date_to ||
            (form.per_page && form.per_page !== '20'),
    );

    const selectCls =
        'rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white';

    return (
        <Layout pageTitle="Riwayat Pemakaian Stok Produksi">
            <Head title="Riwayat Pemakaian Stok Produksi" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Pemakaian Stok untuk Produksi
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Riwayat alokasi bahan produksi di{' '}
                            {location ? location.branch_name : 'seluruh cabang dan gudang aktif'}
                        </p>
                    </div>
                    <Link
                        href={route(
                            'production-materials.create',
                            location ? { branch_id: location.branch_id } : {},
                        )}
                        className="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                    >
                        <Icon name="add" className="text-[18px]" />
                        <span>Gunakan Stok untuk Produksi</span>
                    </Link>
                </div>

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get(route('production-materials.index'), form, { preserveState: true });
                        }}
                        className="flex flex-wrap items-center gap-2.5 border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]"
                    >
                        <div className="relative min-w-[220px] flex-1">
                            <Icon
                                name="search"
                                className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400"
                            />
                            <input
                                type="search"
                                maxLength={100}
                                value={form.search ?? ''}
                                onChange={(e) => set('search', e.target.value)}
                                placeholder="Cari nomor, barang, petugas, atau keperluan…"
                                className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        {branches.length > 1 && (
                            <select
                                value={form.branch_id ?? ''}
                                onChange={(e) => set('branch_id', e.target.value)}
                                className={selectCls}
                            >
                                <option value="">Semua Cabang & Gudang</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {({ gudang: 'Gudang', hendhys: 'Hendhys', jihans: 'Jihans' })[b.entity]} — {b.name}
                                    </option>
                                ))}
                            </select>
                        )}

                        <select
                            value={form.status ?? ''}
                            onChange={(e) => set('status', e.target.value)}
                            className={selectCls}
                        >
                            <option value="">Semua Status</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>

                        <input
                            type="date"
                            value={form.date_from ?? ''}
                            onChange={(e) => set('date_from', e.target.value)}
                            className={selectCls}
                        />
                        <span className="text-xs text-gray-400">s/d</span>
                        <input
                            type="date"
                            min={form.date_from || undefined}
                            value={form.date_to ?? ''}
                            onChange={(e) => set('date_to', e.target.value)}
                            className={selectCls}
                        />

                        <button
                            type="submit"
                            className="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600"
                        >
                            Cari
                        </button>

                        {hasFilter && (
                            <Link
                                href={route('production-materials.index')}
                                className="rounded-lg bg-red-50 px-3.5 py-2 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400"
                            >
                                Reset
                            </Link>
                        )}
                    </form>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">No. Transaksi & Waktu</th>
                                    <th className="px-5 py-3.5">Cabang & Petugas</th>
                                    <th className="px-5 py-3.5">Keperluan Produksi</th>
                                    <th className="px-5 py-3.5">Barang Digunakan</th>
                                    <th className="px-5 py-3.5 text-center">Status</th>
                                    <th className="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {transactions.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={6}
                                        icon="factory"
                                        message="Belum ada catatan pemakaian stok untuk produksi."
                                    />
                                ) : (
                                    transactions.data.map((t) => (
                                        <tr
                                            key={t.id}
                                            className="align-top hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="whitespace-nowrap px-5 py-4">
                                                <Link
                                                    href={route('production-materials.show', t.id)}
                                                    className="font-mono font-bold text-brand-600 hover:underline dark:text-brand-400"
                                                >
                                                    {t.number}
                                                </Link>
                                                <p className="mt-0.5 text-xs text-gray-400">{t.occurred_label}</p>
                                            </td>
                                            <td className="px-5 py-4 text-xs">
                                                <p className="font-semibold text-gray-800 dark:text-white">
                                                    {t.branch_name}
                                                </p>
                                                <p className="text-gray-500">Oleh: {t.receiver_name}</p>
                                            </td>
                                            <td className="max-w-xs px-5 py-4 text-xs text-gray-600 dark:text-gray-300">
                                                <p className="font-medium text-gray-800 dark:text-white">{t.reason}</p>
                                                {t.cancel_reason && (
                                                    <p className="mt-1 text-rose-600">Batal: {t.cancel_reason}</p>
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="space-y-1 text-xs">
                                                    {t.items.slice(0, 3).map((i) => (
                                                        <div
                                                            key={i.id}
                                                            className="flex items-center justify-between gap-2 rounded bg-gray-50 px-2.5 py-1 dark:bg-gray-800"
                                                        >
                                                            <span className="font-medium text-gray-800 dark:text-white">
                                                                {i.product_name}
                                                            </span>
                                                            <strong>
                                                                {i.quantity} {i.unit_name}
                                                            </strong>
                                                        </div>
                                                    ))}
                                                    {t.items.length > 3 && (
                                                        <p className="text-[11px] text-gray-400">
                                                            +{t.items.length - 3} barang lainnya
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                        t.status === 'cancelled'
                                                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400'
                                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                    }`}
                                                >
                                                    {t.status_label}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-right">
                                                <Link
                                                    href={route('production-materials.show', t.id)}
                                                    className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                >
                                                    <Icon name="visibility" className="text-[15px]" />
                                                    <span>Detail</span>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {transactions.total > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            <p className="text-xs text-gray-500">
                                Menampilkan <strong>{transactions.from || 0}–{transactions.to || 0}</strong> dari{' '}
                                <strong>{transactions.total}</strong> transaksi
                            </p>
                            <Pagination links={transactions.links} />
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
