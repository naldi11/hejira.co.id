import SessionActions, { SessionState } from './SessionActions';
import StockOpnameResultCard from '@/Components/StockOpnameResultCard';
import { useOpnameActive } from '@/Components/StockOpnameAccess';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
import Layout, { panel, input, button, Errors } from './Layout';
const route = window.route;

function CountForm({ product, session, onClose }) {
    const form = useForm({ product_id: product.id, expected_quantity: product.quantity, quantity: '', notes: '', confirmed: false });
    const diff = form.data.quantity === '' ? null : Number((Number(form.data.quantity) - Number(product.quantity)).toFixed(session.entity === 'izzi' ? 3 : 0));
    return <form className={`${panel} space-y-4 border-brand-300`} onSubmit={e => { e.preventDefault(); form.post(route('stock-opname.count', session.id), { preserveScroll: true, onSuccess: onClose }); }}>
        <h2 className="font-bold">Hitung fisik: {product.name}</h2><Errors errors={form.errors} />
        <p>Stok sistem saat halaman dibuka: <strong>{product.quantity} {product.unit}</strong></p>
        <label className="block">Jumlah yang benar-benar ada (stok siap jual / distribusi)
            <input autoFocus required type="number" min="0" max="999999999" step={session.entity === 'izzi' ? '0.001' : '1'} className={`${input} mt-2`} value={form.data.quantity} onChange={e => form.setData('quantity', e.target.value)} />
        </label>
        {diff !== null && <p className={diff < 0 ? 'text-red-600' : 'text-green-600'}>Selisih: {diff > 0 ? '+' : ''}{diff} {product.unit}. Saldo sesudah disimpan: {form.data.quantity} {product.unit}.</p>}
        <label className="block">Catatan hasil hitung / alasan selisih
            <textarea placeholder="Contoh: Ditemukan barang di rak lain saat hitung ulang. Jelaskan penyebab selisih sesuai keadaan sebenarnya." required minLength={5} maxLength={1000} className={`${input} mt-2`} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} />
        </label>
        <label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Saya sudah menghitung stok fisik. Jumlah ini akan menggantikan saldo sistem di lokasi sesi.</label>
        <div className="flex flex-wrap gap-3"><button disabled={form.processing || !form.data.confirmed || diff === null} className={button}>Simpan hasil hitung</button><button type="button" disabled={form.processing} className="px-4 py-3" onClick={onClose}>Tutup form</button></div>
    </form>;
}
export default function Show({ session, products, counts, isOwner, description, canCount: permitted, filters, events = [], eventsPagination, countTotal = counts.length, productCounts = counts }) {
    const canCount = useOpnameActive(permitted, session);
    const [selected, select] = useState(null);
    const [search, setSearch] = useState(filters.search);
    const counted = new Map(productCounts.map(c => [Number(c.product_id), c]));
    return <Layout><Head title={`Opname #${session.id}`} /><div className="mx-auto max-w-5xl space-y-6 text-gray-800 dark:text-gray-200">
        <Link className="text-brand-500" href={route('stock-opname.index')}>← Daftar sesi stock opname</Link>
        <div className={panel}><div className="flex flex-wrap justify-between gap-2"><h1 className="text-xl font-bold">Opname #{session.id} · {session.location_name}</h1><SessionState session={session} /></div>
            <p className="mt-3 rounded-lg bg-amber-50 p-3 text-amber-800">{description}</p>
            <p className="mt-3">Instruksi: {session.notes}</p><p className="mt-2 text-sm text-gray-500">Dibuka {session.opened_at} oleh {session.opened_by_name}. Akses berakhir {session.expires_at}.</p>
            {session.closed_at && <p className="mt-2 text-sm">Ditutup {session.closed_at} oleh {session.closed_by_name}.</p>}
            {isOwner && <SessionActions key={session.expires_at_iso + session.status} session={session} />}
            {!isOwner && !canCount && <p className="mt-4 text-red-600">Akses hitung fisik tidak aktif. Minta Owner membuka kembali sesi ini untuk melanjutkan produk yang belum dihitung.</p>}
        </div>
        {events.length > 0 && <section className={`${panel} space-y-3`}><h2 className="font-bold">Riwayat akses Owner</h2>{events.map(e => <div className="border-b border-gray-100 pb-3 text-sm dark:border-gray-800" key={e.id}><strong>{({ open: 'Sesi dibuka', close: 'Sesi ditutup', reopen: 'Sesi dibuka kembali' })[e.action]}</strong><p>{e.created_at} · {e.owner_name}</p>{e.expires_at && <p>Batas waktu: {e.expires_at}</p>}{e.previous_expires_at && <p>Batas waktu sebelumnya: {e.previous_expires_at}</p>}{e.previous_closed_at && <p>Sebelumnya ditutup {e.previous_closed_at} oleh {e.previous_closed_by_name}.</p>}{e.notes && <p>Alasan / instruksi: {e.notes}</p>}</div>)}{eventsPagination?.links && <Pagination links={eventsPagination.links} />}</section>}
        <section id="hasil-so-sesi" className={`${panel} space-y-4`}><div className="flex flex-wrap justify-between gap-3"><h2 className="text-lg font-bold">Hasil hitung stok sesi ini · {countTotal} produk</h2><Link className="text-sm text-brand-500 underline" href={route('stock-opname.history', { session_id: session.id })}>Cari seluruh hasil sesi ini →</Link></div>
            <p className="text-sm text-gray-500">Sebelum dan sesudah di bawah adalah saldo saat SO disimpan. Klik produk untuk melihat petugas, alasan, izin Owner, dan jejak penyesuaian.</p>
            {counts.length === 0 ? <p>Belum ada hasil SO yang disimpan dalam sesi ini.</p> : counts.slice(0, 5).map(c => <StockOpnameResultCard key={c.id} count={c} />)}
            {countTotal > 5 && <Link className="inline-block font-semibold text-brand-500 underline" href={route('stock-opname.history', { session_id: session.id })}>Lihat seluruh {countTotal} hasil hitung stok →</Link>}
        </section>
        {canCount && <p className="text-sm text-gray-500">Hitung saat pergerakan barang berhenti sementara. Jika saldo berubah sebelum disimpan, sistem meminta Anda memuat ulang dan menghitung kembali. Setiap produk hanya dapat disimpan satu kali dalam sesi.</p>}
        {['gudang', 'hendhys-pusat'].includes(session.stock_key) && <p className="text-sm text-gray-500">Untuk stok pusat/produksi: kekurangan mengurangi stok produksi terlama terlebih dahulu; kelebihan dicatat tanpa nomor produksi karena asalnya belum diketahui. Stok retur rusak dan kedaluwarsa tidak termasuk hitungan ini.</p>}
        {selected && canCount && <CountForm key={`${session.id}-${selected.id}`} product={selected} session={session} onClose={() => select(null)} />}
        <div className={`${panel} space-y-4`}><h2 className="font-bold">Stok lokasi sesi</h2>
            <form className="flex gap-3" onSubmit={e => { e.preventDefault(); select(null); router.get(route('stock-opname.show', session.id), { search }, { preserveState: true }); }}><input className={input} placeholder="Cari produk atau kode" aria-label="Cari produk" value={search} onChange={e => setSearch(e.target.value)} /><button className={button}>Cari</button></form>
            {products.data.length === 0 && <p>Belum ada kartu stok yang sesuai.</p>}
            {products.data.map(p => <div key={p.id} className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 py-3 dark:border-gray-800"><div><strong>{p.name}</strong><p className="text-sm text-gray-500">{p.code} · Saldo saat ini: {p.quantity} {p.unit}</p></div>{counted.has(Number(p.id)) ? <div className="text-sm"><Link className="font-semibold text-green-600 underline" href={route('stock-opname.result', counted.get(Number(p.id)).id)}>Sudah dihitung · Lihat detail SO →</Link><p className="mt-1 text-gray-500">Sebelum {counted.get(Number(p.id)).quantity_before} → Sesudah {counted.get(Number(p.id)).quantity_after} {counted.get(Number(p.id)).unit_name}</p></div> : canCount && <button className={button} onClick={() => select(p)}>Hitung fisik</button>}</div>)}
            <Pagination links={products.links} />
        </div>
    </div></Layout>;
}
