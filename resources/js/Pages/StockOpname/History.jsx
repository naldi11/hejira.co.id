import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Layout from './Layout';

const route = window.route;

export default function History({ counts, filters, actors, locations, summary }) {
    const [form, setForm] = useState({
        search: '',
        location_key: '',
        counted_by: '',
        session_id: '',
        product_id: '',
        difference: '',
        date_from: '',
        date_to: '',
        ...Object.fromEntries(Object.entries(filters).map(([k, v]) => [k, v ?? ''])),
    });
    const [showAdvanced, setShowAdvanced] = useState(
        Boolean(filters.location_key || filters.counted_by || filters.difference || filters.session_id),
    );
    const set = (key, value) => setForm({ ...form, [key]: value });

    const hasFilter = Boolean(
        form.search ||
            form.location_key ||
            form.counted_by ||
            form.difference ||
            form.date_from ||
            form.date_to ||
            form.session_id ||
            form.product_id,
    );

    const selectCls =
        'rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white';

    return (
        <Layout pageTitle="Riwayat Stock Opname">
            <Head title="Riwayat Stock Opname" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Riwayat Hasil Stock Opname
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Daftar penyesuaian stok fisik per produk, petugas, dan sesi
                        </p>
                    </div>
                    <Link
                        href={route('stock-opname.index')}
                        className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <Icon name="arrow_back" className="text-[18px]" />
                        <span>Daftar Sesi SO</span>
                    </Link>
                </div>

                {/* 4 Summary Cards */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {[
                        ['total', 'Total Hasil SO', 'text-gray-800 dark:text-white'],
                        ['decreased', 'Stok Berkurang', 'text-rose-600 dark:text-rose-400'],
                        ['increased', 'Stok Bertambah', 'text-emerald-600 dark:text-emerald-400'],
                        ['unchanged', 'Jumlah Sesuai', 'text-blue-600 dark:text-blue-400'],
                    ].map(([key, label, cls]) => (
                        <div
                            key={key}
                            className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]"
                        >
                            <p className="text-xs font-medium text-gray-500">{label}</p>
                            <strong className={`mt-1 block text-2xl font-bold ${cls}`}>{summary[key]}</strong>
                        </div>
                    ))}
                </div>

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get(route('stock-opname.history'), form, { preserveState: true });
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
                                    maxLength={100}
                                    aria-label="Cari hasil SO"
                                    placeholder="Cari produk, kode, nomor SO, petugas, atau alasan…"
                                    value={form.search}
                                    onChange={(e) => set('search', e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>

                            <input
                                type="date"
                                value={form.date_from}
                                onChange={(e) => set('date_from', e.target.value)}
                                className={selectCls}
                            />
                            <span className="text-xs text-gray-400">s/d</span>
                            <input
                                type="date"
                                min={form.date_from || undefined}
                                value={form.date_to}
                                onChange={(e) => set('date_to', e.target.value)}
                                className={selectCls}
                            />

                            <button
                                type="button"
                                onClick={() => setShowAdvanced(!showAdvanced)}
                                className={`inline-flex items-center gap-1.5 rounded-lg border px-3.5 py-2 text-sm font-medium transition ${
                                    showAdvanced
                                        ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                }`}
                            >
                                <Icon name="filter_list" className="text-[18px]" />
                                <span>Filter Lanjutan</span>
                            </button>

                            <button
                                type="submit"
                                className="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600"
                            >
                                Cari
                            </button>

                            {hasFilter && (
                                <Link
                                    href={route('stock-opname.history')}
                                    className="rounded-lg bg-red-50 px-3.5 py-2 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400"
                                >
                                    Reset
                                </Link>
                            )}
                        </div>

                        {showAdvanced && (
                            <div className="grid grid-cols-1 gap-2.5 border-t border-gray-200/70 pt-3 sm:grid-cols-4 dark:border-gray-800">
                                <select
                                    value={form.location_key}
                                    onChange={(e) => set('location_key', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Lokasi</option>
                                    {locations.map((l) => (
                                        <option key={l.key} value={l.key}>
                                            {l.name}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={form.counted_by}
                                    onChange={(e) => set('counted_by', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Petugas</option>
                                    {actors.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.name}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={form.difference}
                                    onChange={(e) => set('difference', e.target.value)}
                                    className={selectCls}
                                >
                                    <option value="">Semua Hasil</option>
                                    <option value="decrease">Stok Berkurang</option>
                                    <option value="increase">Stok Bertambah</option>
                                    <option value="same">Jumlah Sesuai</option>
                                </select>

                                <input
                                    type="number"
                                    min="1"
                                    placeholder="ID Sesi (Contoh: 1)"
                                    value={form.session_id}
                                    onChange={(e) => set('session_id', e.target.value)}
                                    className={selectCls}
                                />
                            </div>
                        )}
                    </form>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">No. SO & Waktu</th>
                                    <th className="px-5 py-3.5">Produk & Lokasi</th>
                                    <th className="px-5 py-3.5 text-right">Sebelum SO</th>
                                    <th className="px-5 py-3.5 text-right">Sesudah SO</th>
                                    <th className="px-5 py-3.5 text-right">Selisih</th>
                                    <th className="px-5 py-3.5">Petugas & Catatan</th>
                                    <th className="px-5 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {counts.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={7}
                                        icon="balance"
                                        message="Belum ada hasil stock opname yang sesuai pencarian."
                                    />
                                ) : (
                                    counts.data.map((c) => {
                                        const diff = Number(c.difference || 0);
                                        return (
                                            <tr
                                                key={c.id}
                                                className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                            >
                                                <td className="whitespace-nowrap px-5 py-4">
                                                    <Link
                                                        href={route('stock-opname.result', c.id)}
                                                        className="font-mono font-bold text-brand-600 hover:underline dark:text-brand-400"
                                                    >
                                                        {c.number}
                                                    </Link>
                                                    <p className="text-xs text-gray-400">{c.created_at}</p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    <p className="font-semibold text-gray-800 dark:text-white">
                                                        {c.product_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {c.location_name} • Sesi #{c.session_id}
                                                    </p>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-4 text-right tabular-nums">
                                                    {c.quantity_before} {c.unit_name}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-4 text-right font-bold tabular-nums">
                                                    {c.quantity_after} {c.unit_name}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-4 text-right font-bold tabular-nums">
                                                    <span
                                                        className={
                                                            diff < 0
                                                                ? 'text-rose-600 dark:text-rose-400'
                                                                : diff > 0
                                                                  ? 'text-emerald-600 dark:text-emerald-400'
                                                                  : 'text-gray-500'
                                                        }
                                                    >
                                                        {diff > 0 ? '+' : ''}
                                                        {c.difference} {c.unit_name}
                                                    </span>
                                                </td>
                                                <td className="max-w-xs px-5 py-4 text-xs text-gray-500">
                                                    <p className="font-semibold text-gray-700 dark:text-gray-300">
                                                        {c.counted_by_name}
                                                    </p>
                                                    <p className="truncate">{c.notes}</p>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-4 text-right">
                                                    <Link
                                                        href={route('stock-opname.result', c.id)}
                                                        className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                    >
                                                        <span>Detail</span>
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {counts.total > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            <p className="text-xs text-gray-500">
                                Menampilkan <strong>{counts.from || 0}–{counts.to || 0}</strong> dari{' '}
                                <strong>{counts.total}</strong> data
                            </p>
                            <Pagination links={counts.links} />
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
