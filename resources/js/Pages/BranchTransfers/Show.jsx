import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import Layout, { input, Errors } from '../StockOpname/Layout';
import { uuid } from '../Izzi/Common';

const route = window.route;

function ReceiveModal({ transfer, items, onClose }) {
    const { auth } = usePage().props;
    const form = useForm({
        request_key: uuid(),
        physical_receiver_name: auth.user.name,
        notes: '',
        items: items.map((i) => ({ id: i.id, quantity: 0, expected_received_quantity: i.received_quantity })),
        confirmed: false,
    });

    return (
        <Modal show={true} onClose={onClose} maxWidth="lg">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('branch-transfers.receive', transfer.id), {
                        preserveScroll: true,
                        onSuccess: onClose,
                    });
                }}
                className="p-6"
            >
                <div className="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                            Konfirmasi Penerimaan — {transfer.to_branch_name}
                        </h3>
                        <p className="text-xs text-gray-500">Isi jumlah barang yang diterima sekarang</p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            form.setData(
                                'items',
                                items.map((i) => ({
                                    id: i.id,
                                    quantity: i.remaining,
                                    expected_received_quantity: i.received_quantity,
                                })),
                            )
                        }
                        className="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-600 hover:bg-brand-100"
                    >
                        Terima Semua Sisa
                    </button>
                </div>

                <div className="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                    <Errors errors={form.errors} />

                    {items.map((item, index) => (
                        <div
                            key={item.id}
                            className="rounded-xl border border-gray-200 bg-gray-50/50 p-3.5 dark:border-gray-700 dark:bg-gray-800/40"
                        >
                            <div className="flex items-center justify-between text-sm">
                                <strong className="text-gray-800 dark:text-white">{item.product_name}</strong>
                                <span className="text-xs text-gray-500">
                                    Sisa belum diterima: <strong>{item.remaining} {item.unit_name}</strong>
                                </span>
                            </div>
                            <input
                                aria-label={`Jumlah diterima ${item.product_name}`}
                                required
                                type="number"
                                min="0"
                                max={item.remaining}
                                step={item.quantity_scale === 1000 ? '0.001' : '1'}
                                className={`${input} mt-2 font-bold`}
                                value={form.data.items[index].quantity}
                                onChange={(e) =>
                                    form.setData(
                                        'items',
                                        form.data.items.map((i, idx) =>
                                            idx === index ? { ...i, quantity: e.target.value } : i,
                                        ),
                                    )
                                }
                            />
                        </div>
                    ))}

                    <label className="block text-sm font-medium">
                        Nama Penerima Fisik
                        <input
                            required
                            minLength={2}
                            maxLength={255}
                            className={`${input} mt-1.5`}
                            value={form.data.physical_receiver_name}
                            onChange={(e) => form.setData('physical_receiver_name', e.target.value)}
                        />
                    </label>

                    <label className="block text-sm font-medium">
                        Catatan Penerimaan
                        <textarea
                            required
                            minLength={5}
                            maxLength={2000}
                            rows={2}
                            placeholder="Contoh: Semua barang diterima lengkap"
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
                        <span>Saya sudah memeriksa barang fisik yang diterima.</span>
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
                        disabled={form.processing || !form.data.confirmed}
                        className="rounded-xl bg-brand-500 px-5 py-2 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-40"
                    >
                        Simpan Penerimaan
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function CancelModal({ transfer, onClose }) {
    const form = useForm({ reason: '', confirmed: false });
    return (
        <Modal show={true} onClose={onClose} maxWidth="md">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('branch-transfers.cancel', transfer.id), {
                        preserveScroll: true,
                        onSuccess: onClose,
                    });
                }}
                className="p-6"
            >
                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                    Batalkan Mutasi {transfer.number}
                </h3>
                <p className="mt-1 text-xs text-gray-500">
                    Seluruh barang akan dikembalikan ke saldo {transfer.from_branch_name}.
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
                        <span>Seluruh barang masih berada di cabang asal atau sudah dikembalikan.</span>
                    </label>
                </div>

                <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                    >
                        Tutup
                    </button>
                    <button
                        type="submit"
                        disabled={form.processing || !form.data.confirmed}
                        className="rounded-xl bg-rose-600 px-5 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40"
                    >
                        Batalkan & Kembalikan Stok
                    </button>
                </div>
            </form>
        </Modal>
    );
}

export default function Show({ transfer: t, items, receipts, canReceive, canCancel, stockChanges = [] }) {
    const unitLabels = { gudang: 'Gudang', jihans: 'Jihan’s', hendhys: 'Hendhys', izzi: 'Izzi Kebab' };
    const [receive, openReceive] = useState(false);
    const [cancel, openCancel] = useState(false);

    return (
        <Layout pageTitle={`Detail Mutasi ${t.number}`}>
            <Head title={t.number} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('branch-transfers.index')}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Daftar Mutasi</span>
                        </Link>
                        <div className="flex items-center gap-3">
                            <h2 className="font-mono text-2xl font-bold text-gray-800 dark:text-white">{t.number}</h2>
                            <span
                                className={`rounded-full px-3 py-0.5 text-xs font-semibold ${
                                    t.status === 'received'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : t.status === 'cancelled'
                                          ? 'bg-rose-100 text-rose-700'
                                          : 'bg-amber-100 text-amber-700'
                                }`}
                            >
                                {t.status_label}
                            </span>
                        </div>
                        <p className="mt-0.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                            {unitLabels[t.from_entity]} ({t.from_branch_name}) → {unitLabels[t.to_entity]} (
                            {t.to_branch_name})
                        </p>
                    </div>

                    <div className="flex gap-2.5">
                        {canReceive && (
                            <button
                                type="button"
                                onClick={() => openReceive(true)}
                                className="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600"
                            >
                                Konfirmasi Penerimaan
                            </button>
                        )}
                        {canCancel && (
                            <button
                                type="button"
                                onClick={() => openCancel(true)}
                                className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                            >
                                Batalkan Mutasi
                            </button>
                        )}
                    </div>
                </div>

                {/* Info Card */}
                <div className="grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div>
                        <span className="text-xs text-gray-500">Tanggal & Waktu</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">
                            {t.date} • {t.sent_at}
                        </strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Pengirim & Penerima</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">
                            {t.physical_sender_name || t.sent_by_name} →{' '}
                            {receipts.length
                                ? [...new Set(receipts.map((r) => r.physical_receiver_name))].join(', ')
                                : 'Belum diterima'}
                        </strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Dicatat Oleh</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">
                            {t.sent_by_name} ({t.recorded_at})
                        </strong>
                    </div>
                    <div>
                        <span className="text-xs text-gray-500">Alasan Mutasi</span>
                        <strong className="mt-0.5 block text-sm text-gray-800 dark:text-white">{t.reason}</strong>
                    </div>
                </div>

                {/* Items Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Rincian Barang Dimutasi</h3>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                                <tr>
                                    <th className="px-6 py-3.5">Barang Asal</th>
                                    <th className="px-6 py-3.5 text-right">Dikirim</th>
                                    <th className="px-6 py-3.5 text-right">Diterima</th>
                                    <th className="px-6 py-3.5">Konversi di Tujuan</th>
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
                                        <td className="whitespace-nowrap px-6 py-4 text-right font-bold">
                                            {i.quantity} {i.unit_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right font-bold text-emerald-600">
                                            {i.received_quantity} {i.unit_name}
                                        </td>
                                        <td className="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                                            <strong>{i.to_product_name}</strong> ({i.received_destination_quantity}{' '}
                                            {i.to_unit_name})
                                        </td>
                                        <td className="px-6 py-4 text-xs text-gray-500">{i.notes || '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Stock Changes Table */}
                {stockChanges.length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h3 className="font-semibold text-gray-800 dark:text-white/90">Jejak Perubahan Stok</h3>
                        </div>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                                    <tr>
                                        <th className="px-6 py-3.5">Lokasi</th>
                                        <th className="px-6 py-3.5">Produk</th>
                                        <th className="px-6 py-3.5 text-right">Perubahan</th>
                                        <th className="px-6 py-3.5 text-right">Saldo (Sebelum → Sesudah)</th>
                                        <th className="px-6 py-3.5">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {stockChanges.map((m) => (
                                        <tr key={m.id}>
                                            <td className="px-6 py-3.5 font-semibold">{m.location}</td>
                                            <td className="px-6 py-3.5">{m.product}</td>
                                            <td
                                                className={`px-6 py-3.5 text-right font-bold ${
                                                    m.delta < 0 ? 'text-rose-600' : 'text-emerald-600'
                                                }`}
                                            >
                                                {m.delta > 0 ? '+' : ''}
                                                {m.delta} {m.unit}
                                            </td>
                                            <td className="px-6 py-3.5 text-right text-xs text-gray-500">
                                                {m.before} → <strong>{m.after} {m.unit}</strong>
                                            </td>
                                            <td className="px-6 py-3.5 text-xs text-gray-500">{m.at}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>

            {receive && canReceive && (
                <ReceiveModal key={receipts.length} transfer={t} items={items} onClose={() => openReceive(false)} />
            )}
            {cancel && canCancel && <CancelModal transfer={t} onClose={() => openCancel(false)} />}
        </Layout>
    );
}
