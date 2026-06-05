import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import { SkeletonTableRows } from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';

export default function TortillaIndex({ sessions, filters }) {
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters?.search || '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('jihans.tortilla.index'), { search, date_from: dateFrom, date_to: dateTo }, {
            preserveState: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false)
        });
    };

    const clearFilter = () => {
        setSearch('');
        setDateFrom('');
        setDateTo('');
        router.get(route('jihans.tortilla.index'), {}, {
            preserveState: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false)
        });
    };

    return (
        <JihansLayout pageTitle="Produksi Tortilla">
            <Head title="Produksi Tortilla" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Produksi Tortilla</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Kelola pencatatan produksi aktual dan prediksi tortilla</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link href={route('jihans.tortilla.prediksi.create')} className="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <Icon name="event_note" className="text-[18px]" /> Prediksi Baru
                        </Link>
                        <Link href={route('jihans.tortilla.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                            <Icon name="add" className="text-[18px]" /> Input Aktual
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cari Nomor</label>
                                <div className="relative">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={e => setSearch(e.target.value)}
                                        placeholder="Sesi produksi..."
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 py-2 pl-9 pr-3 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                    />
                                </div>
                            </div>
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dari Tanggal</label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={e => setDateFrom(e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-855 px-3 py-2 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                            </div>
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={e => setDateTo(e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-855 px-3 py-2 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    type="submit"
                                    className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors"
                                >
                                    Filter
                                </button>
                                {(search || dateFrom || dateTo) && (
                                    <button
                                        type="button"
                                        onClick={clearFilter}
                                        className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors"
                                    >
                                        Reset
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">No. Sesi</th>
                                    <th className="px-6 py-4 font-semibold">Tanggal</th>
                                    <th className="px-6 py-4 font-semibold">Tipe</th>
                                    <th className="px-6 py-4 font-semibold">Total Karyawan</th>
                                    <th className="px-6 py-4 font-semibold">Operator</th>
                                    <th className="px-6 py-4 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? (
                                    <SkeletonTableRows rows={5} columns={6} />
                                ) : sessions.data.length === 0 ? (
                                    <EmptyState colSpan={6} icon="inventory" message="Belum ada data produksi tortilla." />
                                ) : (
                                    sessions.data.map(item => (
                                        <tr key={item.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-mono font-semibold text-gray-800 dark:text-white/90">{item.session_number}</td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{formatDate(item.date)}</td>
                                            <td className="px-6 py-4">
                                                <StatusBadge 
                                                    status={item.type === 'aktual' ? 'completed' : item.overridden_at ? 'draft' : 'pending'} 
                                                    label={item.type === 'prediksi' && item.overridden_at ? 'overridden' : item.type} 
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{item.details_count} orang</td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">{item.creator?.name || 'Sistem'}</td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-3">
                                                    <Link href={route('jihans.tortilla.show', item.id)} className="text-gray-400 transition-colors hover:text-blue-600" title="Detail">
                                                        <Icon name="visibility" className="text-[20px]" />
                                                    </Link>
                                                    {item.type === 'prediksi' && (
                                                        <>
                                                            {!item.overridden_at && (
                                                                <Link href={route('jihans.tortilla.create', { date: item.date ? item.date.substring(0, 10) : undefined })} className="text-gray-400 transition-colors hover:text-green-600" title="Input Aktual (Override)">
                                                                    <Icon name="task_alt" className="text-[20px]" />
                                                                </Link>
                                                            )}
                                                            <a href={route('jihans.tortilla.faktur', item.id)} target="_blank" rel="noopener noreferrer" className="text-gray-400 transition-colors hover:text-orange-600" title="Cetak Faktur">
                                                                <Icon name="print" className="text-[20px]" />
                                                            </a>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {sessions.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={sessions.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
