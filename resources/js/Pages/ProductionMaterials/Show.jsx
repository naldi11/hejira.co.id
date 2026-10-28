import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import Layout, { input, Errors } from '../StockOpname/Layout';

const route = window.route;

export default function Show({ transaction: t, items, events }) {
    const [cancelModal, setCancelModal] = useState(false);
    const form = useForm({ reason: '', confirmed: false });

    return (
        <Layout pageTitle={`Detail Pemakaian Stok ${t.number}`}>
            <Head title={t.number} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('production-materials.index')}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Riwayat Pemakaian</span>
                        </Link>
                        <div className="flex items-center gap-3">
                            <h2 className="font-mono text-2xl font-bold text-gray-800 dark:text-white">{t.number}</h2>
                            <span
                                className={`rounded-full px-3 py-0.5 text-xs font-semibold ${
                                    t.status === 'cancelled'
                                        ? 'bg-rose-100 text-rose-700'
                                        : 'bg-emerald-100 text-emerald-700'
                                }`}
                            >
                                {t.status_label}
                            </span>
                        </div>
                        <p className="mt-0.5 text-xs text-gray-500">
                            {t.branch_name} • Kejadian: {t.occurred_label} • Dicatat oleh {t.created_by_name}
                        </p>
                    </div>

                    {t.status === 'completed' && (
                        <button
                            type="button"
                            onClick={() => setCancelModal(true)}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                        >
                            <Icon name="cancel" className="text-[18px]" />
                            <span>Batalkan Transaksi</span>
                        </button>
                    )}
                </div>

                {/* Info Card */}
                <div className="grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div>
                        <span className="text-xs text-gray-500">Cabang</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">{t.branch_name}</strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Digunakan Oleh</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">
                            {t.receiver_name}
                        </strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Keperluan Produksi</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">{t.reason}</strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Nomor Bukti</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">
                            {t.evidence_reference || '—'}
                        </strong>
                    </div>
                </div>

                {/* Items Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">
                            Rincian Barang yang Digunakan
                        </h3>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-3.5">Barang</th>
                                    <th className="px-6 py-3.5 text-right">Jumlah Digunakan</th>
                                    <th className="px-6 py-3.5 text-right">Saldo (Sebelum → Sesudah)</th>
                                    <th className="px-6 py-3.5">Catatan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {items.map((i) => (
                                    <tr key={i.id}>
                                        <td className="px-6 py-4">
                                            <p className="font-semibold text-gray-800 dark:text-white">
                                                {i.product_name}
                                            </p>
                                            <p className="font-mono text-xs text-gray-400">{i.product_code}</p>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right font-bold text-brand-600">
                                            {i.quantity} {i.unit_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-xs text-gray-500">
                                            {i.source_before} → <strong>{i.source_after} {i.unit_name}</strong>
                                        </td>
                                        <td className="px-6 py-4 text-xs text-gray-500">{i.notes || '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Pembatalan */}
            <Modal show={cancelModal} onClose={() => setCancelModal(false)} maxWidth="md">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('production-materials.cancel', t.id), {
                            onSuccess: () => setCancelModal(false),
                        });
                    }}
                    className="p-6"
                >
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                        Batalkan Pemakaian Stok {t.number}
                    </h3>
                    <p className="mt-1 text-xs text-gray-500">
                        Seluruh barang akan dikembalikan ke stok {t.branch_name}.
                    </p>

                    <div className="mt-4 space-y-4">
                        <Errors errors={form.errors} />
                        <label className="block text-sm font-medium">
                            Alasan Pembatalan
                            <textarea
                                required
                                minLength={5}
                                maxLength={1000}
                                rows={2}
                                className={`${input} mt-1.5`}
                                value={form.data.reason}
                                onChange={(e) => form.setData('reason', e.target.value)}
                            />
                        </label>
                        <label className="flex items-center gap-2 text-xs font-medium">
                            <input
                                type="checkbox"
                                checked={form.data.confirmed}
                                onChange={(e) => form.setData('confirmed', e.target.checked)}
                            />
                            <span>Saya sudah memeriksa stok fisik dan yakin membatalkan transaksi ini.</span>
                        </label>
                    </div>

                    <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={() => setCancelModal(false)}
                            className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing || !form.data.confirmed}
                            className="rounded-xl bg-rose-600 px-5 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40"
                        >
                            Konfirmasi Pembatalan
                        </button>
                    </div>
                </form>
            </Modal>
        </Layout>
    );
}
