import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/lib/format';
const route = window.route;
export default function ReportPelanggan({ rows, filters }) {
    const [form, setForm] = useState({ search: filters.search ?? '', date_from: filters.date_from ?? '', date_to: filters.date_to ?? '' });
    const reload = (e) => { e?.preventDefault(); const p = {}; Object.entries(form).forEach(([k, v]) => { if (v) p[k] = v; }); router.get(route('jihans.reports.pelanggan'), p, { preserveState: true }); };
    return (
        <JihansLayout pageTitle="Statistik Pelanggan">
            <Head title="Statistik Pelanggan" />
            <div className="space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">Statistik Pelanggan</h2><a href={route('jihans.reports.pdf', 'pelanggan') + `?date_from=${form.date_from}&date_to=${form.date_to}&search=${form.search}`} target="_blank" className="rounded-xl border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Export PDF</a></div>
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b bg-gray-50/50 p-4"><form onSubmit={reload} className="flex flex-wrap items-center gap-3"><input type="text" value={form.search} onChange={(e) => setForm({...form, search: e.target.value})} placeholder="Cari pelanggan..." className="min-w-[200px] flex-1 rounded-lg border-gray-300 py-2 text-sm" /><input type="date" value={form.date_from} onChange={(e) => setForm({...form, date_from: e.target.value})} className="rounded-lg border-gray-300 py-2 text-sm" /><input type="date" value={form.date_to} onChange={(e) => setForm({...form, date_to: e.target.value})} className="rounded-lg border-gray-300 py-2 text-sm" /><button type="submit" className="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900">Filter</button></form></div>
                    <div className="custom-scrollbar overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3">Pelanggan</th><th className="px-4 py-3">Pertama</th><th className="px-4 py-3">Terakhir</th><th className="px-4 py-3 text-center">Transaksi</th><th className="px-4 py-3 text-right">Total</th><th className="px-4 py-3 text-right">Tunai</th><th className="px-4 py-3 text-right">Kredit</th></tr></thead>
                        <tbody className="divide-y">{rows.data?.length === 0 ? <EmptyState colSpan={7} icon="group" message="Tidak ada data." /> : rows.data?.map((r, i) => (<tr key={i} className="hover:bg-gray-50"><td className="px-4 py-3 font-bold">{r.pelanggan}</td><td className="px-4 py-3">{r.tanggal_pertama}</td><td className="px-4 py-3">{r.tanggal_terakhir}</td><td className="px-4 py-3 text-center font-bold">{r.jumlah_transaksi}</td><td className="px-4 py-3 text-right font-bold">{formatRupiah(r.total_transaksi)}</td><td className="px-4 py-3 text-right">{formatRupiah(r.tunai)}</td><td className="px-4 py-3 text-right text-red-600">{formatRupiah(r.kredit)}</td></tr>))}</tbody></table></div>
                    {rows.links && <div className="border-t p-4"><Pagination links={rows.links} /></div>}
                </div>
            </div>
        </JihansLayout>
    );
}
