import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah, formatQty } from '@/lib/format';

export default function OwnerReports({ reportData, filters }) {
    const [form, setForm] = useState({
        unit_bisnis: filters.unit_bisnis ?? 'all',
        periode: filters.periode ?? 'bulanan',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const handleFilter = (e) => {
        e?.preventDefault();
        router.get(route('owner.reports'), {
            unit_bisnis: form.unit_bisnis,
            periode: form.periode,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
        }, { preserveState: true });
    };

    const handleExport = (format) => {
        const queryParams = new URLSearchParams({
            format,
            unit_bisnis: form.unit_bisnis,
            periode: form.periode,
            date_from: form.date_from || '',
            date_to: form.date_to || '',
        }).toString();

        window.open(route('owner.reports.export') + '?' + queryParams, '_blank');
    };

    // Calculate chart details
    const maxVal = Math.max(...reportData.map(d => d.total), 1);
    const chartHeight = 150;
    const chartWidth = 600;
    const barWidth = Math.max(10, (chartWidth - 80) / Math.max(1, reportData.length));

    return (
        <OwnerLayout pageTitle="Laporan Omset Konsolidasi">
            <Head title="Owner — Laporan Omset" />

            <div className="space-y-6">
                {/* Filters */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 className="font-bold text-slate-800 dark:text-white/95 mb-4 flex items-center gap-2"><Icon name="tune" className="text-teal-500" /> Filter Laporan</h3>
                    <form onSubmit={handleFilter} className="flex flex-wrap gap-4 items-end">
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-semibold text-slate-500 dark:text-gray-400">Unit Bisnis</label>
                            <select 
                                value={form.unit_bisnis}
                                onChange={(e) => setForm({...form, unit_bisnis: e.target.value})}
                                className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-white dark:bg-gray-850 dark:text-white"
                            >
                                <option value="all">Semua Unit Bisnis</option>
                                <option value="jihans">Jihan's Food</option>
                                <option value="hendhys">Hendhys Brownies</option>
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-semibold text-slate-500 dark:text-gray-400">Periode Rekap</label>
                            <select 
                                value={form.periode}
                                onChange={(e) => setForm({...form, periode: e.target.value})}
                                className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-white dark:bg-gray-850 dark:text-white"
                            >
                                <option value="harian">Harian</option>
                                <option value="mingguan">Mingguan</option>
                                <option value="bulanan">Bulanan</option>
                                <option value="3_bulanan">3 Bulanan (Kuartal)</option>
                                <option value="6_bulanan">6 Bulanan (Semester)</option>
                                <option value="tahunan">Tahunan</option>
                                <option value="keseluruhan">Keseluruhan</option>
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-semibold text-slate-500 dark:text-gray-400">Dari Tanggal</label>
                            <input 
                                type="date"
                                value={form.date_from}
                                onChange={(e) => setForm({...form, date_from: e.target.value})}
                                className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-semibold text-slate-500 dark:text-gray-400">Sampai Tanggal</label>
                            <input 
                                type="date"
                                value={form.date_to}
                                onChange={(e) => setForm({...form, date_to: e.target.value})}
                                className="px-3 py-1.5 text-sm rounded-lg border border-slate-200 outline-none dark:border-gray-700 bg-transparent dark:text-white"
                            />
                        </div>
                        <button type="submit" className="px-5 py-1.8 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg">Terapkan Filter</button>
                    </form>
                </div>

                {/* Graph Card */}
                {reportData.length > 0 && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="font-bold text-slate-800 dark:text-white/95 mb-4 flex items-center gap-2"><Icon name="bar_chart" className="text-teal-500" /> Perbandingan Omset</h3>
                        <div className="w-full">
                            <svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} className="w-full h-auto overflow-visible">
                                {/* Grid lines */}
                                <line x1="40" y1="10" x2={chartWidth - 10} y2="10" stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="40" y1={chartHeight / 2} x2={chartWidth - 10} y2={chartHeight / 2} stroke="#f1f5f9" strokeDasharray="3" />
                                <line x1="40" y1={chartHeight - 20} x2={chartWidth - 10} y2={chartHeight - 20} stroke="#cbd5e1" />

                                {/* Render bars for each data point */}
                                {reportData.map((d, idx) => {
                                    const x = 50 + idx * barWidth;
                                    const hTotal = (d.total / maxVal) * (chartHeight - 40);
                                    const hJihans = (d.jihans / maxVal) * (chartHeight - 40);
                                    const hHendhys = (d.hendhys / maxVal) * (chartHeight - 40);

                                    return (
                                        <g key={idx} className="group">
                                            {/* Stacked bar or side by side. Stacked is clean: Jihans (orange) on bottom, Hendhys (amber) on top */}
                                            <rect
                                                x={x + 4}
                                                y={chartHeight - 20 - hJihans}
                                                width={barWidth - 8}
                                                height={hJihans}
                                                fill="#f97316"
                                                className="transition-all hover:opacity-90"
                                            />
                                            <rect
                                                x={x + 4}
                                                y={chartHeight - 20 - hJihans - hHendhys}
                                                width={barWidth - 8}
                                                height={hHendhys}
                                                fill="#f59e0b"
                                                className="transition-all hover:opacity-90"
                                            />
                                            
                                            {/* Date/Label */}
                                            <text
                                                x={x + barWidth / 2}
                                                y={chartHeight - 5}
                                                textAnchor="middle"
                                                className="text-[8px] font-semibold fill-slate-400 dark:fill-gray-500"
                                            >
                                                {d.label}
                                            </text>
                                            
                                            {/* Tooltip on group hover */}
                                            <g className="hidden group-hover:block pointer-events-none">
                                                <rect x={x - 20} y={chartHeight - 20 - hTotal - 35} width="80" height="30" rx="4" fill="#0f172a" />
                                                <text x={x + 20} y={chartHeight - 20 - hTotal - 23} textAnchor="middle" fill="#fff" className="text-[7px] font-bold">Jihans: {formatQty(d.jihans / 1000)}k</text>
                                                <text x={x + 20} y={chartHeight - 20 - hTotal - 13} textAnchor="middle" fill="#fff" className="text-[7px] font-bold">Hendhys: {formatQty(d.hendhys / 1000)}k</text>
                                            </g>
                                        </g>
                                    );
                                })}
                            </svg>
                            <div className="flex gap-4 justify-center mt-3 text-xs">
                                <span className="flex items-center gap-1"><span className="h-3 w-3 bg-orange-500 rounded" /> Jihan's Food</span>
                                <span className="flex items-center gap-1"><span className="h-3 w-3 bg-amber-500 rounded" /> Hendhys Brownies</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Detail Table */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                        <h3 className="font-bold text-slate-800 dark:text-white/95 text-lg flex items-center gap-2"><Icon name="table_view" className="text-teal-500" /> Rincian Data Omset</h3>
                        <div className="flex gap-2">
                            <button 
                                onClick={() => handleExport('pdf')}
                                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-red-650 hover:bg-red-700 text-white rounded-lg transition"
                            >
                                <Icon name="picture_as_pdf" className="text-[16px]" /> Export PDF
                            </button>
                            <button 
                                onClick={() => handleExport('csv')}
                                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-green-700 hover:bg-green-800 text-white rounded-lg transition"
                            >
                                <Icon name="grid_on" className="text-[16px]" /> Export CSV
                            </button>
                        </div>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 dark:bg-white/[0.02] text-xs font-semibold text-slate-500 dark:text-gray-400">
                                <tr>
                                    <th className="px-4 py-3">Periode</th>
                                    <th className="px-4 py-3 text-right">Jihan's Food</th>
                                    <th className="px-4 py-3 text-right">Hendhys Brownies</th>
                                    <th className="px-4 py-3 text-right">Total Omset</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-gray-800">
                                {reportData.length === 0 ? <EmptyState colSpan={4} icon="table_chart" message="Data omset kosong untuk filter ini." />
                                    : reportData.map((row, idx) => (
                                        <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-4 py-3 font-semibold text-slate-800 dark:text-white/90">{row.label}</td>
                                            <td className="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-medium">{formatRupiah(row.jihans)}</td>
                                            <td className="px-4 py-3 text-right text-amber-600 dark:text-amber-400 font-medium">{formatRupiah(row.hendhys)}</td>
                                            <td className="px-4 py-3 text-right font-black text-slate-900 dark:text-white">{formatRupiah(row.total)}</td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </OwnerLayout>
    );
}
