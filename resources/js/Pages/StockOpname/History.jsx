import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
import StockOpnameResultCard from '@/Components/StockOpnameResultCard';
import Layout, { panel, input, button } from './Layout';
const route = window.route;

export default function History({ counts, filters, actors, locations, summary }) {
    const [form, setForm] = useState({ search: '', location_key: '', counted_by: '', session_id: '', product_id: '', difference: '', date_from: '', date_to: '', ...Object.fromEntries(Object.entries(filters).map(([k, v]) => [k, v ?? ''])) });
    const set = (key, value) => setForm({ ...form, [key]: value });
    return <Layout pageTitle="Riwayat Stock Opname"><Head title="Riwayat SO" /><div className="mx-auto max-w-6xl space-y-6 text-gray-800 dark:text-gray-200">
        <div className="flex flex-wrap justify-between gap-4"><div><h1 className="text-2xl font-bold">Riwayat Stock Opname</h1><p className="mt-2 text-gray-500">Telusuri perubahan stok per produk, petugas, dan sesi izin Owner.</p></div><Link className="text-brand-500 underline" href={route('stock-opname.index')}>Daftar sesi dan akses SO →</Link></div>
        <form className={`${panel} grid gap-4 sm:grid-cols-2 lg:grid-cols-3`} onSubmit={e => { e.preventDefault(); router.get(route('stock-opname.history'), form, { preserveState: true }); }}>
            <label className="text-sm sm:col-span-2 lg:col-span-3">Cari hasil SO<input type="search" maxLength={100} aria-label="Cari hasil SO" placeholder="Produk, kode, nomor SO, petugas, lokasi, Owner, atau alasan" className={`${input} mt-2`} value={form.search} onChange={e => set('search', e.target.value)} /></label>
            <label className="text-sm">Lokasi<select className={`${input} mt-2`} value={form.location_key} onChange={e => set('location_key', e.target.value)}><option value="">Semua lokasi sesuai akses</option>{locations.map(l => <option key={l.key} value={l.key}>{l.name}</option>)}</select></label>
            <label className="text-sm">Petugas yang melakukan SO<select className={`${input} mt-2`} value={form.counted_by} onChange={e => set('counted_by', e.target.value)}><option value="">Semua petugas</option>{actors.map(a => <option key={a.id} value={a.id}>{a.name} (akun #{a.id})</option>)}</select></label>
            <label className="text-sm">Hasil penyesuaian<select className={`${input} mt-2`} value={form.difference} onChange={e => set('difference', e.target.value)}><option value="">Semua hasil</option><option value="decrease">Stok berkurang</option><option value="increase">Stok bertambah</option><option value="same">Jumlah sesuai / tidak berubah</option></select></label>
            <label className="text-sm">Tanggal SO mulai (WIB)<input type="date" className={`${input} mt-2`} value={form.date_from} onChange={e => set('date_from', e.target.value)} /></label>
            <label className="text-sm">Tanggal SO sampai (WIB)<input type="date" min={form.date_from || undefined} className={`${input} mt-2`} value={form.date_to} onChange={e => set('date_to', e.target.value)} /></label>
            <label className="text-sm">Nomor sesi izin Owner<input type="number" min="1" step="1" placeholder="Contoh: 1" className={`${input} mt-2`} value={form.session_id} onChange={e => set('session_id', e.target.value)} /></label>
            <div className="flex flex-wrap gap-3 sm:col-span-2 lg:col-span-3"><button className={button}>Cari riwayat</button><Link className="px-3 py-3 text-brand-500" href={route('stock-opname.history')}>Reset semua filter</Link>{form.product_id && <button type="button" className="text-brand-500 underline" onClick={() => set('product_id', '')}>Hapus batasan produk #{form.product_id}</button>}</div>
        </form>
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">{[['total', 'Hasil SO ditemukan'], ['decreased', 'Stok berkurang'], ['increased', 'Stok bertambah'], ['unchanged', 'Jumlah sesuai']].map(([key, label]) => <div key={key} className={panel}><p className="text-sm text-gray-500">{label}</p><strong className="mt-2 block text-2xl">{summary[key]}</strong></div>)}</div>
        <p className="text-sm text-gray-500">Jumlah sebelum dan sesudah adalah saldo saat SO disimpan. Transaksi setelah SO tidak mengubah riwayat ini.</p>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Hasil SO per produk</h2>{counts.data.length === 0 && <p className="text-gray-500">Belum ada hasil SO yang sesuai pencarian dan akses lokasi Anda.</p>}{counts.data.map(c => <StockOpnameResultCard key={c.id} count={c} location={c.location_name} showSession />)}<Pagination links={counts.links} /></div>
    </div></Layout>;
}
