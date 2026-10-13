import { Head, Link, useForm, usePage } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import { panel, input, button, Errors } from '../StockOpname/Layout';
const route = window.route;

export default function Create({ stocks, kinds, productions, lots, today, currentTime }) {
    const { auth } = usePage().props;
    const form = useForm({ request_key: crypto.randomUUID(), kind: 'warehouse_transfer', date: today, time: currentTime, production_id: '', sender_name: auth.user.name, receiver_name: '', reason: '', notes: '', evidence_reference: '', items: [{ product_id: '', quantity: '', lot_id: '', notes: '' }], confirmed: false });
    const products = stocks[form.data.kind];
    const usage = form.data.kind === 'production_usage';
    const setItem = (index, key, value) => form.setData('items', form.data.items.map((item, i) => i === index ? { ...item, [key]: value, ...(key === 'product_id' ? { quantity: '', lot_id: '' } : {}) } : item));
    const validItems = form.data.items.every(i => products.find(p => String(p.id) === String(i.product_id))?.unit_matches && Number(i.quantity) > 0);
    return <GudangLayout pageTitle="Catat Barang Produksi"><Head title="Catat Barang Produksi" /><div className="mx-auto max-w-4xl space-y-6 text-gray-800 dark:text-gray-200">
        <Link className="text-brand-500" href={route('production-materials.index')}>← Riwayat barang produksi</Link>
        <h1 className="text-2xl font-bold">Catat Barang untuk Produksi</h1>
        <p className="text-gray-500">Khusus Super Admin. Catat barang yang sudah diserahterimakan atau dipakai. Jangan mencatat ulang transaksi yang sudah masuk melalui menu pengiriman lainnya.</p>
        <form className="space-y-6" onSubmit={e => { e.preventDefault(); form.post(route('production-materials.store')); }}>
            <Errors errors={form.errors} />
            <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">1. Pilih kegiatan</h2>
                {Object.entries(kinds).map(([key, label]) => <label key={key} className={`flex cursor-pointer gap-3 rounded-xl border p-4 ${form.data.kind === key ? 'border-brand-500' : 'border-gray-200 dark:border-gray-700'}`}><input type="radio" name="kind" value={key} checked={form.data.kind === key} onChange={() => form.setData({ ...form.data, kind: key, items: [{ product_id: '', quantity: '', lot_id: '', notes: '' }], confirmed: false })} /><span><strong>{label}</strong><span className="mt-1 block text-sm text-gray-500">{key === 'warehouse_transfer' ? 'Stok Gudang Utama berkurang; stok Hendhys Produksi bertambah setelah barang diterima.' : 'Bahan yang sudah dipakai mengurangi stok Hendhys Produksi. Hasil produksi dicatat terpisah lewat form produksi.'}</span></span></label>)}
                <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">{usage ? 'Asal: Hendhys Produksi → digunakan oleh tim produksi. Stok cabang tidak digunakan.' : 'Asal: Gudang Utama → tujuan: Hendhys Produksi. Saldo penjualan Jihan’s dan stok cabang tidak digunakan.'}</p>
            </div>
            <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">2. Waktu dan penanggung jawab</h2>
                <p className="text-sm text-gray-500">Nomor otomatis: <strong className="font-mono">BPR-{form.data.date.replaceAll('-', '')}-xxxx</strong>. Urutan ditentukan saat disimpan, mulai 0001 setiap tanggal.</p>
                <div className="grid gap-4 sm:grid-cols-2"><label>Tanggal kejadian<input required type="date" max={today} className={`${input} mt-2`} value={form.data.date} onChange={e => form.setData('date', e.target.value)} /><span className="mt-1 block text-sm text-gray-500">Otomatis hari ini (WIB), bisa diubah ke tanggal sebelumnya.</span></label><label>Jam kejadian (WIB)<input required type="time" className={`${input} mt-2`} value={form.data.time} onChange={e => form.setData('time', e.target.value)} /></label></div>
                <div className="grid gap-4 sm:grid-cols-2"><label>Petugas yang menyerahkan barang<input required minLength={2} maxLength={255} className={`${input} mt-2`} value={form.data.sender_name} onChange={e => form.setData('sender_name', e.target.value)} /></label><label>{usage ? 'Petugas produksi yang memakai barang' : 'Petugas penerima di Hendhys Produksi'}<input required minLength={2} maxLength={255} className={`${input} mt-2`} value={form.data.receiver_name} onChange={e => form.setData('receiver_name', e.target.value)} /></label></div>
                <p className="text-sm text-gray-500">Akun pencatat: <strong>{auth.user.name}</strong>. Nama petugas barang disimpan terpisah dari akun pencatat.</p>
                <label className="block">Nomor produksi yang menggunakan barang {usage ? '(wajib)' : '(opsional)'}<select required={usage} className={`${input} mt-2`} value={form.data.production_id} onChange={e => form.setData('production_id', e.target.value)}><option value="">Pilih nomor produksi Hendhys</option>{productions.map(p => <option key={p.id} value={p.id}>{p.number} · {p.date} · {p.type === 'aktual' ? 'Aktual' : 'Prediksi'}</option>)}</select><span className="mt-1 block text-sm text-gray-500">Untuk pemakaian sebelum hasil produksi dicatat, buat prediksi produksi dahulu lalu pilih nomornya. Pemakaian ini tidak menambah hasil produksi.</span></label>
                <label className="block">Alasan / keperluan<input required minLength={5} maxLength={1000} className={`${input} mt-2`} placeholder="Contoh: bahan untuk produksi brownies pagi" value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} /></label>
                <label className="block">Nomor bukti serah terima (opsional)<input maxLength={255} className={`${input} mt-2`} value={form.data.evidence_reference} onChange={e => form.setData('evidence_reference', e.target.value)} /></label>
                <label className="block">Catatan tambahan (opsional)<textarea maxLength={2000} className={`${input} mt-2`} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></label>
            </div>
            <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">3. Rincian barang</h2><p className="text-sm text-gray-500">Satuan otomatis dari master produk. Masukkan jumlah dalam satuan tersebut; PACK tidak dikonversi ke PCS. Stok rusak/retur tidak dapat digunakan di sini.</p>
                {products.length === 0 && <p className="text-red-600">Tidak ada stok tersedia di lokasi asal.</p>}
                {products.some(p => !p.unit_matches) && <div role="alert" className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{products.filter(p => !p.unit_matches).map(p => <p key={p.id}>{p.name}: kartu stok {p.quantity} {p.stock_unit ?? '?'}, master {p.unit ?? '?'}. Periksa satuan sebelum digunakan.</p>)}</div>}
                {form.data.items.map((item, index) => {
                    const product = products.find(p => String(p.id) === String(item.product_id));
                    const choices = lots[form.data.kind].filter(l => String(l.product_id) === String(item.product_id) && String(l.unit_id) === String(product?.unit_id));
                    const lot = choices.find(l => String(l.id) === String(item.lot_id));
                    const max = lot ? Math.min(Number(lot.remaining), product?.quantity ?? 0) : product?.quantity ?? 0;
                    return <div key={index} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><div className="flex justify-between"><strong>Barang {index + 1}</strong>{form.data.items.length > 1 && <button type="button" className="text-red-600" onClick={() => form.setData('items', form.data.items.filter((_, i) => i !== index))}>Hapus baris</button>}</div>
                        <label className="block">Produk<select required className={`${input} mt-1`} value={item.product_id} onChange={e => setItem(index, 'product_id', e.target.value)}><option value="">Pilih barang dari lokasi asal</option>{products.map(p => <option key={p.id} value={p.id} disabled={!p.unit_matches || form.data.items.some((i, n) => n !== index && String(i.product_id) === String(p.id))}>{p.name} · {p.code} · {p.quantity} {p.stock_unit ?? '?'}{!p.unit_matches ? ' · Satuan belum sesuai' : ''}</option>)}</select></label>
                        <div className="grid gap-4 sm:grid-cols-2"><label>Jumlah {usage ? 'dipakai' : 'diterima'}<input required disabled={!product?.unit_matches} type="number" min="1" step="1" max={max} className={`${input} mt-1`} value={item.quantity} onChange={e => setItem(index, 'quantity', e.target.value)} /></label><label>Satuan master<input readOnly className={`${input} mt-1`} value={product?.unit ?? ''} placeholder="Pilih barang dahulu" /></label></div>
                        {product?.unit_matches && <p className="text-sm text-gray-500">Stok asal {product.quantity} {product.unit} → sisa {product.quantity - Number(item.quantity || 0)} {product.unit} setelah dicatat.</p>}
                        {choices.length > 0 && <label className="block">Nomor produksi asal barang<select className={`${input} mt-1`} value={item.lot_id} onChange={e => setItem(index, 'lot_id', e.target.value)}><option value="">Otomatis: stok tanpa nomor dahulu, lalu produksi tertua</option>{choices.map(l => <option key={l.id} value={l.id}>{l.production_number ?? 'Stok tanpa nomor produksi'} · sisa {l.remaining} {product?.unit}</option>)}</select><span className="mt-1 block text-sm text-gray-500">Ini sumber barang yang diambil, berbeda dari nomor produksi tujuan pemakaian. Alokasi asal tersimpan di riwayat.</span></label>}
                        <label className="block">Catatan barang (opsional)<input maxLength={500} className={`${input} mt-1`} value={item.notes} onChange={e => setItem(index, 'notes', e.target.value)} /></label>
                    </div>;
                })}
                <button type="button" disabled={form.processing || form.data.items.length >= 100} className="text-brand-500 disabled:opacity-50" onClick={() => form.setData('items', [...form.data.items, { product_id: '', quantity: '', lot_id: '', notes: '' }])}>+ Tambah barang</button>
            </div>
            <div className={`${panel} space-y-4`}><label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} /><span>{usage ? 'Barang benar-benar sudah dipakai untuk produksi yang dipilih.' : 'Barang sudah diterima oleh Hendhys Produksi.'} Rincian benar dan transaksi ini belum pernah dicatat.</span></label><button className={button} disabled={form.processing || !form.data.confirmed || !validItems}>{form.processing ? 'Menyimpan…' : 'Simpan dan catat perubahan stok'}</button></div>
        </form>
    </div></GudangLayout>;
}
