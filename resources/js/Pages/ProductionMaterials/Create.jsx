import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import SelectField from '@/Components/SelectField';
import CataloguePages from '@/Components/CataloguePages';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import { panel, input, button, Errors } from '../StockOpname/Layout';
const route = window.route;
const emptyItem = () => ({ product_id: '', quantity: '', notes: '' });

export default function Create({ products, catalogue: initialCatalogue, location, today, currentTime }) {
    const { auth } = usePage().props;
    const catalogue = usePosCatalogue(route('production-materials.create'), products, initialCatalogue, location.branch_id);
    const knownProducts = useRef(new Map(products.map(p => [String(p.id), p])));
    catalogue.products.forEach(p => knownProducts.current.set(String(p.id), p));
    const form = useForm({ request_key: crypto.randomUUID(), branch_id: location.branch_id, date: today, time: currentTime,
        receiver_name: auth.user.name, reason: '', notes: '', evidence_reference: '', items: [emptyItem()] });
    const setItem = (index, key, value) => form.setData('items', form.data.items.map((item, i) => i === index ? {
        ...item, [key]: value, ...(key === 'product_id' ? { quantity: '' } : {}),
    } : item));
    const validItems = form.data.items.every(item => {
        const product = knownProducts.current.get(String(item.product_id));
        return product?.unit_matches && Number(item.quantity) > 0 && Number(item.quantity) <= product.quantity;
    });
    return <HendhysLayout pageTitle="Gunakan Stok untuk Produksi"><Head title="Gunakan Stok untuk Produksi" />
        <div className="mx-auto max-w-4xl space-y-5 text-gray-800 dark:text-gray-200">
            <Link className="text-brand-500" href={route('production-materials.index')}>← Riwayat pemakaian stok</Link>
            <h1 className="text-2xl font-bold">Gunakan Stok untuk Produksi</h1>
            <p className="rounded-xl bg-blue-50 p-4 text-blue-900 dark:bg-blue-950 dark:text-blue-200">Cabang aktif: <strong>{location.branch_name}</strong>. Pilih barang yang sudah digunakan, isi jumlah dan keperluannya, lalu simpan. Stok cabang ini langsung berkurang.</p>
            <form className={`${panel} space-y-5`} onSubmit={e => { e.preventDefault(); form.post(route('production-materials.store')); }}>
                <Errors errors={form.errors} />
                <div className="grid gap-4 sm:grid-cols-2">
                    <label>Tanggal pemakaian<input required type="date" max={today} className={`${input} mt-2`} value={form.data.date} onChange={e => form.setData('date', e.target.value)} /></label>
                    <label>Jam pemakaian (WIB)<input required type="time" className={`${input} mt-2`} value={form.data.time} onChange={e => form.setData('time', e.target.value)} /></label>
                </div>
                <p className="text-sm text-gray-500">Tanggal dan jam otomatis saat form dibuka, bisa disesuaikan dengan waktu pemakaian sebenarnya. Nomor pemakaian dibuat otomatis saat disimpan.</p>
                <label className="block">Digunakan oleh<input required minLength={2} maxLength={255} className={`${input} mt-2`} value={form.data.receiver_name} onChange={e => form.setData('receiver_name', e.target.value)} /></label>
                <label className="block">Keperluan produksi<input required minLength={5} maxLength={1000} placeholder="Contoh: bahan untuk membuat brownies pagi" className={`${input} mt-2`} value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} /></label>
                <div className="space-y-3">
                    <h2 className="font-bold">Barang yang digunakan</h2>
                    <p className="text-sm text-gray-500">Ketik nama atau kode di pilihan barang untuk mencari seluruh stok cabang aktif. Jumlah memakai satuan master produk; stok rusak dan retur tidak termasuk.</p>
                    <CataloguePages catalogue={catalogue} />
                    {form.data.items.map((item, index) => {
                        const product = knownProducts.current.get(String(item.product_id));
                        const options = [...catalogue.products];
                        if (product && !options.some(p => p.id === product.id)) options.unshift(product);
                        return <div key={index} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div className="flex justify-between"><strong>Barang {index + 1}</strong>{form.data.items.length > 1 && <button type="button" className="text-red-600" onClick={() => form.setData('items', form.data.items.filter((_, i) => i !== index))}>Hapus baris</button>}</div>
                            <label className="block">Pilih barang<SelectField isLoading={catalogue.loading} filterOption={() => true} options={options.map(p => ({ value: p.id, label: `${p.name} · ${p.code} · ${p.quantity} ${p.stock_unit || '?'}`, isDisabled: !p.unit_matches || form.data.items.some((other, n) => n !== index && String(other.product_id) === String(p.id)) }))} value={item.product_id} onChange={value => setItem(index, 'product_id', value)} onInputChange={(value, meta) => { if (meta.action === 'input-change') catalogue.setQuery(value); }} placeholder="Ketik nama atau kode barang" /></label>
                            <label className="block">Jumlah digunakan {product ? `(${product.unit || '?'})` : ''}<input required disabled={!product?.unit_matches} type="number" min="1" step="1" max={product?.quantity || 0} className={`${input} mt-2`} value={item.quantity} onChange={e => setItem(index, 'quantity', e.target.value)} /></label>
                            {product && !product.unit_matches && <p className="text-sm text-red-600">Satuan kartu stok berbeda dari master. Minta admin memeriksa sebelum barang digunakan.</p>}
                            {product?.unit_matches && <p className="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">Stok {location.branch_name}: <strong>{product.quantity} {product.unit}</strong> → sisa <strong>{product.quantity - Number(item.quantity || 0)} {product.unit}</strong> setelah disimpan.</p>}
                            <label className="block text-sm">Catatan barang (opsional)<input maxLength={500} className={`${input} mt-2`} placeholder="Contoh: dipakai untuk 2 adonan" value={item.notes} onChange={e => setItem(index, 'notes', e.target.value)} /></label>
                        </div>;
                    })}
                    <button type="button" disabled={form.data.items.length >= 100} className="font-semibold text-brand-500" onClick={() => form.setData('items', [...form.data.items, emptyItem()])}>+ Tambah barang</button>
                </div>
                <details className="space-y-3 text-sm"><summary className="cursor-pointer font-semibold">Catatan dan bukti tambahan (opsional)</summary>
                    <label className="block pt-3">Nomor bukti<input maxLength={255} className={`${input} mt-2`} value={form.data.evidence_reference} onChange={e => form.setData('evidence_reference', e.target.value)} /></label>
                    <label className="block">Catatan tambahan<textarea maxLength={2000} className={`${input} mt-2`} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></label>
                </details>
                <p className="text-sm text-gray-500">Dicatat oleh {auth.user.name}. Stok sebelum dan sesudah pemakaian, waktu, petugas, dan alasan akan tersimpan dalam riwayat.</p>
                <button disabled={form.processing || !validItems || Boolean(catalogue.error)} className={button}>{form.processing ? 'Menyimpan…' : 'Catat pemakaian & kurangi stok'}</button>
            </form>
        </div>
    </HendhysLayout>;
}
