import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import EmptyState from '@/Components/EmptyState';
import { Navigation, input, Errors, money, Paging, Search } from './Common';
import { formatDateTime as time } from '@/lib/format';

const route = window.route;

export default function Shifts(props) {
    const cashier = usePage().props.auth.user.roles.includes('kasir_izzi');
    const [openModal, setOpenModal] = useState(false);
    const [closeModal, setCloseModal] = useState(false);

    const open = useForm({ starting_cash: '' });
    const close = useForm({ actual_cash: '', note: '' });

    const expected = Number(props.active?.starting_cash || 0) + Number(props.cash_sales || 0);
    const hasActual = close.data.actual_cash !== '';
    const liveDiff = hasActual ? Number(close.data.actual_cash) - expected : 0;

    return (
        <IzziLayout pageTitle="Laporan Laci Kasir Izzi">
            <Head title="Laci Kasir Izzi" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800 dark:text-white/90">Laci Kasir (Shift Kerja)</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Kelola buka-tutup shift dan rekapitulasi uang tunai laci kasir
                        </p>
                    </div>
                    <Navigation {...props} />
                </div>

                {/* Shift Controller Banner (Only for Kasir) */}
                {cashier && (
                    <div className="rounded-2xl border border-brand-200/80 bg-brand-50/50 p-6 dark:border-brand-950/40 dark:bg-brand-950/10">
                        {props.active ? (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-500" />
                                        <h3 className="font-bold text-gray-800 dark:text-brand-100">
                                            Laci Kasir Sedang Terbuka (Shift #{props.active.id})
                                        </h3>
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Dibuka sejak:{' '}
                                        <span className="font-semibold text-gray-800 dark:text-white">
                                            {time(props.active.opened_at)}
                                        </span>
                                    </p>
                                    <div className="mt-2 flex flex-wrap gap-6 pt-1 text-sm">
                                        <div>
                                            <span className="text-gray-500">Modal Awal: </span>
                                            <strong className="text-gray-900 dark:text-white">
                                                {money(props.active.starting_cash)}
                                            </strong>
                                        </div>
                                        <div>
                                            <span className="text-gray-500">Penjualan Tunai Bersih: </span>
                                            <strong className="text-emerald-600 dark:text-emerald-400">
                                                + {money(props.cash_sales)}
                                            </strong>
                                        </div>
                                        <div>
                                            <span className="text-gray-500">Perkiraan Uang di Laci: </span>
                                            <strong className="text-brand-600 dark:text-brand-400">
                                                {money(expected)}
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-2.5">
                                    <Link
                                        href={route('izzi.pos')}
                                        className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                    >
                                        <Icon name="point_of_sale" className="text-[18px] text-brand-500" />
                                        <span>Ke Kasir POS</span>
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            close.setData('actual_cash', String(expected));
                                            setCloseModal(true);
                                        }}
                                        className="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700"
                                    >
                                        <Icon name="lock" className="text-[18px]" />
                                        <span>Tutup Laci (Akhiri Shift)</span>
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                                <div className="flex gap-4">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-md shadow-rose-500/20">
                                        <Icon name="lock" className="text-[24px]" />
                                    </div>
                                    <div className="space-y-1">
                                        <h3 className="text-lg font-bold text-rose-800 dark:text-rose-200">
                                            Akses POS Terkunci — Laci Kasir Belum Dibuka
                                        </h3>
                                        <p className="max-w-2xl text-sm text-rose-700/90 dark:text-rose-300">
                                            Anda wajib memulai shift kerja dengan menginput uang modal awal terlebih dahulu sebelum mencatat transaksi di Kasir POS.
                                        </p>
                                    </div>
                                </div>
                                <div className="shrink-0">
                                    <button
                                        type="button"
                                        onClick={() => setOpenModal(true)}
                                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-600 md:w-auto"
                                    >
                                        <Icon name="vpn_key" className="text-[18px]" />
                                        <span>Buka Laci & Mulai Shift Kerja</span>
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            path="izzi.shifts"
                            filters={props.filters}
                            branch={props.branch}
                            placeholder="Cari nama kasir atau catatan laci…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-5 py-3.5">Shift & Kasir</th>
                                    <th className="px-5 py-3.5">Buka Shift</th>
                                    <th className="px-5 py-3.5">Tutup Shift</th>
                                    <th className="px-5 py-3.5 text-center">Status</th>
                                    <th className="px-5 py-3.5 text-right">Modal Awal</th>
                                    <th className="px-5 py-3.5 text-right">Perkiraan Sistem</th>
                                    <th className="px-5 py-3.5 text-right">Uang Fisik</th>
                                    <th className="px-5 py-3.5 text-right">Selisih</th>
                                    <th className="px-5 py-3.5">Catatan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {!props.shifts.data.length ? (
                                    <EmptyState
                                        colSpan={9}
                                        icon="account_balance_wallet"
                                        message="Belum ada riwayat shift laci kasir."
                                    />
                                ) : (
                                    props.shifts.data.map((s) => {
                                        const isClosed = s.status === 'closed';
                                        const selisih = Number(s.discrepancy || 0);
                                        const hasDiscrepancy = isClosed && selisih !== 0;

                                        return (
                                            <tr
                                                key={s.id}
                                                className={`hover:bg-gray-50/60 dark:hover:bg-white/[0.01] ${
                                                    hasDiscrepancy ? 'bg-rose-50/40 dark:bg-rose-500/5' : ''
                                                }`}
                                            >
                                                <td className="whitespace-nowrap px-5 py-3.5">
                                                    <p className="font-semibold text-gray-800 dark:text-white">
                                                        {s.user?.name || `Kasir #${s.user_id}`}
                                                    </p>
                                                    <p className="font-mono text-xs text-gray-400">Shift #{s.id}</p>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">
                                                    {time(s.opened_at)}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">
                                                    {isClosed ? time(s.closed_at) : '—'}
                                                </td>
                                                <td className="px-5 py-3.5 text-center">
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                            isClosed
                                                                ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                                                : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                        }`}
                                                    >
                                                        {isClosed ? 'Selesai' : 'Aktif'}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right font-medium tabular-nums">
                                                    {money(s.starting_cash)}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right font-medium tabular-nums">
                                                    {isClosed ? money(s.expected_cash) : '—'}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right font-bold tabular-nums">
                                                    {isClosed ? money(s.actual_cash) : '—'}
                                                </td>
                                                <td className="whitespace-nowrap px-5 py-3.5 text-right font-bold tabular-nums">
                                                    {isClosed ? (
                                                        <span
                                                            className={
                                                                selisih < 0
                                                                    ? 'text-rose-600 dark:text-rose-400'
                                                                    : selisih > 0
                                                                      ? 'text-blue-600 dark:text-blue-400'
                                                                      : 'text-emerald-600 dark:text-emerald-400'
                                                            }
                                                        >
                                                            {selisih > 0 ? '+' : ''}
                                                            {money(selisih)}
                                                        </span>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="max-w-xs truncate px-5 py-3.5 text-xs text-gray-500">
                                                    {s.note || '—'}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={props.shifts} />
                </div>
            </div>

            {/* Modal Buka Laci */}
            <Modal show={openModal} onClose={() => setOpenModal(false)} maxWidth="md">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        open.post(route('izzi.shifts.open'), {
                            onSuccess: () => {
                                setOpenModal(false);
                                open.reset();
                            },
                        });
                    }}
                    className="p-6"
                >
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">Buka Laci Kasir Baru</h3>
                    <p className="mt-1 text-xs text-gray-500">
                        Masukkan jumlah uang tunai modal awal yang ada di dalam laci sebelum mulai berjualan.
                    </p>

                    <div className="mt-4 space-y-4">
                        <Errors errors={open.errors} />
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Modal Uang Tunai Awal (Rp)
                            <input
                                required
                                type="number"
                                min="0"
                                max="1000000000"
                                step="1"
                                placeholder="Contoh: 100000 (isi 0 jika tanpa modal)"
                                className={`${input} mt-1.5 font-bold`}
                                value={open.data.starting_cash}
                                onChange={(e) => open.setData('starting_cash', e.target.value)}
                            />
                        </label>
                    </div>

                    <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={() => setOpenModal(false)}
                            className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={open.processing}
                            className="rounded-xl bg-brand-500 px-5 py-2 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-40"
                        >
                            {open.processing ? 'Membuka…' : 'Buka Laci Sekarang'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Tutup Laci */}
            <Modal show={closeModal} onClose={() => setCloseModal(false)} maxWidth="md">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        close.post(route('izzi.shifts.close'), {
                            onSuccess: () => {
                                setCloseModal(false);
                                close.reset();
                            },
                        });
                    }}
                    className="p-6"
                >
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">Tutup Laci Kasir (Akhiri Shift)</h3>
                    <p className="mt-1 text-xs text-gray-500">
                        Hitung fisik uang tunai di laci dan cocokkan dengan perkiraan sistem.
                    </p>

                    <div className="my-4 grid grid-cols-2 gap-3 rounded-xl bg-gray-50 p-3.5 text-xs dark:bg-gray-800">
                        <div>
                            <span className="text-gray-500">Modal + Tunai Bersih</span>
                            <strong className="mt-0.5 block text-base font-bold text-gray-900 dark:text-white">
                                {money(expected)}
                            </strong>
                        </div>
                        <div>
                            <span className="text-gray-500">Selisih Hitung</span>
                            <strong
                                className={`mt-0.5 block text-base font-bold ${
                                    liveDiff < 0
                                        ? 'text-rose-600'
                                        : liveDiff > 0
                                          ? 'text-blue-600'
                                          : 'text-emerald-600'
                                }`}
                            >
                                {hasActual ? `${liveDiff > 0 ? '+' : ''}${money(liveDiff)}` : '—'}
                            </strong>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <Errors errors={close.errors} />
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Uang Tunai Fisik di Laci (Rp)
                            <input
                                required
                                type="number"
                                min="0"
                                max="1000000000000"
                                step="1"
                                className={`${input} mt-1.5 font-bold`}
                                value={close.data.actual_cash}
                                onChange={(e) => close.setData('actual_cash', e.target.value)}
                            />
                        </label>

                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Catatan Penutupan (Opsional)
                            <textarea
                                maxLength="1000"
                                rows={2}
                                placeholder="Catatan jika ada selisih uang tunai…"
                                className={`${input} mt-1.5`}
                                value={close.data.note}
                                onChange={(e) => close.setData('note', e.target.value)}
                            />
                        </label>
                    </div>

                    <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={() => setCloseModal(false)}
                            className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={close.processing}
                            className="rounded-xl bg-rose-600 px-5 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40"
                        >
                            {close.processing ? 'Menutup…' : 'Tutup Laci & Simpan'}
                        </button>
                    </div>
                </form>
            </Modal>
        </IzziLayout>
    );
}
