import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

export default function Index({ sessions, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('jihans.production.index'), {
            search, date_from: dateFrom, date_to: dateTo
        }, { preserveState: true });
    };

    const handleClear = () => {
        setSearch('');
        setDateFrom('');
        setDateTo('');
        router.get(route('jihans.production.index'));
    };

    const deletePrediksi = (id) => {
        if (confirm('Yakin ingin menghapus sesi prediksi ini? Stok akan dikembalikan jika sebelumnya ada selisih.')) {
            router.delete(route('jihans.production.prediksi.destroy', id));
        }
    };

    const StatusBadge = ({ type }) => {
        if (type === 'aktual') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-success-50 px-2 py-1 text-xs font-semibold text-success-600 dark:bg-success-900/10 dark:text-success-500">
                    <Icon name="check_circle" className="text-[14px]" />
                    Aktual
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-900/10 dark:text-warning-500">
                <Icon name="schedule" className="text-[14px]" />
                Prediksi
            </span>
        );
    };

    return (
        <JihansLayout pageTitle="Data Produksi">
            <Head title="Data Produksi" />
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Data Produksi</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Kelola sesi prediksi dan hasil aktual produksi harian.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('jihans.production.prediksi.create')}
                            className="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
                        >
                            <Icon name="schedule" className="text-[18px]" />
                            Input Prediksi
                        </Link>
                        <Link
                            href={route('jihans.production.create')}
                            className="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-700 transition-colors"
                        >
                            <Icon name="add" className="text-[18px]" />
                            Input Aktual
                        </Link>
                    </div>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <form onSubmit={handleFilter} className="flex flex-col sm:flex-row gap-4 items-end">
                        <div className="flex-1 w-full">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nomor</label>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="JHS-PRD-..."
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            />
                        </div>
                        <div className="w-full sm:w-auto">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            />
                        </div>
                        <div className="w-full sm:w-auto">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            />
                        </div>
                        <div className="flex gap-2 w-full sm:w-auto">
                            <button type="submit" className="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 transition-colors">
                                <Icon name="search" className="text-[18px]" />
                                Filter
                            </button>
                            <button type="button" onClick={handleClear} className="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                                <tr>
                                    <th className="px-4 py-4 font-medium">Tanggal</th>
                                    <th className="px-4 py-4 font-medium">No. Sesi</th>
                                    <th className="px-4 py-4 font-medium">Status</th>
                                    <th className="px-4 py-4 font-medium">Jumlah Data</th>
                                    <th className="px-4 py-4 font-medium">Dibuat Oleh</th>
                                    <th className="px-4 py-4 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                {sessions.data.map((session) => (
                                    <tr key={session.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td className="px-4 py-4 text-gray-900 dark:text-white whitespace-nowrap">
                                            {session.date}
                                        </td>
                                        <td className="px-4 py-4 text-gray-900 dark:text-white font-medium whitespace-nowrap">
                                            {session.session_number}
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap">
                                            <StatusBadge type={session.type} />
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap">
                                            {session.details_count} Karyawan/Baris
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap">
                                            {session.creator?.name || '-'}
                                        </td>
                                        <td className="px-4 py-4 text-right whitespace-nowrap">
                                            <div className="flex justify-end gap-3">
                                                {session.type === 'prediksi' && (
                                                    <>
                                                        <Link
                                                            href={route('jihans.production.create', { prediction_id: session.id })}
                                                            className="text-brand-600 hover:text-brand-900 dark:text-brand-500 dark:hover:text-brand-400 font-medium text-xs bg-brand-50 px-2 py-1 rounded"
                                                            title="Input Aktual dari Prediksi ini"
                                                        >
                                                            Input Aktual
                                                        </Link>
                                                        <Link
                                                            href={route('jihans.production.prediksi.edit', session.id)}
                                                            className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                                            title="Edit Prediksi"
                                                        >
                                                            <Icon name="edit" className="text-[18px]" />
                                                        </Link>
                                                        <button
                                                            onClick={() => deletePrediksi(session.id)}
                                                            className="text-error-500 hover:text-error-700 dark:text-error-400 dark:hover:text-error-300"
                                                            title="Hapus Prediksi"
                                                        >
                                                            <Icon name="delete" className="text-[18px]" />
                                                        </button>
                                                    </>
                                                )}
                                                <Link
                                                    href={route('jihans.production.show', session.id)}
                                                    className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                                    title="Lihat Detail"
                                                >
                                                    <Icon name="visibility" className="text-[18px]" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {sessions.data.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-8 text-center text-gray-500">
                                            Belum ada data produksi.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {sessions.last_page > 1 && (
                        <div className="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900/50">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <span className="text-sm text-gray-700 dark:text-gray-400">
                                    Menampilkan {sessions.from} - {sessions.to} dari {sessions.total} data
                                </span>
                                <div className="flex gap-1">
                                    {sessions.links.map((link, i) => (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            className={`px-3 py-1 text-sm border rounded-md ${
                                                link.active 
                                                ? 'bg-brand-600 text-white border-brand-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'
                                            } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </JihansLayout>
    );
}
