import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import { Navigation, panel, input, button, Errors, money } from './Common';
export default function Pos(props) {
    const catalogue = usePosCatalogue(route('izzi.pos'), props.products, props.catalogue, props.branch.id);
    const [cart, setCart] = useState([]);
    const form = useForm({ checkout_key: crypto.randomUUID(), branch_id: props.branch.id, customer_name: '', payment_type: 'cash', amount_paid: '', payment_reference: '', items: [] });
    const total = cart.reduce((n, i) => n + i.price * i.quantity, 0);
    const add = p => setCart(items => { const old = items.find(i => i.id === p.id); return old ? items.map(i => i.id === p.id ? { ...i, quantity: Math.min(i.quantity + 1, i.available, 1000) } : i) : [...items, { ...p, quantity: 1 }]; });
    const update = (id, value) => setCart(items => items.map(i => i.id === id ? { ...i, quantity: Math.max(1, Math.min(Number(value) || 1, i.available, 1000)) } : i));
    const paid = form.data.payment_type === 'transfer' ? total : Number(form.data.amount_paid);
    return <IzziLayout pageTitle="Kasir Izzi Kebab"><Head title="Kasir Izzi Kebab" /><div className="space-y-5 text-gray-800 dark:text-gray-200"><Navigation {...props} /><Errors errors={form.errors} />
        <p className="rounded-xl bg-amber-50 p-4 text-amber-900">Setiap menu mengurangi seluruh bahan sesuai resep. Angka tersedia adalah perkiraan untuk satu jenis menu; sistem memeriksa gabungan kebutuhan bahan semua menu saat pembayaran.</p>
        <div className="grid gap-5 lg:grid-cols-2"><section className={`${panel} space-y-4`}><h1 className="text-xl font-bold">Pilih menu</h1><input type="search" aria-label="Cari menu" placeholder="Cari nama atau kode menu…" className={input} value={catalogue.query} onChange={e => catalogue.setQuery(e.target.value)} />
            {catalogue.loading && <p>Memuat menu…</p>}{catalogue.error && <p className="text-red-600">{catalogue.error}</p>}
            {!catalogue.loading && !catalogue.error && !catalogue.products.length && <p>Menu belum tersedia. Admin perlu mengisi resep dan stok bahan.</p>}
            <div className="grid gap-3 sm:grid-cols-2">{catalogue.products.map(p => <button disabled={!p.available || form.processing} type="button" key={p.id} onClick={() => add(p)} className="rounded-xl border p-4 text-left disabled:opacity-40 dark:border-gray-700"><strong>{p.name}</strong><p className="text-sm text-gray-500">{p.code} · {p.category}</p><p>{money(p.price)}</p><p className="text-sm">Perkiraan tersedia: {p.available} menu</p></button>)}</div>
            <div className="flex items-center justify-between gap-3"><button type="button" disabled={catalogue.loading || catalogue.pagination.page <= 1} onClick={() => catalogue.setPage(catalogue.pagination.page - 1)}>← Sebelumnya</button><span>Halaman {catalogue.pagination.page}</span><button type="button" disabled={catalogue.loading || !catalogue.pagination.has_more} onClick={() => catalogue.setPage(catalogue.pagination.page + 1)}>Berikutnya →</button></div>
        </section><form className={`${panel} space-y-4`} onSubmit={e => { e.preventDefault(); form.transform(d => ({ ...d, amount_paid: paid, items: cart.map(i => ({ menu_id: i.id, quantity: i.quantity, price: i.price })) })); form.post(route('izzi.checkout'), { onSuccess: () => setCart([]) }); }}><h2 className="text-xl font-bold">Pesanan & pembayaran</h2>
            {!cart.length && <p>Tekan menu untuk menambah pesanan.</p>}{cart.map(i => <div key={i.id} className="space-y-2 rounded-lg border p-3 dark:border-gray-700"><strong>{i.name}</strong><div className="flex items-center gap-3"><input aria-label={`Jumlah ${i.name}`} className="w-24 rounded-lg dark:bg-gray-800" type="number" min="1" max={Math.min(i.available, 1000)} step="1" value={i.quantity} disabled={form.processing} onChange={e => update(i.id, e.target.value)} /><span>{money(i.price * i.quantity)}</span><button type="button" disabled={form.processing} className="ml-auto text-red-600" onClick={() => setCart(items => items.filter(p => p.id !== i.id))}>Hapus</button></div></div>)}
            <strong className="block text-2xl">Total {money(total)}</strong><label className="block">Nama pelanggan (opsional)<input maxLength="150" className={input} value={form.data.customer_name} onChange={e => form.setData('customer_name', e.target.value)} /></label>
            <label className="block">Cara bayar<select className={input} value={form.data.payment_type} onChange={e => form.setData('payment_type', e.target.value)}><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></label>
            {form.data.payment_type === 'cash' ? <label className="block">Uang diterima<input required type="number" min={total} max="1000000000000" step="1" className={input} value={form.data.amount_paid} onChange={e => form.setData('amount_paid', e.target.value)} /><p>Kembalian {money(Math.max(0, paid - total))}</p></label> : <label className="block">Nomor / keterangan bukti transfer (opsional)<input maxLength="255" className={input} value={form.data.payment_reference} onChange={e => form.setData('payment_reference', e.target.value)} /><p>Pembayaran transfer {money(total)}. Periksa dana sudah diterima.</p></label>}
            <button className={`${button} w-full`} disabled={form.processing || !cart.length || paid < total}>{form.processing ? 'Menyimpan…' : 'Simpan pembayaran & kurangi bahan'}</button>
        </form></div>
    </div></IzziLayout>;
}
