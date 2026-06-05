import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import Icon from '@/Components/Icon';
import { formatRupiah } from '@/lib/format';

const route = window.route;

export default function ReportLaci({ rows, filters }) {
    const [form, setForm] = useState({ date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const reload = (e) => {
        e?.preventDefault();
        const p = {};
        Object.entries(form).forEach(([k, v]) => {
            if (v) p[k] = v;
        });
        router.get(route('jihans.reports.laci'), p, { preserveState: true });
    };

    return (
        <JihansLayout pageTitle="Laporan Laci Kasir">
            <Head title="Laci Kasir" />
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Laci Kasir</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Rekap transaksi dan metode pembayaran harian kasir</p>
                    </div>
                    <a
                        href={route('jihans.reports.pdf', 'laci') + `?date_from=${form.date_from}&date_to=${form.date_to}`}
                        target="_blank"
                        className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors shadow-sm"
                    >
                        <Icon name="picture_as_pdf" className="text-[18px]" /> Export PDF
                    </a>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.01]">
                        <form onSubmit={reload} className="flex flex-wrap items-center gap-3">
                            <div className="flex items-center gap-2">
                                <input
                                    type="date"
                                    value={form.date_from}
                                    onChange={(e) => setForm({...form, date_from: e.target.value})}
                                    className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                                <span className="text-sm text-gray-400 dark:text-gray-500">s/d</span>
                                <input
                                    type="date"
                                    value={form.date_to}
                                    onChange={(e) => setForm({...form, date_to: e.target.value})}
                                    className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors"
                            >
                                Filter
                            </button>
                        </form>
                    </div>
                    
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Tanggal</th>
                                    <th className="px-6 py-4 text-center font-semibold">Transaksi</th>
                                    <th className="px-6 py-4 text-right font-semibold">Total</th>
                                    <th className="px-6 py-4 text-right font-semibold">Tunai</th>
                                    <th className="px-6 py-4 text-right font-semibold">Debit</th>
                                    <th className="px-6 py-4 text-right font-semibold">Kredit Card</th>
                                    <th className="px-6 py-4 text-right font-semibold">Kredit</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {rows.data?.length === 0 ? (
                                    <EmptyState colSpan={7} icon="assessment" message="Tidak ada data." />
                                ) : (
                                    rows.data?.map((r, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium">{r.date}</td>
                                            <td className="px-6 py-4 text-center font-bold text-gray-800 dark:text-white/90">{r.jumlah_transaksi}</td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-950 dark:text-white">{formatRupiah(r.total_transaksi)}</td>
                                            <td className="px-6 py-4 text-right text-gray-600 dark:text-gray-300">{formatRupiah(r.tunai)}</td>
                                            <td className="px-6 py-4 text-right text-gray-600 dark:text-gray-300">{formatRupiah(r.kartu_debit)}</td>
                                            <td className="px-6 py-4 text-right text-gray-600 dark:text-gray-300">{formatRupiah(r.kartu_kredit)}</td>
                                            <td className="px-6 py-4 text-right font-semibold text-red-600 dark:text-red-400">{formatRupiah(r.kredit)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {rows.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={rows.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
