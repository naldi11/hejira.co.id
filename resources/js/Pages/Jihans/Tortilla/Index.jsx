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
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Produksi Tortilla</h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link href={route('jihans.tortilla.prediksi.create')} className="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            <Icon name="event_note" className="text-[20px]" /> Prediksi Baru
                        </Link>
                        <Link href={route('jihans.tortilla.create')} className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-orange-700">
                            <Icon name="add" className="text-[20px]" /> Input Aktual
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Cari Nomor</label>
                                <div className="relative">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                                    <input type="text" value={search} onChange={e => setSearch(e.target.value)} placeholder="Sesi produksi..." className="w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm" />
                                </div>
                            </div>
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Dari Tanggal</label>
                                <input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" />
                            </div>
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Sampai Tanggal</label>
                                <input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" />
                            </div>
                            <div className="flex items-center gap-2">
                                <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button>
                                {(search || dateFrom || dateTo) && (
                                    <button type="button" onClick={clearFilter} className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</button>
                                )}
                            </div>
                        </form>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">No. Sesi</th>
                                    <th className="px-6 py-4 font-medium">Tanggal</th>
                                    <th className="px-6 py-4 font-medium">Tipe</th>
                                    <th className="px-6 py-4 font-medium">Total Karyawan</th>
                                    <th className="px-6 py-4 font-medium">Operator</th>
                                    <th className="px-6 py-4 text-center font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? (
                                    <SkeletonTableRows rows={5} columns={6} />
                                ) : sessions.data.length === 0 ? (
                                    <EmptyState colSpan={6} icon="inventory" message="Belum ada data produksi tortilla." />
                                ) : (
                                    sessions.data.map(item => (
                                        <tr key={item.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 font-bold text-gray-800">{item.session_number}</td>
                                            <td className="px-6 py-4 text-gray-600">{formatDate(item.date)}</td>
                                            <td className="px-6 py-4">
                                                <StatusBadge status={item.type === 'aktual' ? 'completed' : 'pending'} label={item.type} />
                                            </td>
                                            <td className="px-6 py-4 text-gray-600">{item.details_count} orang</td>
                                            <td className="px-6 py-4 text-gray-600">{item.creator?.name || 'Sistem'}</td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-3">
                                                    <Link href={route('jihans.tortilla.show', item.id)} className="text-gray-400 transition-colors hover:text-blue-600" title="Detail">
                                                        <Icon name="visibility" className="text-[20px]" />
                                                    </Link>
                                                    {item.type === 'prediksi' && (
                                                        <a href={route('jihans.tortilla.faktur', item.id)} target="_blank" rel="noopener noreferrer" className="text-gray-400 transition-colors hover:text-orange-600" title="Cetak Faktur">
                                                            <Icon name="print" className="text-[20px]" />
                                                        </a>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {sessions.links && <div className="border-t border-gray-100 p-4"><Pagination links={sessions.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
