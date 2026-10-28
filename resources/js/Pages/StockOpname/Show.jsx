import SessionActions, { SessionState } from './SessionActions';
import { useOpnameActive } from '@/Components/StockOpnameAccess';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Layout, { input, Errors } from './Layout';

const route = window.route;

function CountModal({ product, session, onClose }) {
    const form = useForm({
        product_id: product.id,
        expected_quantity: product.quantity,
        quantity: '',
        notes: '',
        confirmed: false,
    });
    const diff =
        form.data.quantity === ''
            ? null
            : Number((Number(form.data.quantity) - Number(product.quantity)).toFixed(session.entity === 'izzi' ? 3 : 0));

    return (
        <Modal show={true} onClose={onClose} maxWidth="md">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('stock-opname.count', session.id), {
                        preserveScroll: true,
                        onSuccess: onClose,
                    });
                }}
                className="p-6"
            >
                <h3 className="text-lg font-bold text-gray-900 dark:text-white">Hitung Fisik: {product.name}</h3>
                <p className="mt-1 text-xs text-gray-500">
                    Kode: {product.code} • Saldo sistem saat ini: <strong>{product.quantity} {product.unit}</strong>
                </p>

                <div className="mt-4 space-y-4">
                    <Errors errors={form.errors} />

                    <label className="block text-sm font-medium">
                        Jumlah Fisik Sebenarnya ({product.unit})
                        <input
                            autoFocus
                            required
                            type="number"
                            min="0"
                            max="999999999"
                            step={session.entity === 'izzi' ? '0.001' : '1'}
                            className={`${input} mt-1.5 font-bold`}
                            value={form.data.quantity}
                            onChange={(e) => form.setData('quantity', e.target.value)}
                        />
                    </label>

                    {diff !== null && (
                        <div
                            className={`rounded-xl p-3 text-xs font-semibold ${
                                diff < 0
                                    ? 'bg-rose-50 text-rose-700'
                                    : diff > 0
                                      ? 'bg-emerald-50 text-emerald-700'
                                      : 'bg-blue-50 text-blue-700'
                            }`}
                        >
                            Selisih: {diff > 0 ? '+' : ''}
                            {diff} {product.unit} → Saldo baru menjadi <strong>{form.data.quantity} {product.unit}</strong>
                        </div>
                    )}

                    <label className="block text-sm font-medium">
                        Catatan / Alasan Selisih
                        <textarea
                            required
                            minLength={5}
                            maxLength={1000}
                            rows={2}
                            placeholder="Jelaskan hasil hitung fisik atau alasan selisih…"
                            className={`${input} mt-1.5`}
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                    </label>

                    <label className="flex items-center gap-2 text-xs font-medium">
                        <input
                            type="checkbox"
                            checked={form.data.confirmed}
                            onChange={(e) => form.setData('confirmed', e.target.checked)}
                        />
                        <span>Saya sudah menghitung fisik barang ini dengan benar.</span>
                    </label>
                </div>

                <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={form.processing || !form.data.confirmed || diff === null}
                        className="rounded-xl bg-brand-500 px-5 py-2 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-40"
                    >
                        {form.processing ? 'Menyimpan…' : 'Simpan Hasil Hitung'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

export default function Show({
    session,
    products,
    counts,
    isOwner,
    canCount: permitted,
    filters,
    countTotal = counts.length,
    productCounts = counts,
}) {
    const canCount = useOpnameActive(permitted, session);
    const [selected, select] = useState(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const counted = new Map(productCounts.map((c) => [Number(c.product_id), c]));

    return (
        <Layout pageTitle={`Opname #${session.id}`}>
            <Head title={`Opname #${session.id}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('stock-opname.index')}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Daftar Sesi SO</span>
                        </Link>
                        <div className="flex items-center gap-3">
                            <h2 className="text-xl font-bold text-gray-800 dark:text-white">
                                Opname #{session.id} — {session.location_name}
                            </h2>
                            <SessionState session={session} />
                        </div>
                        <p className="mt-0.5 text-xs text-gray-500">
                            Dibuka {session.opened_at} oleh {session.opened_by_name} • Berakhir {session.expires_at} •
                            Instruksi: <strong>{session.notes}</strong>
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Link
                            href={route('stock-opname.history', { session_id: session.id })}
                            className="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            Riwayat Hasil Sesi Ini ({countTotal}) →
                        </Link>
                        {isOwner && <SessionActions key={session.expires_at_iso + session.status} session={session} />}
                    </div>
                </div>

                {/* Products Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                select(null);
                                router.get(route('stock-opname.show', session.id), { search }, { preserveState: true });
                            }}
                            className="flex flex-wrap items-center gap-2.5"
                        >
                            <div className="relative min-w-[240px] flex-1">
                                <Icon
                                    name="search"
                                    className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400"
                                />
                                <input
                                    type="search"
                                    aria-label="Cari produk"
                                    placeholder="Cari nama produk atau kode…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600"
                            >
                                Cari
                            </button>
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-3.5">Produk</th>
                                    <th className="px-6 py-3.5 text-right">Saldo Sistem</th>
                                    <th className="px-6 py-3.5 text-center">Status Hitung</th>
                                    <th className="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {products.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={4}
                                        icon="inventory_2"
                                        message="Tidak ada produk yang ditemukan."
                                    />
                                ) : (
                                    products.data.map((p) => {
                                        const c = counted.get(Number(p.id));
                                        return (
                                            <tr
                                                key={p.id}
                                                className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                            >
                                                <td className="px-6 py-4">
                                                    <p className="font-semibold text-gray-800 dark:text-white">
                                                        {p.name}
                                                    </p>
                                                    <p className="font-mono text-xs text-gray-400">{p.code}</p>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right font-bold tabular-nums">
                                                    {p.quantity} {p.unit}
                                                </td>
                                                <td className="px-6 py-4 text-center">
                                                    {c ? (
                                                        <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                                            Sudah Dihitung ({c.quantity_before} → {c.quantity_after}{' '}
                                                            {c.unit_name})
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800">
                                                            Belum Dihitung
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    {c ? (
                                                        <Link
                                                            href={route('stock-opname.result', c.id)}
                                                            className="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                                                        >
                                                            Lihat Detail SO →
                                                        </Link>
                                                    ) : (
                                                        canCount && (
                                                            <button
                                                                type="button"
                                                                onClick={() => select(p)}
                                                                className="rounded-lg bg-brand-500 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-600"
                                                            >
                                                                Hitung Fisik
                                                            </button>
                                                        )
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {products.total > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            <p className="text-xs text-gray-500">
                                Menampilkan <strong>{products.from || 0}–{products.to || 0}</strong> dari{' '}
                                <strong>{products.total}</strong> produk
                            </p>
                            <Pagination links={products.links} />
                        </div>
                    )}
                </div>
            </div>

            {selected && canCount && (
                <CountModal
                    key={`${session.id}-${selected.id}`}
                    product={selected}
                    session={session}
                    onClose={() => select(null)}
                />
            )}
        </Layout>
    );
}
