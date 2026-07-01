import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

export default function Recap({ productTotals, filters, noFilter }) {
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [periode, setPeriode] = useState(filters.periode || '');

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('jihans.production.recap'), {
            date_from: dateFrom,
            date_to: dateTo,
            periode
        }, { preserveState: true });
    };

    const handleClear = () => {
        setDateFrom('');
        setDateTo('');
        setPeriode('');
        router.get(route('jihans.production.recap'));
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (periode) params.append('periode', periode);
        window.location.href = `${route('jihans.production.recap.export')}?${params.toString()}`;
    };

    return (
        <JihansLayout pageTitle="Rekap Produksi">
            <Head title="Rekap Produksi" />
            
            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Rekapitulasi Produksi</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Laporan total produksi aktual per produk.
                        </p>
                    </div>
                    <div>
                        <button
                            onClick={handleExport}
                            className="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-success-700 transition-colors"
                        >
                            <Icon name="download" className="text-[18px]" />
                            Export Excel
                        </button>
                    </div>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <form onSubmit={handleFilter} className="flex flex-col sm:flex-row gap-4 items-end">
                        <div className="w-full sm:w-auto">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Periode Cepat</label>
                            <select
                                value={periode}
                                onChange={(e) => {
                                    setPeriode(e.target.value);
                                    setDateFrom('');
                                    setDateTo('');
                                }}
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            >
                                <option value="">Custom Tanggal</option>
                                <option value="hari">Hari Ini</option>
                                <option value="minggu">Minggu Ini</option>
                                <option value="bulan">Bulan Ini</option>
                            </select>
                        </div>
                        
                        <div className="w-full sm:w-auto">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => { setDateFrom(e.target.value); setPeriode(''); }}
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            />
                        </div>
                        <div className="w-full sm:w-auto">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => { setDateTo(e.target.value); setPeriode(''); }}
                                className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white"
                            />
                        </div>

                        <div className="flex gap-2 w-full sm:w-auto">
                            <button type="submit" className="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 transition-colors">
                                <Icon name="filter_list" className="text-[18px]" />
                                Terapkan
                            </button>
                            <button type="button" onClick={handleClear} className="flex-1 sm:flex-none inline-flex justify-center items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
                    {noFilter && (
                        <div className="bg-warning-50 px-4 py-3 border-b border-warning-200 dark:bg-warning-900/10 dark:border-warning-900/30">
                            <p className="text-sm text-warning-800 dark:text-warning-400 flex items-center gap-2">
                                <Icon name="info" className="text-[18px]" />
                                Menampilkan rekapitulasi <strong>seluruh data</strong> produksi (tanpa filter periode).
                            </p>
                        </div>
                    )}
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Nama Produk</th>
                                    <th className="px-6 py-4 font-medium text-right w-48">Total Produksi (Aktual)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                {productTotals.map((item, i) => (
                                    <tr key={i} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td className="px-6 py-4 text-gray-900 dark:text-white font-medium">
                                            {item.product_name}
                                        </td>
                                        <td className="px-6 py-4 text-gray-900 dark:text-white font-bold text-right text-lg">
                                            {Number(item.total_qty).toLocaleString('id-ID')}
                                        </td>
                                    </tr>
                                ))}
                                {productTotals.length === 0 && (
                                    <tr>
                                        <td colSpan="2" className="px-6 py-8 text-center text-gray-500">
                                            Tidak ada data produksi pada periode tersebut.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </JihansLayout>
    );
}
