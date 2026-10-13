import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Pagination from '@/Components/Pagination';
import { formatQty } from '@/lib/format';
import History from './History';
import BulkForm from './BulkForm';

const field = 'mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2.5 dark:border-gray-700';
const panel = 'rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900';
const stockKey = stock => `${stock.entity}-${stock.product_id}-${stock.lot_id ?? 'legacy'}`;

function WriteoffForm({ stock, reasons, onClose }) {
    const [requestKey] = useState(() => crypto.randomUUID());
    const { data, setData, post, processing, errors } = useForm({
        request_key: requestKey, entity: stock.entity, product_id: stock.product_id,
        lot_id: stock.lot_id, expected_remaining: Number(stock.remaining), quantity: Number(stock.remaining),
        reason: 'tidak_habis', notes: '', confirmed: false,
    });
    const amount = Number(data.quantity);
    const validAmount = Number.isInteger(amount) && amount > 0 && amount <= Number(stock.remaining);
    const submit = e => { e.preventDefault(); post(route('owner.production-writeoffs.store'), { preserveScroll: true, onSuccess: onClose }); };
    return <form onSubmit={submit} className="mt-4 space-y-4 border-t border-slate-200 pt-4 dark:border-gray-700">
        <h3 className="font-bold">Catat penghapusan {stock.product_name}</h3>
        <p className="text-sm text-slate-500">Pastikan barang sejumlah ini sudah dipisahkan dari stok yang akan dijual atau dikirim.</p>
        <label className="block text-sm font-semibold">Jumlah yang dihapus ({stock.unit_name})
            <input type="number" required min="1" max={Number(stock.remaining)} step="1" className={field} value={data.quantity} onChange={e => { setData('quantity', e.target.value); setData('confirmed', false); }} />
        </label>
        <button type="button" disabled={processing} onClick={() => { setData('quantity', Number(stock.remaining)); setData('confirmed', false); }} className="text-sm font-semibold text-indigo-600">Gunakan seluruh sisa ({formatQty(stock.remaining)} {stock.unit_name})</button>
        <label className="block text-sm font-semibold">Alasan penghapusan
            <select value={data.reason} onChange={e => { setData('reason', e.target.value); setData('confirmed', false); }} className={field}>{Object.entries(reasons).map(([key, value]) => <option key={key} value={key}>{value}</option>)}</select>
        </label>
        <label className="block text-sm font-semibold">Catatan kejadian
            <textarea required minLength="5" maxLength="1000" rows="3" className={field} placeholder="Contoh: Sisa 20 brownies produksi kemarin dipisahkan dan dibuang." value={data.notes} onChange={e => { setData('notes', e.target.value); setData('confirmed', false); }} />
        </label>
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
            <p>Stok sumber ini akan berkurang <strong>{validAmount ? formatQty(amount) : '—'} {stock.unit_name}</strong>. Sisa setelah penghapusan: <strong>{validAmount ? formatQty(Number(stock.remaining) - amount) : '—'} {stock.unit_name}</strong>.</p>
            <p className="mt-2">Tindakan ini mengurangi stok fisik yang tercatat dan menyimpan riwayat penghapusan. Pastikan sumber dan jumlahnya benar.</p>
            <label className="mt-3 flex items-start gap-2"><input type="checkbox" required className="mt-1" checked={data.confirmed} onChange={e => setData('confirmed', e.target.checked)} /><span>Saya memastikan sumber stok, jumlah, dan alasan penghapusan sudah benar.</span></label>
        </div>
        {Object.entries(errors).map(([key, error]) => <p key={key} role="alert" className="text-sm font-semibold text-red-600">{error}</p>)}
        <div className="flex flex-wrap gap-3"><button type="submit" disabled={processing || !data.confirmed || !validAmount || data.notes.trim().length < 5} className="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-40">{processing ? 'Mencatat...' : 'Hapus dan catat sisa stok'}</button><button type="button" disabled={processing} onClick={onClose} className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm">Batal</button></div>
    </form>;
}

export default function Index({ stocks, history, filters, reasons }) {
    const [selected, setSelected] = useState(null);
    const [bulkStocks, setBulkStocks] = useState({});
    const bulkRows = Object.values(bulkStocks);
    const toggleStock = stock => {
        setSelected(null);
        setBulkStocks(current => { const next = { ...current }; const key = stockKey(stock); if (next[key]) delete next[key]; else if (Object.keys(next).length < 50) next[key] = stock; return next; });
    };
    const allPageSelected = stocks.data.length > 0 && stocks.data.every(stock => bulkStocks[stockKey(stock)]);
    const togglePage = () => {
        setSelected(null);
        setBulkStocks(current => { const next = { ...current }; stocks.data.forEach(stock => { const key = stockKey(stock); if (allPageSelected) delete next[key]; else if (Object.keys(next).length < 50) next[key] = stock; }); return next; });
    };
    const filter = useForm({ entity: filters.entity ?? '', search: filters.search ?? '' });
    const submitFilter = e => { e.preventDefault(); setSelected(null); setBulkStocks({}); filter.get(route('owner.production-writeoffs.index'), { preserveState: true }); };
    return <OwnerLayout pageTitle="Hapus Sisa Produksi"><Head title="Hapus Sisa Produksi" />
        <div className="mx-auto max-w-5xl space-y-5 text-slate-800 dark:text-gray-200">
            <header><h1 className="text-2xl font-bold">Hapus sisa stok produksi</h1><p className="mt-2 text-sm text-slate-500">Khusus owner. Pilih sisa produksi Jihans atau Hendhys yang akan dikeluarkan dari stok, lalu catat jumlah dan alasannya.</p></header>
            <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">Stok yang masih disimpan untuk besok tetap dicatat sebagai sisa produksi lama. Untuk barang yang dikirim ke cabang, gunakan menu pengiriman. Batas 17.00 WIB Hendhys adalah batas distribusi; kondisi barang tetap diperiksa sebelum penghapusan.</div>
            <section className={panel}>
                <h2 className="text-lg font-bold">Pilih stok yang tersisa</h2>
                <form onSubmit={submitFilter} className="my-4 grid items-end gap-3 sm:grid-cols-[1fr_2fr_auto]">
                    <label className="text-sm">Unit usaha<select className={field} value={filter.data.entity} onChange={e => filter.setData('entity', e.target.value)}><option value="">Semua unit</option><option value="hendhys">Hendhys Produksi</option><option value="jihans">Jihans Produksi</option></select></label>
                    <label className="text-sm">Cari produk atau nomor produksi<input type="search" className={field} value={filter.data.search} onChange={e => filter.setData('search', e.target.value)} placeholder="Contoh: Brownies atau HND-PRD..." /></label>
                    <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Tampilkan</button>
                </form>
                {stocks.data.length > 0 && <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm dark:bg-gray-800"><label className="flex items-center gap-2"><input type="checkbox" checked={allPageSelected} onChange={togglePage} />Pilih semua di halaman ini</label><span>{bulkRows.length} dipilih · Maksimal 50 pilihan</span>{bulkRows.length > 0 && <button type="button" onClick={() => setBulkStocks({})} className="font-semibold text-indigo-600">Kosongkan pilihan</button>}</div>}
                <div className="space-y-3">{stocks.data.map(stock => {
                    const key = stockKey(stock);
                    return <article key={key} className="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                        <div className="flex flex-wrap justify-between gap-2"><h3 className="font-bold">{stock.product_name}</h3><p className="font-bold">Sisa {formatQty(stock.remaining)} {stock.unit_name}</p></div>
                        <label className="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" checked={!!bulkStocks[key]} disabled={!bulkStocks[key] && bulkRows.length >= 50} onChange={() => toggleStock(stock)} />Pilih {stock.production_number ?? stock.product_name} untuk hapus sekaligus</label>
                        <p className="mt-1 text-sm text-slate-500">{stock.entity === 'hendhys' ? 'Hendhys Produksi' : 'Jihans Produksi · Gudang'}</p>
                        <p className="mt-2 break-words text-sm font-semibold">{stock.production_number ?? 'Tanpa nomor produksi'}</p>
                        {stock.production_date ? <p className="mt-1 text-sm">Tanggal produksi: {stock.production_date}</p> : <p className="mt-2 text-sm text-amber-700 dark:text-amber-400">Stok lama / asal belum diketahui. Tanggal dan nomor produksi tidak dapat dipastikan; penghapusan dicatat terpisah dari produksi yang diketahui.</p>}
                        {stock.production_id != null && <Link href={route('owner.production.show', { entity: stock.entity, id: stock.production_id })} className="mt-3 mr-4 inline-block text-sm font-semibold text-indigo-600">Lihat produksi →</Link>}
                        {selected === key ? <WriteoffForm stock={stock} reasons={reasons} onClose={() => setSelected(null)} /> : <button type="button" disabled={bulkRows.length > 0} onClick={() => setSelected(key)} className="mt-3 rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-40 dark:text-red-300" aria-label={`Hapus sisa ${stock.product_name} ${stock.production_number ?? 'tanpa nomor produksi'}`}>Hapus sisa stok</button>}
                    </article>;
                })}{!stocks.data.length && <p className="py-6 text-center text-sm text-slate-500">Tidak ada sisa stok produksi untuk pilihan ini.</p>}</div>
                <div className="mt-4"><Pagination links={stocks.links} /></div>
            </section>
            {bulkRows.length > 0 && <BulkForm key={Object.keys(bulkStocks).sort().join('|')} stocks={bulkRows} reasons={reasons} onClose={() => setBulkStocks({})} />}
            <History history={history} onChanged={() => { setBulkStocks({}); setSelected(null); }} />
        </div>
    </OwnerLayout>;
}
