import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Layout from '../StockOpname/Layout';

const route = window.route;

export default function Index({ transfers, branches, filters, canCreate, isHistory }) {
    const defaults = {
        search: '',
        status: '',
        from_branch_id: '',
        to_branch_id: '',
        date_from: '',
        date_to: '',
        per_page: '20',
    };
    const [form, setForm] = useState({
        ...defaults,
        ...Object.fromEntries(Object.entries(filters).map(([key, value]) => [key, value ?? ''])),
    });
    const [showAdvanced, setShowAdvanced] = useState(
        Boolean(filters.status || filters.from_branch_id || filters.to_branch_id),
    );

    const set = (key, value) => setForm({ ...form, [key]: value });
    const indexRoute = isHistory ? 'branch-transfers.history' : 'branch-transfers.index';
    const title = isHistory ? 'Riwayat Semua Mutasi Stok' : 'Mutasi Stok Cabang & Gudang';

    const hasFilter = Boolean(
        form.search ||
            form.status ||
            form.from_branch_id ||
            form.to_branch_id ||
            form.date_from ||
            form.date_to ||
            (form.per_page && form.per_page !== '20'),
    );

    const statusBadge = (status) => {
        if (status === 'received')
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400';
        if (status === 'cancelled')
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400';
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400';
    };

    const selectCls =
        'rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white';

    return (
        <Layout pageTitle={title}>
            <Head title={title} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{title}</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Pencatatan perpindahan stok antar cabang dan gudang
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <div className="inline-flex rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
                            <Link
                                href={route('branch-transfers.index')}
                                className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
                                    !isHistory
                                        ? 'bg-brand-500 text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'
                                }`}
                            >
                                Hari Ini
                            </Link>
                            <Link
                                href={route('branch-transfers.history')}
                                className={`rounded-lg px-3.5 py-1.5 text-xs font-semibold transition ${
                                    isHistory
                                        ? 'bg-brand-500 text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'
                                }`}
                            >
                                Semua Riwayat
                            </Link>
                        </div>

                        {canCreate && (
                            <Link
                                href={route('branch-transfers.create')}
                                className="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                            >
                                <Icon name="add" className="text-[18px]" />
                                <span>Catat Mutasi Baru</span>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Main Card: Compact Filter + Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    {/* Compact Filter Bar */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get(route(indexRoute), form, { preserveState: true });
                        }}
                        className="space-y-3 border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]"
                    >
                        <div className="flex flex-wrap items-center gap-2.5">
                            <div className="relative min-w-[240px] flex-1">
                                <Icon
                                    name="search"
                                    className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400"
                                />
                                <input
                                    type="search"
                                    aria-label="Cari mutasi"
                                    maxLength={100}
                                    placeholder="Cari No. Mutasi, barang, cabang, atau petugas…"
                                    value={form.search}
                                    onChange={(e) => set('search', e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>

                            <input
                                type="date"
                                aria-label="Tanggal mulai"
                                value={form.date_from}
                                onChange={(e) => set('date_from', e.target.value)}
                                className={selectCls}
                            />
                            <span className="text-xs text-gray-400">s/d</span>
                            <input
                                type="date"
                                aria-label="Tanggal sampai"
                                min={form.date_from || undefined}
                                value={form.date_to}
                                onChange={(e) => set('date_to', e.target.value)}
                                className={selectCls}
                            />

                            <button
                                type="button"
                                onClick={() => setShowAdvanced(!showAdvanced)}
                                className={`inline-flex items-center gap-1.5 rounded-lg border px-3.5 py-2 text-sm font-medium transition ${
                                    showAdvanced || form.status || form.from_branch_id || form.to_branch_id
                                        ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                }`}
                            >
                                <Icon name="filter_list" className="text-[18px]" />
                                <span>Filter Cabang</span>
                            </button>

                            <button
                                type="submit"
                                className="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                            >
                                Cari
                            </button>

                            {hasFilter && (
                                <Link
                                    href={route(indexRoute)}
                                    className="rounded-lg bg-red-50 px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400"
                                >
                                    Reset
                                </Link>
                            )}
                        </div>

                        {showAdvanced && (
                            <div className="grid grid-cols-1 gap-2.5 border-t border-gray-200/70 pt-3 sm:grid-cols-4 dark:border-gray-800">
                                <select
                                    aria-label="Status"
                                    value={form.status}
                                    onChange={(e) => set('status', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Status</option>
                                    <option value="received">Selesai Diterima</option>
                                    <option value="cancelled">Dibatalkan</option>
                                    <option value="sent">Dalam Perjalanan</option>
                                    <option value="partial">Diterima Sebagian</option>
                                </select>

                                <select
                                    aria-label="Cabang asal"
                                    value={form.from_branch_id}
                                    onChange={(e) => set('from_branch_id', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Cabang Asal</option>
                                    {branches.map((b) => (
                                        <option key={b.id} value={b.id}>
                                            Asal: {b.name}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    aria-label="Lokasi tujuan"
                                    value={form.to_branch_id}
                                    onChange={(e) => set('to_branch_id', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Lokasi Tujuan</option>
                                    {branches.map((b) => (
                                        <option key={b.id} value={b.id}>
                                            Tujuan: {b.name}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    aria-label="Data per halaman"
                                    value={form.per_page}
                                    onChange={(e) => set('per_page', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="20">20 baris / halaman</option>
                                    <option value="50">50 baris / halaman</option>
                                    <option value="100">100 baris / halaman</option>
                                    <option value="all">Semua data</option>
                                </select>
                            </div>
                        )}
                    </form>

                    {/* Data Table */}
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">No. Mutasi & Tanggal</th>
                                    <th className="px-5 py-3.5">Asal → Tujuan</th>
                                    <th className="px-5 py-3.5">Pengirim & Penerima</th>
                                    <th className="px-5 py-3.5">Barang Dimutasi</th>
                                    <th className="px-5 py-3.5 text-center">Status</th>
                                    <th className="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {transfers.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={6}
                                        icon="swap_horiz"
                                        message="Belum ada data mutasi stok pada rentang tanggal ini."
                                    />
                                ) : (
                                    transfers.data.map((t) => (
                                        <tr
                                            key={t.id}
                                            className="align-top hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="whitespace-nowrap px-5 py-4">
                                                <Link
                                                    href={route('branch-transfers.show', t.id)}
                                                    className="font-mono font-bold text-brand-600 hover:underline dark:text-brand-400"
                                                >
                                                    {t.number}
                                                </Link>
                                                <p className="mt-0.5 text-xs text-gray-500">{t.date}</p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex items-center gap-1.5 font-semibold text-gray-800 dark:text-white">
                                                    <span>{t.from_branch_name}</span>
                                                    <Icon name="arrow_forward" className="text-[15px] text-brand-500" />
                                                    <span>{t.to_branch_name}</span>
                                                </div>
                                                <p className="mt-0.5 max-w-xs truncate text-xs text-gray-500">
                                                    {t.reason}
                                                </p>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-xs text-gray-600 dark:text-gray-300">
                                                <p>
                                                    Pengirim:{' '}
                                                    <strong className="text-gray-800 dark:text-white">
                                                        {t.physical_sender_name || t.sent_by_name}
                                                    </strong>
                                                </p>
                                                <p className="mt-0.5">
                                                    Penerima:{' '}
                                                    <strong className="text-gray-800 dark:text-white">
                                                        {t.receiver_names.length
                                                            ? t.receiver_names.join(', ')
                                                            : 'Belum diterima'}
                                                    </strong>
                                                </p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="space-y-1 text-xs">
                                                    {t.items.slice(0, 3).map((i, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between gap-2 rounded bg-gray-50 px-2.5 py-1 dark:bg-gray-800"
                                                        >
                                                            <span className="font-medium text-gray-800 dark:text-white">
                                                                {i.name}
                                                            </span>
                                                            <strong className="text-gray-700 dark:text-gray-300">
                                                                {i.quantity} {i.unit}
                                                            </strong>
                                                        </div>
                                                    ))}
                                                    {t.items.length > 3 && (
                                                        <p className="text-[11px] font-medium text-gray-400">
                                                            +{t.items.length - 3} jenis barang lainnya
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusBadge(
                                                        t.status,
                                                    )}`}
                                                >
                                                    {t.status_label}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-right">
                                                <Link
                                                    href={route('branch-transfers.show', t.id)}
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

                    {transfers.total > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            <p className="text-xs text-gray-500">
                                Menampilkan <strong>{transfers.from || 0}–{transfers.to || 0}</strong> dari{' '}
                                <strong>{transfers.total}</strong> mutasi
                            </p>
                            <Pagination links={transfers.links} />
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
