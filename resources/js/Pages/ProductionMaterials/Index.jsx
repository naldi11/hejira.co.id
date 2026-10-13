import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Pagination from '@/Components/Pagination';
import { panel, input, button } from '../StockOpname/Layout';
const route = window.route;

export default function Index({ transactions, filters, kinds }) {
    const [form, setForm] = useState({ search: '', kind: '', status: '', date_from: '', date_to: '', ...filters });
    const set = (key, value) => setForm({ ...form, [key]: value });
    return <GudangLayout pageTitle="Riwayat Barang Produksi"><Head title="Riwayat Barang Produksi" /><div className="mx-auto max-w-6xl space-y-6 text-gray-800 dark:text-gray-200">
        <div className="flex flex-wrap items-center justify-between gap-4"><div><h1 className="text-2xl font-bold">Barang untuk Produksi</h1><p className="mt-2 text-gray-500">Riwayat pengiriman Gudang → Hendhys Produksi dan pemakaian barang di pusat. Dikelola khusus Super Admin.</p></div><Link className={button} href={route('production-materials.create')}>Catat barang produksi</Link></div>
        <form className={`${panel} grid gap-4 sm:grid-cols-2 lg:grid-cols-3`} onSubmit={e => { e.preventDefault(); router.get(route('production-materials.index'), form, { preserveState: true }); }}>
            <label className="sm:col-span-2 lg:col-span-3">Cari riwayat<input type="search" maxLength={100} className={`${input} mt-2`} value={form.search ?? ''} onChange={e => set('search', e.target.value)} placeholder="Nomor transaksi/produksi, barang/kode, petugas, alasan, bukti, atau pembatalan" /></label>
            <label>Kegiatan<select className={`${input} mt-2`} value={form.kind ?? ''} onChange={e => set('kind', e.target.value)}><option value="">Semua kegiatan</option>{Object.entries(kinds).map(([key, name]) => <option key={key} value={key}>{name}</option>)}</select></label>
            <label>Status<select className={`${input} mt-2`} value={form.status ?? ''} onChange={e => set('status', e.target.value)}><option value="">Semua status</option><option value="completed">Selesai dicatat</option><option value="cancelled">Dibatalkan</option></select></label>
            <label>Tanggal mulai<input type="date" className={`${input} mt-2`} value={form.date_from ?? ''} onChange={e => set('date_from', e.target.value)} /></label>
            <label>Tanggal sampai<input type="date" min={form.date_from || undefined} className={`${input} mt-2`} value={form.date_to ?? ''} onChange={e => set('date_to', e.target.value)} /></label>
            <div className="flex items-end gap-3"><button className={button}>Cari riwayat</button><Link className="py-3 text-brand-500" href={route('production-materials.index')}>Reset</Link></div>
            <p className="text-sm text-gray-500 sm:col-span-2 lg:col-span-3">Semua tanggal ditampilkan. Nama barang dan petugas mengikuti data saat transaksi dicatat, sehingga tetap dapat ditelusuri walaupun master berubah.</p>
        </form>
        <div className={`${panel} space-y-4`}><h2 className="font-bold">{transactions.total} transaksi ditemukan</h2>
            {transactions.data.length === 0 && <p className="text-gray-500">Belum ada transaksi yang sesuai. Coba ubah pencarian atau catat kegiatan baru.</p>}
            {transactions.data.map(t => <article key={t.id} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><div className="flex flex-wrap justify-between gap-3"><Link className="font-mono font-bold text-brand-500 underline" href={route('production-materials.show', t.id)}>{t.number}</Link><span className={`rounded-full px-3 py-1 text-sm ${t.status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'}`}>{t.status_label}</span></div><h3 className="font-semibold">{t.kind_label}</h3><p className="text-sm">Kejadian: {t.occurred_label} · Produksi tujuan: {t.production_number ?? 'Belum ditentukan'}</p><p className="text-sm">Penyerah: {t.sender_name} · {t.kind === 'production_usage' ? 'Pemakai' : 'Penerima'}: {t.receiver_name}</p><p className="text-sm text-gray-500">Dicatat {t.recorded_label} oleh {t.created_by_name}</p><div className="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">{t.items.slice(0, 4).map(i => <p key={i.id}>{i.product_name} · {i.product_code} · {i.quantity} {i.unit_name}</p>)}{t.items.length > 4 && <p>+{t.items.length - 4} barang lainnya</p>}</div><p className="text-sm">Alasan: {t.reason}</p>{t.cancel_reason && <p className="text-sm text-red-700">Pembatalan: {t.cancel_reason} · {t.cancelled_by_name} · {t.cancelled_label}</p>}<Link className="inline-block text-sm text-brand-500 underline" href={route('production-materials.show', t.id)}>Lihat rincian dan jejak perubahan stok →</Link></article>)}
            <Pagination links={transactions.links} />
        </div>
    </div></GudangLayout>;
}
