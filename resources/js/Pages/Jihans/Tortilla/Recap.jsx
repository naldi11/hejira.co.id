import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { formatDate } from '@/lib/format';

export default function TortillaRecap({ recap, filters, noFilter, dateFrom, dateTo }) {
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
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Rekap Produksi Tortilla</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Laporan total produksi per karyawan untuk perhitungan gaji.
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode Cepat</label>
                                <select 
                                    value={filterType} 
                                    onChange={e => {
                                        setFilterType(e.target.value);
                                        if(e.target.value !== 'custom') {
                                            setStartDate('');
                                            setEndDate('');
                                        }
                                    }} 
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 px-3 py-2 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                >
                                    <option value="custom" className="dark:bg-gray-800">Pilih Tanggal Manual</option>
                                    <option value="hari" className="dark:bg-gray-800">Hari Ini</option>
                                    <option value="minggu" className="dark:bg-gray-800">Minggu Ini</option>
                                    <option value="bulan" className="dark:bg-gray-800">Bulan Ini</option>
                                </select>
                            </div>
                            
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dari Tanggal</label>
                                <input 
                                    type="date" 
                                    value={startDate} 
                                    onChange={e => {
                                        setStartDate(e.target.value);
                                        setFilterType('custom');
                                    }} 
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-855 px-3 py-2 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 disabled:opacity-50" 
                                    disabled={filterType !== 'custom'}
                                />
                            </div>
                            
                            <div className="flex-1">
                                <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sampai Tanggal</label>
                                <input 
                                    type="date" 
                                    value={endDate} 
                                    onChange={e => {
                                        setEndDate(e.target.value);
                                        setFilterType('custom');
                                    }} 
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 disabled:opacity-50" 
                                    disabled={filterType !== 'custom'}
                                />
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                                >
                                    <Icon name={loading ? 'sync' : 'filter_alt'} className={loading ? 'animate-spin text-[18px]' : 'text-[18px]'} /> Filter
                                </button>
                                
                                {!noFilter && (
                                    <button
                                        type="button"
                                        onClick={clearFilter}
                                        className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors"
                                    >
                                        Reset
                                    </button>
                                )}
                                
                                {!noFilter && recap.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={handleExport}
                                        className="rounded-lg bg-emerald-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 transition-colors flex items-center gap-2"
                                    >
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
                            <div className="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400">
                                <Icon name="filter_alt" className="text-[32px]" />
                            </div>
                            <h3 className="mb-1 text-base font-bold text-gray-800 dark:text-white/90">Silakan Filter Periode</h3>
                            <p className="max-w-sm text-sm text-gray-500 dark:text-gray-400">Pilih periode tanggal di atas untuk menampilkan rekap hasil produksi per karyawan.</p>
                        </div>
                    ) : (
                        <>
                            <div className="bg-orange-500/10 border-b border-orange-500/20 px-5 py-3 text-sm text-orange-800 dark:text-orange-400 flex items-center gap-2 font-medium">
                                <Icon name="info" className="text-[18px]" />
                                Menampilkan rekap dari {dateFrom ? formatDate(dateFrom) : '-'} s/d {dateTo ? formatDate(dateTo) : '-'}
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-4 font-semibold">Nama Karyawan</th>
                                            <th className="px-6 py-4 font-semibold text-center w-32">Kehadiran</th>
                                            <th className="px-6 py-4 font-semibold text-center">Tortilla Besar</th>
                                            <th className="px-6 py-4 font-semibold text-center">Tortilla Sedang</th>
                                            <th className="px-6 py-4 font-semibold text-center">Tortilla Kecil</th>
                                            <th className="px-6 py-4 font-semibold text-center">Tortilla Catering</th>
                                            <th className="px-6 py-4 font-semibold text-center">Kribab</th>
                                            <th className="px-6 py-4 font-semibold text-center">HTM BSR</th>
                                            <th className="px-6 py-4 font-semibold text-center">HTM SDG</th>
                                            <th className="px-6 py-4 font-semibold text-center">HTM MNI</th>
                                            <th className="px-6 py-4 font-semibold text-center">ALB BSR</th>
                                            <th className="px-6 py-4 font-semibold text-center">ALB SDG</th>
                                            <th className="px-6 py-4 font-semibold text-center">ALB MNI</th>
                                            <th className="px-6 py-4 font-semibold text-center">REG BSR</th>
                                            <th className="px-6 py-4 font-semibold text-center">REG SDG</th>
                                            <th className="px-6 py-4 font-semibold text-center">REG MNI</th>
                                            <th className="px-6 py-4 font-semibold text-center">LEN BSR</th>
                                            <th className="px-6 py-4 font-semibold text-center">LEN SDG</th>
                                            <th className="px-6 py-4 font-semibold text-center">LEN MNI</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {recap.map((item) => (
                                            <tr key={item.karyawan_id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">{item.karyawan?.name || 'Karyawan Dihapus'}</td>
                                                <td className="px-6 py-4 text-center">
                                                    <span className="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-400 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-400/20">
                                                        {item.hadir_count} Hari
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_tb || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_ts || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_tk || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_tc || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_kribab || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_hitam_besar || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_hitam_sedang || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_hitam_mini || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_albaik_besar || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_albaik_sedang || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_albaik_mini || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_regular_besar || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_regular_sedang || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_regular_mini || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_lentur_besar || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_lentur_sedang || 0}</td>
                                                <td className="px-6 py-4 text-center text-gray-600 dark:text-gray-300 font-semibold">{item.total_lentur_mini || 0}</td>
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
