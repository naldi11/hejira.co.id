import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import CataloguePages from '@/Components/CataloguePages';
import Layout, { panel, input, button, Errors } from '../StockOpname/Layout';
const route = window.route;
const units = { gudang: 'Gudang', jihans: 'Jihan’s', hendhys: 'Hendhys', izzi: 'Izzi Kebab' };
const empty = () => ({ product_id: '', to_product_id: '', conversion_from: '1', conversion_to: '1', quantity: '', notes: '' });
function DestinationPicker({ to, initial, page, selected, source, index, onPick }) {
    const catalogue = usePosCatalogue(route('branch-transfers.destination-products', to.id), initial, page, to.id);
    const choices = new Map(catalogue.products.map(p => [String(p.id), p]));
    if (selected) choices.set(String(selected.id), selected);
    // A product already registered as an Izzi ingredient can be reused in another Izzi outlet.
    if (source && to.entity !== 'izzi') choices.set(String(source.id), source);
    return <div className="space-y-2"><label className="block">Cari barang tujuan baris {index + 1}<input type="search" className={input} value={catalogue.query} onChange={e => catalogue.setQuery(e.target.value)} placeholder="Nama atau kode barang tujuan…" /></label><CataloguePages catalogue={catalogue} />
        <label className="block">Barang di cabang penerima baris {index + 1}<select required className={input} value={selected?.id || ''} onChange={e => onPick(choices.get(e.target.value))}><option value="">Pilih barang / bahan yang sama di tujuan</option>{[...choices.values()].map(p => <option value={p.id} key={p.id}>{p.name} · {p.code} · {p.unit}</option>)}</select></label>
        <p className="text-sm text-gray-500">Pilih barang fisik yang sama. Jika tujuan Izzi, pilih bahan yang sudah didaftarkan pada Bahan & Satuan Kemasan.</p>
    </div>;
}
function Form({ sources, destinations, from, to, products, catalogue: page, destinationProducts, destinationCatalogue, today, currentTime }) {
    const { auth } = usePage().props;
    const [loadingTarget, setLoadingTarget] = useState(false);
    const sourceCatalogue = usePosCatalogue(route('branch-transfers.source-products', from.id), products, page, from.id);
    const sourceBank = useRef(new Map(products.map(p => [String(p.id), p])));
    const targetBank = useRef(new Map(destinationProducts.map(p => [String(p.id), p])));
    for (const p of sourceCatalogue.products) sourceBank.current.set(String(p.id), p);
    for (const p of destinationProducts) targetBank.current.set(String(p.id), p);
    const form = useForm({ request_key: crypto.randomUUID(), from_branch_id: from.id, to_branch_id: to?.id || '', date: today, occurred_time: currentTime, physical_sender_name: auth.user.name, physical_receiver_name: '', reason: '', notes: '', courier_name: '', items: [empty()], confirmed: false });
    const changeItem = (index, key, value) => form.setData('items', form.data.items.map((item, i) => i === index ? { ...item, [key]: value } : item));
    const chooseSource = (index, id) => {
        const p = sourceBank.current.get(String(id));
        const reuse = p && to && (to.entity !== 'izzi' || from.entity === 'izzi' || destinationProducts.some(d => Number(d.id) === Number(p.id)));
        if (reuse) targetBank.current.set(String(p.id), p);
        form.setData('items', form.data.items.map((r, i) => i === index ? { ...empty(), notes:r.notes, product_id:id, to_product_id:reuse ? p.id : '' } : r));
    };
    const chooseTarget = (index, p, source) => {
        if (p) targetBank.current.set(String(p.id), p);
        form.setData('items', form.data.items.map((r, i) => i === index ? { ...r, to_product_id:p?.id || '', conversion_from:'1', conversion_to:p && Number(p.unit_id) !== Number(source?.unit_id) ? '' : '1' } : r));
    };
    const changeDestination = id => {
        form.setData(d => ({ ...d, to_branch_id:id, items:d.items.map(r => ({ ...r, to_product_id:'', conversion_from:'1', conversion_to:'1' })) }));
        router.get(route('branch-transfers.create'), { from_branch_id:from.id, to_branch_id:id || undefined }, { preserveState:true, preserveScroll:true, onStart:()=>setLoadingTarget(true), onFinish:()=>setLoadingTarget(false) });
    };
    const blocked = form.data.items.some(i => i.product_id && !sourceBank.current.get(String(i.product_id))?.unit_matches);
    const choices = new Map(sourceCatalogue.products.map(p => [String(p.id), p]));
    for (const i of form.data.items) if (sourceBank.current.has(String(i.product_id))) choices.set(String(i.product_id), sourceBank.current.get(String(i.product_id)));
    return <form className="space-y-6" onSubmit={e => { e.preventDefault(); form.post(route('branch-transfers.store')); }}>
        <Errors errors={form.errors} />
        <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900"><strong>Pencatatan setelah barang diterima</strong><p className="mt-2 text-sm">Status otomatis Selesai diterima. Sekali simpan, pengurangan asal dan penambahan tujuan dicatat bersama. Pastikan mutasi belum pernah dicatat.</p></div>
        <div className={`${panel} space-y-2`}><p className="text-sm text-gray-500">Nomor mutasi otomatis</p><strong className="font-mono">MUT-{form.data.date.replaceAll('-', '')}-xxxx</strong><p className="text-sm text-gray-500">Nomor mengikuti tanggal mutasi, dengan urutan baru setiap hari.</p></div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Asal dan tujuan</h2>
            <label className="block">Lokasi pengirim<select className={`${input} mt-2`} value={from.id} disabled={form.processing} onChange={e => router.get(route('branch-transfers.create'), { from_branch_id:e.target.value })}>{sources.map(b => <option key={b.id} value={b.id}>{units[b.entity]} · {b.name}</option>)}</select></label>
            {sources.length > 1 && <p className="text-sm text-gray-500">Mengganti asal memulai form baru dengan stok cabang tersebut.</p>}
            <label className="block">Lokasi tujuan<select required className={`${input} mt-2`} value={form.data.to_branch_id} disabled={form.processing || loadingTarget} onChange={e => changeDestination(e.target.value)}><option value="">Pilih Gudang atau cabang Jihan’s, Hendhys, atau Izzi</option>{destinations.map(b => <option key={b.id} value={b.id}>{units[b.entity]} · {b.name}</option>)}</select></label>
            {(from.entity === 'jihans' || to?.entity === 'jihans') && <p className="rounded-lg bg-amber-50 p-3 text-amber-800">Stok penjualan Jihan’s masih satu saldo bersama. Mutasi antar cabang Jihan’s mencatat perpindahan fisik; jika produknya sama, saldo bersama tetap. Mutasi ke/dari unit lain mengubah saldo bersama tersebut.</p>}
            <div className="grid gap-4 sm:grid-cols-2"><label>Tanggal mutasi<input required type="date" max={today} className={`${input} mt-2`} value={form.data.date} onChange={e => form.setData('date', e.target.value)} /><span className="mt-1 block text-sm text-gray-500">Otomatis hari ini (WIB), tetap bisa diubah.</span></label><label>Jam barang diterima (WIB)<input required type="time" className={`${input} mt-2`} value={form.data.occurred_time} onChange={e => form.setData('occurred_time', e.target.value)} /></label></div>
            <div className="grid gap-4 sm:grid-cols-2"><label>Nama pengirim barang<input required minLength={2} maxLength={255} className={`${input} mt-2`} value={form.data.physical_sender_name} onChange={e => form.setData('physical_sender_name', e.target.value)} /></label><label>Nama penerima di cabang tujuan<input required minLength={2} maxLength={255} placeholder="Petugas yang menerima barang" className={`${input} mt-2`} value={form.data.physical_receiver_name} onChange={e => form.setData('physical_receiver_name', e.target.value)} /></label></div>
            <p className="text-sm text-gray-500">Akun pencatat: <strong>{auth.user.name}</strong>. Nama pengirim dan penerima barang dicatat terpisah dari akun pencatat.</p>
            <label className="block">Alasan mutasi<textarea required minLength={5} maxLength={1000} className={`${input} mt-2`} placeholder="Contoh: cabang penerima membutuhkan bahan untuk operasional." value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} /></label>
            <label className="block">Petugas pengantar (opsional)<input maxLength={255} className={`${input} mt-2`} value={form.data.courier_name} onChange={e => form.setData('courier_name', e.target.value)} /></label>
            <label className="block">Catatan mutasi / kondisi saat diterima (opsional)<textarea maxLength={2000} className={`${input} mt-2`} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></label>
        </div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Barang yang telah diterima</h2><p className="text-sm text-gray-500">Satuan mengikuti master barang asal dan tujuan. Jika pencatatan barang atau satuannya berbeda, pilih barang tujuan dan isi konversi yang benar.</p>
            <label className="block">Cari barang asal<input type="search" className={input} value={sourceCatalogue.query} onChange={e => sourceCatalogue.setQuery(e.target.value)} placeholder="Nama atau kode barang asal…" /></label><CataloguePages catalogue={sourceCatalogue} />
            {products.length === 0 && <p className="text-red-600">Belum ada stok siap jual di asal. Mutasi membutuhkan stok tersedia.</p>}
            {form.data.items.map((item, index) => {
                const product = sourceBank.current.get(String(item.product_id));
                const target = targetBank.current.get(String(item.to_product_id));
                const amount = Number(item.quantity || 0) * Number(item.conversion_to || 0) / Number(item.conversion_from || 1);
                const differing = target && product && Number(target.id) !== Number(product.id);
                return <div key={index} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><div className="flex justify-between gap-3"><strong>Barang {index + 1}</strong>{form.data.items.length > 1 && <button type="button" className="text-red-600" onClick={() => form.setData('items', form.data.items.filter((_, i) => i !== index))}>Hapus baris</button>}</div>
                    <label className="block">Produk asal baris {index + 1}<select required className={input} value={item.product_id} onChange={e => chooseSource(index, e.target.value)}><option value="">Pilih produk dari stok asal</option>{[...choices.values()].map(p => <option key={p.id} value={p.id} disabled={!p.unit_matches || form.data.items.some((r, i) => i !== index && String(r.product_id) === String(p.id))}>{p.name} · {p.code} · {p.quantity} {p.stock_unit ?? '?'}{!p.unit_matches ? ' · Satuan belum sesuai' : ''}</option>)}</select></label>
                    {product && !product.unit_matches && <p role="alert" className="text-red-600">Satuan kartu stok {product.stock_unit} berbeda dari master {product.unit}. Periksa kartu stok sebelum mutasi.</p>}
                    <label className="block">Jumlah dipindahkan ({product?.unit || 'satuan asal'})<input required type="number" min={from.entity === 'izzi' ? '0.001' : '1'} step={from.entity === 'izzi' ? '0.001' : '1'} max={product?.quantity ?? 999999999} disabled={!product?.unit_matches} className={input} value={item.quantity} onChange={e => changeItem(index, 'quantity', e.target.value)} /></label>
                    {product?.unit_matches && <p className="text-sm text-gray-500">Tersedia {product.quantity} {product.unit} · Sisa asal {(Number(product.quantity) - Number(item.quantity || 0)).toLocaleString('id-ID',{maximumFractionDigits:3})} {product.unit}</p>}
                    {product && to && !loadingTarget && <DestinationPicker key={`${to.id}-${index}`} to={to} initial={destinationProducts} page={destinationCatalogue} selected={target} source={product} index={index} onPick={p => chooseTarget(index, p, product)} />}
                    {loadingTarget && <p>Memuat pilihan barang tujuan…</p>}
                    {differing && <div className="space-y-3 rounded-lg bg-blue-50 p-3 text-blue-900"><strong>Konversi barang yang sama</strong><p className="text-sm">Contoh: 1 PACK asal = 2000 gram tujuan. Untuk arah sebaliknya, isi 2000 gram asal = 1 PACK tujuan.</p><div className="grid grid-cols-2 gap-3"><label>Jumlah acuan asal ({product.unit})<input required type="number" min="0.001" step="0.001" className={input} value={item.conversion_from} onChange={e => changeItem(index, 'conversion_from', e.target.value)} /></label><label>Setara jumlah tujuan ({target.unit})<input required type="number" min="0.001" step="0.001" className={input} value={item.conversion_to} onChange={e => changeItem(index, 'conversion_to', e.target.value)} /></label></div><p>{item.conversion_from || '?'} {product.unit} = {item.conversion_to || '?'} {target.unit}</p></div>}
                    {target && <p className="font-semibold text-green-700">Tujuan menerima {amount.toLocaleString('id-ID',{maximumFractionDigits:3})} {target.unit} · {target.name}</p>}
                    {target && to?.entity !== 'izzi' && !Number.isInteger(Number(amount.toFixed(3))) && <p className="text-amber-700">Tujuan Gudang/Jihan’s/Hendhys membutuhkan jumlah bulat sesuai satuan master. Periksa jumlah dan konversinya.</p>}
                    <label className="block">Catatan barang (opsional)<input maxLength={500} className={input} value={item.notes} onChange={e => changeItem(index, 'notes', e.target.value)} /></label>
                </div>;
            })}<button type="button" disabled={form.data.items.length >= 100 || form.processing} className="text-brand-500" onClick={() => form.setData('items', [...form.data.items, empty()])}>+ Tambah barang</button>
        </div><div className={`${panel} space-y-4`}><label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Barang sudah diterima, belum pernah dicatat sebagai mutasi, dan pilihan barang serta konversinya sudah saya periksa.</label><button disabled={form.processing || loadingTarget || !form.data.confirmed || !form.data.to_branch_id || blocked} className={button}>Simpan mutasi selesai</button></div>
    </form>;
}
export default function Create(props) {
    return <Layout pageTitle="Catat Mutasi Cabang & Gudang"><Head title="Buat Mutasi" /><div className="mx-auto max-w-4xl space-y-6 text-gray-800 dark:text-gray-200"><Link className="text-brand-500" href={route('branch-transfers.index')}>← Daftar mutasi</Link><h1 className="text-2xl font-bold">Catat Mutasi Cabang & Gudang</h1><Form key={props.from.id} {...props} /></div></Layout>;
}
