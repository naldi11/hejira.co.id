import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import { Navigation, input, Errors, money, status, statusBadgeClass, uuid } from './Common';

const route = window.route;

export default function Sale(props) {
    const [cancelModal, setCancelModal] = useState(false);
    const form = useForm({
        request_key: uuid(),
        branch_id: props.branch.id,
        reason: '',
        confirmed: false,
        items: props.lines
            .filter((l) => l.quantity > l.cancelled_quantity)
            .map((l) => ({ line_id: l.id, unused: 0, wasted: 0 })),
    });

    const set = (id, key, value) =>
        form.setData(
            'items',
            form.data.items.map((i) => (i.line_id === id ? { ...i, [key]: value } : i)),
        );

    const selected = form.data.items.some((i) => Number(i.unused) + Number(i.wasted) > 0);
    const sale = props.sale;

    return (
        <IzziLayout pageTitle={`Detail Transaksi ${sale.number}`}>
            <Head title={sale.number} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <Link
                            href={route('izzi.sales', { branch_id: props.branch.id })}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Riwayat Transaksi</span>
                        </Link>
                        <div className="flex items-center gap-3">
                            <h2 className="font-mono text-2xl font-bold text-gray-800 dark:text-white">{sale.number}</h2>
                            <span
                                className={`rounded-full px-3 py-0.5 text-xs font-semibold ${
                                    statusBadgeClass[sale.status] || statusBadgeClass.completed
                                }`}
                            >
                                {status[sale.status]}
                            </span>
                        </div>
                        <p className="mt-0.5 text-xs text-gray-500">
                            {sale.branch_name} • {sale.time} • Kasir: <strong>{sale.actor_name}</strong> • Pelanggan:{' '}
                            <strong>{sale.customer_name || 'Pelanggan Umum'}</strong>
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {props.canCancel && sale.status !== 'cancelled' && (
                            <button
                                type="button"
                                onClick={() => setCancelModal(true)}
                                className="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-300"
                            >
                                <Icon name="cancel" className="text-[18px]" />
                                <span>Batalkan Pesanan</span>
                            </button>
                        )}
                        <a
                            target="_blank"
                            rel="noopener"
                            href={route('izzi.sales.receipt', { sale: sale.id, branch_id: props.branch.id })}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                        >
                            <Icon name="print" className="text-[18px]" />
                            <span>Cetak Struk</span>
                        </a>
                    </div>
                </div>

                <Navigation {...props} />

                {/* 4 Summary Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <span className="text-xs text-gray-500">Penjualan Awal</span>
                        <strong className="mt-1 block text-xl font-bold text-gray-800 dark:text-white">
                            {money(sale.total)}
                        </strong>
                    </div>
                    <div className="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-theme-xs dark:border-rose-500/20 dark:bg-rose-500/10">
                        <span className="text-xs text-rose-700 dark:text-rose-300">Nilai Pembatalan</span>
                        <strong className="mt-1 block text-xl font-bold text-rose-600 dark:text-rose-400">
                            {money(sale.total - sale.net_total)}
                        </strong>
                    </div>
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-theme-xs dark:border-emerald-500/20 dark:bg-emerald-500/10">
                        <span className="text-xs text-emerald-700 dark:text-emerald-300">Penjualan Bersih</span>
                        <strong className="mt-1 block text-xl font-extrabold text-emerald-700 dark:text-emerald-300">
                            {money(sale.net_total)}
                        </strong>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <span className="text-xs text-gray-500">
                            Metode Bayar ({sale.payment_type === 'cash' ? 'Tunai' : 'Transfer'})
                        </span>
                        <strong className="mt-1 block text-sm font-bold text-gray-800 dark:text-white">
                            Dibayar {money(sale.amount_paid)} • Kembali {money(sale.amount_paid - sale.total)}
                        </strong>
                        {sale.payment_reference && (
                            <span className="mt-0.5 block text-xs text-gray-400">Ref: {sale.payment_reference}</span>
                        )}
                    </div>
                </div>

                {/* Menu & Recipe Consumption Table */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">
                            Rincian Menu & Pemotongan Bahan Resep
                        </h3>
                        <p className="text-xs text-gray-500">
                            Catatan gramasi bahan di bawah ini dikunci sesuai versi resep saat transaksi dibuat.
                        </p>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3.5">Menu Pesanan</th>
                                    <th className="px-6 py-3.5 text-center">Jumlah</th>
                                    <th className="px-6 py-3.5 text-right">Harga Satuan</th>
                                    <th className="px-6 py-3.5">Bahan Resep yang Terpotong</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {props.lines.map((l) => (
                                    <tr key={l.id} className="align-top">
                                        <td className="px-6 py-4">
                                            <p className="font-bold text-gray-800 dark:text-white">{l.menu_name}</p>
                                            <p className="font-mono text-xs text-gray-400">
                                                {l.menu_code} • Resep v{l.recipe_version}
                                            </p>
                                            {l.cancelled_quantity > 0 && (
                                                <span className="mt-1.5 inline-block rounded-lg bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                                                    Batal {l.cancelled_quantity} (Kembali: {l.unused_quantity}, Terbuang:{' '}
                                                    {l.wasted_quantity})
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-center font-bold">{l.quantity} porsi</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right font-semibold tabular-nums">
                                            {money(l.price)}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="grid gap-2 sm:grid-cols-2">
                                                {l.ingredients.map((i) => (
                                                    <div
                                                        key={i.id}
                                                        className="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs dark:border-gray-800 dark:bg-gray-800/60"
                                                    >
                                                        <div className="flex items-center justify-between font-semibold text-gray-800 dark:text-white">
                                                            <span>{i.product_name}</span>
                                                            <span className="text-brand-600 dark:text-brand-400">
                                                                −{i.quantity} {i.unit_name}
                                                            </span>
                                                        </div>
                                                        <p className="mt-0.5 text-[11px] text-gray-500">
                                                            {i.per_menu} {i.unit_name}/porsi
                                                            {(Number(i.restored_quantity) > 0 ||
                                                                Number(i.wasted_quantity) > 0) && (
                                                                <span>
                                                                    {' '}
                                                                    • Kembali: {i.restored_quantity}, Terbuang:{' '}
                                                                    {i.wasted_quantity}
                                                                </span>
                                                            )}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Cancellations History (Only shown if any exist) */}
                {props.cancellations.length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-theme-xs dark:border-rose-500/20 dark:bg-white/[0.03]">
                        <div className="border-b border-rose-100 bg-rose-50/50 px-6 py-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                            <h3 className="font-semibold text-rose-800 dark:text-rose-200">Riwayat Pembatalan</h3>
                        </div>
                        <div className="divide-y divide-gray-100 p-4 dark:divide-gray-800">
                            {props.cancellations.map((c) => (
                                <div key={c.id} className="space-y-1.5 py-3 first:pt-0 last:pb-0">
                                    <div className="flex items-center justify-between text-sm">
                                        <strong className="text-gray-800 dark:text-white">
                                            {c.time} • {c.actor_name}
                                        </strong>
                                        <span className="font-bold text-rose-600 dark:text-rose-400">
                                            Refund: {money(c.refund_amount)}
                                        </span>
                                    </div>
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Alasan: {c.reason}
                                    </p>
                                    <div className="flex flex-wrap gap-2 pt-1 text-xs text-gray-500">
                                        {c.items.map((i) => (
                                            <span
                                                key={i.line_id}
                                                className="rounded-lg bg-gray-100 px-2.5 py-1 dark:bg-gray-800"
                                            >
                                                {i.menu_name}: kembali stok {i.unused}, terbuang {i.wasted}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Pembatalan Pesanan */}
            <Modal show={cancelModal} onClose={() => setCancelModal(false)} maxWidth="lg">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.transform((d) => ({
                            ...d,
                            items: d.items.filter((i) => Number(i.unused) + Number(i.wasted) > 0),
                        }));
                        form.post(route('izzi.sales.cancel', sale.id), {
                            onSuccess: () => {
                                setCancelModal(false);
                                form.reset();
                                form.setData('request_key', uuid());
                            },
                        });
                    }}
                    className="p-6"
                >
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                        Pembatalan Pesanan — {sale.number}
                    </h3>
                    <p className="mt-1 text-xs text-gray-500">
                        Isi jumlah porsi yang dibatalkan pada kolom <strong>Belum Dibuat</strong> (bahan kembali ke stok) atau <strong>Sudah Dibuat</strong> (bahan tidak kembali).
                    </p>

                    <div className="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                        <Errors errors={form.errors} />

                        {form.data.items.map((i) => {
                            const l = props.lines.find((line) => line.id === i.line_id);
                            const max = l.quantity - l.cancelled_quantity;
                            return (
                                <div
                                    key={i.line_id}
                                    className="space-y-3 rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-800/40"
                                >
                                    <div className="flex items-center justify-between">
                                        <strong className="text-sm font-bold text-gray-800 dark:text-white">
                                            {l.menu_name}
                                        </strong>
                                        <span className="text-xs text-gray-500">Maks. batal: {max} porsi</span>
                                    </div>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <label className="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                            Belum Dibuat (Bahan Kembali)
                                            <input
                                                type="number"
                                                min="0"
                                                max={max - Number(i.wasted)}
                                                step="1"
                                                className={`${input} mt-1`}
                                                value={i.unused}
                                                onChange={(e) => set(i.line_id, 'unused', e.target.value)}
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                            Sudah Dibuat / Terbuang
                                            <input
                                                type="number"
                                                min="0"
                                                max={max - Number(i.unused)}
                                                step="1"
                                                className={`${input} mt-1`}
                                                value={i.wasted}
                                                onChange={(e) => set(i.line_id, 'wasted', e.target.value)}
                                            />
                                        </label>
                                    </div>
                                </div>
                            );
                        })}

                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Alasan Pembatalan
                            <textarea
                                required
                                minLength={5}
                                maxLength={1000}
                                rows={2}
                                placeholder="Contoh: Pelanggan membatalkan pesanan…"
                                className={`${input} mt-1.5`}
                                value={form.data.reason}
                                onChange={(e) => form.setData('reason', e.target.value)}
                            />
                        </label>

                        <label className="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                checked={form.data.confirmed}
                                onChange={(e) => form.setData('confirmed', e.target.checked)}
                            />
                            <span>Saya sudah memeriksa kondisi makanan dan jumlah porsi yang dibatalkan.</span>
                        </label>
                    </div>

                    <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={() => setCancelModal(false)}
                            className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300"
                        >
                            Tutup
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing || !selected || !form.data.confirmed}
                            className="rounded-xl bg-rose-600 px-5 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40"
                        >
                            {form.processing ? 'Menyimpan…' : 'Simpan Pembatalan'}
                        </button>
                    </div>
                </form>
            </Modal>
        </IzziLayout>
    );
}
