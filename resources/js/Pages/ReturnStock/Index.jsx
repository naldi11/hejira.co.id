import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Layout, { panel, input, button } from '../StockOpname/Layout';
import Pagination from '@/Components/Pagination';
import { formatQty, formatDate } from '@/lib/format';
export const labels = { damaged: 'Rusak', expired: 'Kedaluwarsa / basi', other: 'Retur lain / kondisi belum jelas' };
export const destinations = { gudang: 'Gudang Utama', 'hendhys-pusat': 'Hendhys Produksi' };
const route = window.route;
export default function Index({ entries, balances, branches, filters }) {
    const [f, set] = useState({ search: '', condition: '', destination: '', branch_id: '', date_from: '', date_to: '', ...filters });
    return <Layout pageTitle="Stok Rusak / Retur"><Head title="Stok Rusak / Retur" /><div className="mx-auto max-w-6xl space-y-6 text-gray-800 dark:text-gray-200">
        <div><h1 className="text-2xl font-bold">Stok Rusak / Retur</h1><p className="mt-2 text-gray-500">Barang retur yang sudah diterima, dipisahkan dari stok siap jual. Tidak bisa dijual, diproduksi, didistribusikan, atau dipakai untuk mutasi cabang.</p></div>
        <div className="rounded-xl bg-red-50 p-4 text-red-800">Simpan fisik barang ini terpisah dan beri label nomor retur. Saldo siap jual dan Stock Opname tidak mencakup stok ini. Retur yang masih dalam perjalanan belum masuk saldo di bawah.</div>
        <form className={`${panel} grid gap-3 sm:grid-cols-2 lg:grid-cols-3`} onSubmit={e => { e.preventDefault(); router.get(route('return-stock.index'), f); }}>
            <input className={input} aria-label="Cari stok retur" placeholder="Produk, nomor retur, cabang, alasan, petugas" value={f.search} onChange={e => set({ ...f, search: e.target.value })} />
            <select className={input} aria-label="Kondisi" value={f.condition} onChange={e => set({ ...f, condition: e.target.value })}><option value="">Semua kondisi</option>{Object.entries(labels).map(([key, name]) => <option key={key} value={key}>{name}</option>)}</select>
            <select className={input} aria-label="Lokasi penyimpanan" value={f.destination} onChange={e => set({ ...f, destination: e.target.value })}><option value="">Semua lokasi penyimpanan</option>{Object.entries(destinations).map(([key, name]) => <option key={key} value={key}>{name}</option>)}</select>
            <select className={input} aria-label="Cabang asal" value={f.branch_id} onChange={e => set({ ...f, branch_id: e.target.value })}><option value="">Semua cabang asal</option>{branches.map(b => <option key={`${b.id}-${b.name}`} value={b.id}>{b.name}</option>)}</select>
            <label>Tanggal retur mulai<input type="date" className={input} value={f.date_from} onChange={e => set({ ...f, date_from: e.target.value })} /></label><label>Sampai<input type="date" className={input} value={f.date_to} onChange={e => set({ ...f, date_to: e.target.value })} /></label>
            <div className="flex gap-3"><button className={button}>Cari / filter</button><Link className="px-4 py-3 text-brand-500" href={route('return-stock.index')}>Reset</Link></div>
        </form>
        <section className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Saldo stok terpisah sesuai filter</h2><p className="text-sm text-gray-500">Dikelompokkan per produk, satuan, kondisi, dan lokasi penyimpanan.</p>{balances.data.length === 0 && <p>Belum ada stok retur yang sudah diterima sesuai filter.</p>}
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{balances.data.map(b => <div className="rounded-xl border border-red-100 p-4 dark:border-gray-700" key={`${b.product_id}-${b.unit_id}-${b.condition}-${b.destination_key}`}><strong>{b.product_name}</strong><p className="text-sm text-gray-500">{b.product_code} · {destinations[b.destination_key]}</p><p className="mt-2 text-red-600">{labels[b.condition]}</p><p className="text-2xl font-bold">{formatQty(b.quantity)} {b.unit_name}</p><p className="text-sm text-gray-500">{b.receipts} rincian penerimaan</p></div>)}</div><Pagination links={balances.links} />
        </section>
        <section className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Riwayat per barang retur · {entries.total} rincian</h2>{entries.data.map(r => <Link className="block rounded-xl border border-gray-200 p-4 hover:border-brand-500 dark:border-gray-700" key={r.id} href={route('return-stock.show', r.id)}><div className="flex flex-wrap justify-between gap-3"><strong>{r.product_name} · {r.return_number}</strong><span className="font-bold text-red-600">{formatQty(r.quantity)} {r.unit_name} · {labels[r.condition]}</span></div><p className="mt-2">{r.origin_name} → {destinations[r.destination_key]}</p><p className="text-sm">Alasan: {r.reason || 'Belum dicatat pada retur lama'}</p><p className="text-sm text-gray-500">Tanggal retur {formatDate(r.return_date)} · Diterima {r.received_at || 'Waktu tidak tercatat pada data lama'} · {r.received_by_name || '-'}</p><p className="mt-2 text-brand-500 underline">Lihat detail penelusuran →</p></Link>)}<Pagination links={entries.links} /></section>
    </div></Layout>;
}
