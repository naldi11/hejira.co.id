import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/lib/format';
const route = window.route;
export default function ReportMingguan({ rows, filters }) {
    const [form, setForm] = useState({ date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('hendhys.reports.mingguan'), p, { preserveState: true }); };
    return (
        <HendhysLayout pageTitle="Laporan Mingguan">
            <Head title="Laporan Mingguan" />
            <div className="space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800 dark:text-white/90">Laporan Mingguan</h2><a href={route('hendhys.reports.pdf', 'mingguan') + `?date_from=${form.date_from}&date_to=${form.date_to}`} target="_blank" className="rounded-2xl border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">Export PDF</a></div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b bg-gray-50/50 p-4 dark:bg-white/[0.01]"><form onSubmit={reload} className="flex flex-wrap items-center gap-3"><input type="date" value={form.date_from} onChange={(e) => {
                                        const newDateFrom = e.target.value;
                                        let newDateTo = form.date_to;
                                        if (newDateFrom) {
                                            const from = new Date(newDateFrom);
                                            from.setDate(from.getDate() + 6);
                                            newDateTo = from.toISOString().split('T')[0];
                                        }
                                        setForm({...form, date_from: newDateFrom, date_to: newDateTo});
                                    }} className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:border-amber-500 focus:ring-amber-500" /><span className="text-gray-400 dark:text-gray-500">s/d</span><input type="date" value={form.date_to} onChange={(e) => setForm({...form, date_to: e.target.value})} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" /><button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button></form></div>
                    <div className="custom-scrollbar overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500 dark:text-gray-400 dark:bg-white/[0.02]"><tr><th className="px-4 py-3">Minggu</th><th className="px-4 py-3">Periode</th><th className="px-4 py-3 text-center">Transaksi</th><th className="px-4 py-3 text-right">Total</th><th className="px-4 py-3 text-right">Tunai</th><th className="px-4 py-3 text-right">Debit</th><th className="px-4 py-3 text-right">Kredit</th></tr></thead>
                        <tbody className="divide-y">{rows.data?.length === 0 ? <EmptyState colSpan={7} icon="date_range" message="Tidak ada data." /> : rows.data?.map((r, i) => (<tr key={i} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]"><td className="px-4 py-3 font-bold">{r.tahun_minggu}</td><td className="px-4 py-3 text-xs">{r.minggu_mulai} — {r.minggu_akhir}</td><td className="px-4 py-3 text-center font-bold">{r.jumlah_transaksi}</td><td className="px-4 py-3 text-right font-bold">{formatRupiah(r.total_transaksi)}</td><td className="px-4 py-3 text-right">{formatRupiah(r.tunai)}</td><td className="px-4 py-3 text-right">{formatRupiah(r.kartu_debit)}</td><td className="px-4 py-3 text-right text-red-600 dark:text-red-400">{formatRupiah(r.kredit)}</td></tr>))}</tbody></table></div>
                    {rows.links && <div className="border-t p-4"><Pagination links={rows.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
