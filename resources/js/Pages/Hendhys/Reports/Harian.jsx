import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/lib/format';
const route = window.route;
export default function ReportHarian({ rows, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('hendhys.reports.harian'), p, { preserveState: true }); };
    return (
        <HendhysLayout pageTitle="Laporan Harian">
            <Head title="Laporan Harian" />
            <div className="space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800 dark:text-white/90">Laporan Harian (Per Pelanggan)</h2><a href={route('hendhys.reports.pdf', 'harian') + `?date_from=${form.date_from}&date_to=${form.date_to}`} target="_blank" className="rounded-2xl border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">Export PDF</a></div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b bg-gray-50/50 p-4 dark:bg-white/[0.01]"><form onSubmit={reload} className="flex flex-wrap items-center gap-3"><input type="text" value={form.search} onChange={(e) => setForm({...form, search: e.target.value})} placeholder="Cari pelanggan/no transaksi..." className="min-w-[200px] flex-1 rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" /><input type="date" value={form.date_from} onChange={(e) => setForm({...form, date_from: e.target.value})} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" /><input type="date" value={form.date_to} onChange={(e) => setForm({...form, date_to: e.target.value})} className="rounded-lg border-gray-300 py-2 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" /><button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button></form></div>
                    <div className="custom-scrollbar overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500 dark:text-gray-400 dark:bg-white/[0.02]"><tr><th className="px-4 py-3">No. Transaksi</th><th className="px-4 py-3">Tanggal</th><th className="px-4 py-3">Pelanggan</th><th className="px-4 py-3">Operator</th><th className="px-4 py-3 text-right">Total</th></tr></thead>
                        <tbody className="divide-y">{rows.data?.length === 0 ? <EmptyState colSpan={5} icon="receipt_long" message="Tidak ada data." /> : rows.data?.map((r) => (<tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-white/[0.01]"><td className="px-4 py-3 font-bold">{r.transaction_number}</td><td className="px-4 py-3">{r.date}</td><td className="px-4 py-3">{r.customer_name}</td><td className="px-4 py-3 text-gray-500 dark:text-gray-400">{r.operator}</td><td className="px-4 py-3 text-right font-bold">{formatRupiah(r.grand_total)}</td></tr>))}</tbody></table></div>
                    {rows.links && <div className="border-t p-4"><Pagination links={rows.links} /></div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
