import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { formatDate } from '@/lib/format';

export default function TortillaRecap({ recap, filters, noFilter, dateFrom, dateTo, periode }) {
    const [loading, setLoading] = useState(false);
    
    // Convert 'hari', 'minggu', 'bulan' back if needed, but easier to use form state
    const [filterType, setFilterType] = useState(filters?.periode || 'custom');
    const [startDate, setStartDate] = useState(filters?.date_from || '');
    const [endDate, setEndDate] = useState(filters?.date_to || '');

    const handleSearch = (e) => {
        e.preventDefault();
        
        let queryParams = {};
        if (filterType !== 'custom') {
            queryParams = { periode: filterType };
        } else if (startDate || endDate) {
            queryParams = { date_from: startDate, date_to: endDate };
        }
        
        router.get(route('jihans.tortilla.recap'), queryParams, {
            preserveState: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false)
        });
    };

    const handleExport = (e) => {
        e.preventDefault();
        let queryParams = new URLSearchParams();
        if (filterType !== 'custom') {
            queryParams.append('periode', filterType);
        } else {
            if (startDate) queryParams.append('date_from', startDate);
            if (endDate) queryParams.append('date_to', endDate);
        }
        
        window.location.href = `${route('jihans.tortilla.recap.export')}?${queryParams.toString()}`;
    };

    const clearFilter = () => {
        setFilterType('custom');
        setStartDate('');
        setEndDate('');
        router.get(route('jihans.tortilla.recap'), {}, {
            preserveState: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false)
        });
    };

    return (
        <JihansLayout pageTitle="Rekap Produksi Tortilla">
            <Head title="Rekap Produksi" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">Rekap Produksi Tortilla</h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Laporan total produksi per karyawan untuk perhitungan gaji.
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Periode Cepat</label>
                                <select 
                                    value={filterType} 
                                    onChange={e => {
                                        setFilterType(e.target.value);
                                        if(e.target.value !== 'custom') {
                                            setStartDate('');
                                            setEndDate('');
                                        }
                                    }} 
                                    className="w-full rounded-lg border-gray-300 py-2 text-sm"
                                >
                                    <option value="custom">Pilih Tanggal Manual</option>
                                    <option value="hari">Hari Ini</option>
                                    <option value="minggu">Minggu Ini</option>
                                    <option value="bulan">Bulan Ini</option>
                                </select>
                            </div>
                            
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Dari Tanggal</label>
                                <input 
                                    type="date" 
                                    value={startDate} 
                                    onChange={e => {
                                        setStartDate(e.target.value);
                                        setFilterType('custom');
                                    }} 
                                    className="w-full rounded-lg border-gray-300 py-2 text-sm disabled:bg-gray-100" 
                                    disabled={filterType !== 'custom'}
                                />
                            </div>
                            
                            <div className="flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Sampai Tanggal</label>
                                <input 
                                    type="date" 
                                    value={endDate} 
                                    onChange={e => {
                                        setEndDate(e.target.value);
                                        setFilterType('custom');
                                    }} 
                                    className="w-full rounded-lg border-gray-300 py-2 text-sm disabled:bg-gray-100" 
                                    disabled={filterType !== 'custom'}
                                />
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <button type="submit" disabled={loading} className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 flex items-center gap-2">
                                    <Icon name={loading ? 'sync' : 'filter_alt'} className={loading ? 'animate-spin text-[18px]' : 'text-[18px]'} /> Filter
                                </button>
                                
                                {!noFilter && (
                                    <button type="button" onClick={clearFilter} className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                        Reset
                                    </button>
                                )}
                                
                                {!noFilter && recap.length > 0 && (
                                    <button type="button" onClick={handleExport} className="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 flex items-center gap-2">
                                        <Icon name="download" className="text-[18px]" /> Excel
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    {!noFilter && recap.length === 0 ? (
                        <EmptyState icon="folder_off" message="Tidak ada data produksi pada periode tersebut." />
                    ) : noFilter ? (
                        <div className="flex flex-col items-center justify-center p-12 text-center text-gray-500">
                            <div className="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <Icon name="filter_alt" className="text-[32px]" />
                            </div>
                            <h3 className="mb-1 text-lg font-bold text-gray-800">Silakan Filter Periode</h3>
                            <p className="max-w-sm text-sm">Pilih periode tanggal di atas untuk menampilkan rekap hasil produksi per karyawan.</p>
                        </div>
                    ) : (
                        <>
                            <div className="bg-orange-50 px-5 py-3 border-b border-orange-100 text-sm text-orange-800 flex items-center gap-2 font-medium">
                                <Icon name="info" className="text-[18px]" />
                                Menampilkan rekap dari {dateFrom ? formatDate(dateFrom) : '-'} s/d {dateTo ? formatDate(dateTo) : '-'}
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                        <tr>
                                            <th className="px-6 py-4 font-medium">Nama Karyawan</th>
                                            <th className="px-6 py-4 font-medium text-center">Kehadiran</th>
                                            <th className="px-6 py-4 font-medium text-center">Tortilla Besar</th>
                                            <th className="px-6 py-4 font-medium text-center">Tortilla Sedang</th>
                                            <th className="px-6 py-4 font-medium text-center">Tortilla Kecil</th>
                                            <th className="px-6 py-4 font-medium text-center">Tortilla Catering</th>
                                            <th className="px-6 py-4 font-medium text-center">Kribab</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {recap.map((item) => (
                                            <tr key={item.karyawan_id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 font-bold text-gray-800">{item.karyawan?.name || 'Karyawan Dihapus'}</td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                        {item.hadir_count} Hari
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-center text-gray-600">{item.total_tb || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600">{item.total_ts || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600">{item.total_tk || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600">{item.total_tc || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600">{item.total_kribab || 0}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </JihansLayout>
    );
}
