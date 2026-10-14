import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Pagination from '@/Components/Pagination';
import { panel, input, button } from '../StockOpname/Layout';
const route = window.route;

export default function Index({ transactions, filters, location }) {
    const [form, setForm] = useState({ search: '', per_page: '20', status: '', date_from: '', date_to: '', ...filters });
    const set = (key, value) => setForm({ ...form, [key]: value });
    return <HendhysLayout pageTitle="Riwayat Pemakaian Stok"><Head title="Riwayat Pemakaian Stok" /><div className="mx-auto max-w-6xl space-y-6 text-gray-800 dark:text-gray-200">
        <div className="flex flex-wrap items-center justify-between gap-4"><div><h1 className="text-2xl font-bold">Pemakaian Stok untuk Produksi</h1><p className="mt-2 text-gray-500">Riwayat pemakaian stok di {location.branch_name}. Setiap catatan menunjukkan barang, petugas, keperluan, dan perubahan saldo.</p></div><Link className={button} href={route('production-materials.create')}>Gunakan stok untuk produksi</Link></div>
        <form className={`${panel} grid gap-4 sm:grid-cols-2 lg:grid-cols-3`} onSubmit={e => { e.preventDefault(); router.get(route('production-materials.index'), form, { preserveState: true }); }}>
            <label className="sm:col-span-2 lg:col-span-3">Cari riwayat<input type="search" maxLength={100} className={`${input} mt-2`} value={form.search ?? ''} onChange={e => set('search', e.target.value)} placeholder="Nomor pemakaian, barang/kode, petugas, keperluan, bukti, atau pembatalan" /></label>
            <label>Data per halaman<select className={`${input} mt-2`} value={form.per_page} onChange={e => set('per_page', e.target.value)}>{[['20', '20'], ['50', '50'], ['100', '100'], ['all', 'Semua']].map(([value, text]) => <option key={value} value={value}>{text}</option>)}</select></label>
            <label>Status<select className={`${input} mt-2`} value={form.status ?? ''} onChange={e => set('status', e.target.value)}><option value="">Semua status</option><option value="completed">Selesai dicatat</option><option value="cancelled">Dibatalkan</option></select></label>
            <label>Tanggal mulai<input type="date" className={`${input} mt-2`} value={form.date_from ?? ''} onChange={e => set('date_from', e.target.value)} /></label>
            <label>Tanggal sampai<input type="date" min={form.date_from || undefined} className={`${input} mt-2`} value={form.date_to ?? ''} onChange={e => set('date_to', e.target.value)} /></label>
            <div className="flex items-end gap-3"><button className={button}>Cari riwayat</button><Link className="py-3 text-brand-500" href={route('production-materials.index')}>Reset</Link></div>
            <p className="text-sm text-gray-500 sm:col-span-2 lg:col-span-3">Semua tanggal ditampilkan. Nama barang dan petugas mengikuti data saat transaksi dicatat, sehingga tetap dapat ditelusuri walaupun master berubah.</p>
        </form>
        <div className={`${panel} space-y-4`}><h2 className="font-bold">{transactions.total} transaksi ditemukan</h2>
            {transactions.data.length === 0 && <p className="text-gray-500">Belum ada transaksi yang sesuai. Coba ubah pencarian atau catat kegiatan baru.</p>}
            {transactions.data.map(t => <article key={t.id} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><div className="flex flex-wrap justify-between gap-3"><Link className="font-mono font-bold text-brand-500 underline" href={route('production-materials.show', t.id)}>{t.number}</Link><span className={`rounded-full px-3 py-1 text-sm ${t.status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'}`}>{t.status_label}</span></div><h3 className="font-semibold">{t.kind_label}</h3><p className="text-sm">Kejadian: {t.occurred_label} · Cabang: {t.branch_name}</p><p className="text-sm">Digunakan oleh: {t.receiver_name}</p><p className="text-sm text-gray-500">Dicatat {t.recorded_label} oleh {t.created_by_name}</p><div className="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">{t.items.slice(0, 4).map(i => <p key={i.id}>{i.product_name} · {i.product_code} · {i.quantity} {i.unit_name}</p>)}{t.items.length > 4 && <p>+{t.items.length - 4} barang lainnya</p>}</div><p className="text-sm">Keperluan: {t.reason}</p>{t.cancel_reason && <p className="text-sm text-red-700">Pembatalan: {t.cancel_reason} · {t.cancelled_by_name} · {t.cancelled_label}</p>}<Link className="inline-block text-sm text-brand-500 underline" href={route('production-materials.show', t.id)}>Lihat rincian dan jejak perubahan stok →</Link></article>)}
            <Pagination links={transactions.links} />
        </div>
    </div></HendhysLayout>;
}
