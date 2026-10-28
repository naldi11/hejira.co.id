import SessionActions, { SessionState } from './SessionActions';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Layout, { input, Errors } from './Layout';
import { uuid } from '../Izzi/Common';

const route = window.route;

export default function Index({ locations, sessions, isOwner }) {
    const [openModal, setOpenModal] = useState(false);
    const form = useForm({
        request_key: uuid(),
        location_key: '',
        expires_at: '',
        notes: '',
        confirmed: false,
    });
    const chosen = locations.find((l) => l.key === form.data.location_key);

    return (
        <Layout pageTitle="Stock Opname">
            <Head title="Sesi Stock Opname" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            {isOwner ? 'Kelola Sesi Stock Opname' : 'Hitung Stok Fisik (Stock Opname)'}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {isOwner
                                ? 'Buka akses hitung fisik untuk cabang/gudang dan pantau hasilnya'
                                : 'Pilih sesi aktif di bawah ini untuk mulai menghitung stok fisik di cabang Anda'}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <Link
                            href={route('stock-opname.history')}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            <Icon name="history" className="text-[18px]" />
                            <span>Riwayat Hasil SO</span>
                        </Link>

                        {isOwner && (
                            <button
                                type="button"
                                onClick={() => setOpenModal(true)}
                                className="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600"
                            >
                                <Icon name="add" className="text-[18px]" />
                                <span>Buka Sesi SO Baru</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Sessions Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">Daftar Sesi Stock Opname</h3>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3.5">Sesi & Lokasi</th>
                                    <th className="px-6 py-3.5">Dibuka Oleh</th>
                                    <th className="px-6 py-3.5">Batas Waktu Akses</th>
                                    <th className="px-6 py-3.5 text-center">Status</th>
                                    <th className="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {sessions.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={5}
                                        icon="balance"
                                        message="Belum ada sesi stock opname untuk lokasi ini."
                                    />
                                ) : (
                                    sessions.data.map((s) => (
                                        <tr
                                            key={s.id}
                                            className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-800 dark:text-white">
                                                    {s.location_name}
                                                </p>
                                                <p className="font-mono text-xs text-gray-400">Sesi #{s.id}</p>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                                <p className="font-semibold text-gray-700 dark:text-gray-300">
                                                    {s.opened_by_name}
                                                </p>
                                                <p>{s.opened_at}</p>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {s.expires_at}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <SessionState session={s} />
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                <div className="inline-flex items-center justify-end gap-2">
                                                    <Link
                                                        href={route('stock-opname.show', s.id)}
                                                        className={`inline-flex items-center gap-1 rounded-lg px-3.5 py-1.5 text-xs font-semibold ${
                                                            s.active && !isOwner
                                                                ? 'bg-brand-500 text-white hover:bg-brand-600'
                                                                : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200'
                                                        }`}
                                                    >
                                                        <span>
                                                            {isOwner
                                                                ? 'Detail Sesi'
                                                                : s.active
                                                                  ? 'Mulai Hitung →'
                                                                  : 'Lihat Hasil'}
                                                        </span>
                                                    </Link>
                                                    {isOwner && (
                                                        <SessionActions
                                                            key={s.expires_at_iso + s.status}
                                                            session={s}
                                                        />
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {sessions.total > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                            <p className="text-xs text-gray-500">
                                Menampilkan <strong>{sessions.from || 0}–{sessions.to || 0}</strong> dari{' '}
                                <strong>{sessions.total}</strong> sesi
                            </p>
                            <Pagination links={sessions.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Buka Sesi SO (Owner) */}
            {isOwner && (
                <Modal show={openModal} onClose={() => setOpenModal(false)} maxWidth="md">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route('owner.stock-opname.open'), {
                                onSuccess: () => {
                                    setOpenModal(false);
                                    form.reset();
                                    form.setData('request_key', uuid());
                                },
                            });
                        }}
                        className="p-6"
                    >
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                            Buka Akses Stock Opname Baru
                        </h3>
                        <p className="mt-1 text-xs text-gray-500">
                            Pilih lokasi cabang/gudang dan batas waktu akses hitung fisik.
                        </p>

                        <div className="mt-4 space-y-4">
                            <Errors errors={form.errors} />

                            <label className="block text-sm font-medium">
                                Lokasi yang Diberi Akses
                                <select
                                    required
                                    className={`${input} mt-1.5`}
                                    value={form.data.location_key}
                                    onChange={(e) => form.setData('location_key', e.target.value)}
                                >
                                    <option value="">— Pilih Lokasi —</option>
                                    {locations.map((l) => (
                                        <option key={l.key} value={l.key}>
                                            {l.name}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            {chosen && (
                                <p className="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                                    {chosen.description}
                                </p>
                            )}

                            <label className="block text-sm font-medium">
                                Akses Berakhir Pada (WIB)
                                <input
                                    required
                                    type="datetime-local"
                                    className={`${input} mt-1.5`}
                                    value={form.data.expires_at}
                                    onChange={(e) => form.setData('expires_at', e.target.value)}
                                />
                            </label>

                            <label className="block text-sm font-medium">
                                Instruksi untuk Petugas
                                <textarea
                                    required
                                    minLength={5}
                                    maxLength={1000}
                                    rows={2}
                                    placeholder="Contoh: Hitung stok fisik setelah toko tutup."
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
                                <span>Saya memberi akses petugas lokasi ini untuk menghitung stok fisik.</span>
                            </label>
                        </div>

                        <div className="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                onClick={() => setOpenModal(false)}
                                className="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing || !form.data.confirmed}
                                className="rounded-xl bg-brand-500 px-5 py-2 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-40"
                            >
                                Buka Sesi Sekarang
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </Layout>
    );
}
