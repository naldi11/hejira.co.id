import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Layout, { panel, input, button, Errors } from '../StockOpname/Layout';
const route = window.route;

function Form({ sources, destinations, from, products, today, currentTime }) {
    const { auth } = usePage().props;
    const form = useForm({ request_key: crypto.randomUUID(), from_branch_id: from.id, to_branch_id: '', date: today, occurred_time: currentTime, physical_sender_name: auth.user.name, physical_receiver_name: '', reason: '', notes: '', courier_name: '', items: [{ product_id: '', quantity: '', notes: '' }], confirmed: false });
    const changeItem = (index, key, value) => form.setData('items', form.data.items.map((item, i) => i === index ? { ...item, [key]: value } : item));
    return <form className="space-y-6" onSubmit={e => { e.preventDefault(); form.post(route('branch-transfers.store')); }}>
        <Errors errors={form.errors} />
        <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900"><strong>Pencatatan setelah barang diterima</strong><p className="mt-2 text-sm">Status otomatis Selesai diterima. Sekali simpan, stok asal berkurang dan stok penerima bertambah. Pastikan mutasi ini belum pernah dicatat.</p></div>
        <div className={`${panel} space-y-2`}><p className="text-sm text-gray-500">Nomor mutasi otomatis</p><strong className="font-mono">MUT-{form.data.date.replaceAll('-', '')}-xxxx</strong><p className="text-sm text-gray-500">Nomor urut ditetapkan saat disimpan, dimulai 0001 untuk setiap tanggal mutasi. Mengubah tanggal akan mengubah bagian tanggal pada nomor.</p></div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Asal dan tujuan</h2>
            <label className="block">Cabang pengirim<select className={`${input} mt-2`} value={from.id} disabled={form.processing} onChange={e => router.get(route('branch-transfers.create'), { from_branch_id: e.target.value })}>{sources.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</select></label>
            {sources.length > 1 && <p className="text-sm text-gray-500">Mengganti asal akan memulai form baru dan menampilkan stok lokasi tersebut.</p>}
            {from.entity === 'jihans' && <p className="rounded-lg bg-amber-50 p-3 text-amber-800">Pengiriman ini mengurangi saldo penjualan bersama seluruh cabang Jihan’s. Cabang asal dicatat sebagai lokasi pengirim; stok gudang/produksi Jihan’s terpisah dan tidak digunakan.</p>}
            <label className="block">Cabang tujuan<select required className={`${input} mt-2`} value={form.data.to_branch_id} onChange={e => form.setData('to_branch_id', e.target.value)}><option value="">Pilih cabang Hendhys penerima</option>{destinations.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</select></label>
            <div className="grid gap-4 sm:grid-cols-2"><label>Tanggal mutasi<input required type="date" max={today} className={`${input} mt-2`} value={form.data.date} onChange={e => form.setData('date', e.target.value)} /><span className="mt-1 block text-sm text-gray-500">Otomatis hari ini (WIB). Ubah jika mencatat mutasi hari sebelumnya.</span></label><label>Jam barang diterima (WIB)<input required type="time" className={`${input} mt-2`} value={form.data.occurred_time} onChange={e => form.setData('occurred_time', e.target.value)} /></label></div>
            <div className="grid gap-4 sm:grid-cols-2"><label>Nama pengirim barang<input required minLength={2} maxLength={255} className={`${input} mt-2`} value={form.data.physical_sender_name} onChange={e => form.setData('physical_sender_name', e.target.value)} /></label><label>Nama penerima di cabang tujuan<input required minLength={2} maxLength={255} placeholder="Petugas yang menerima barang" className={`${input} mt-2`} value={form.data.physical_receiver_name} onChange={e => form.setData('physical_receiver_name', e.target.value)} /></label></div>
            <p className="text-sm text-gray-500">Dicatat oleh akun <strong>{auth.user.name}</strong>. Nama petugas barang disimpan terpisah dari akun pencatat; pengisian nama penerima merupakan catatan penerimaan dari pencatat.</p>
            <label className="block">Alasan mutasi<textarea required minLength={5} maxLength={1000} className={`${input} mt-2`} placeholder="Contoh: cabang tujuan kekurangan stok brownies untuk penjualan sore." value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} /></label>
            <label className="block">Petugas pengantar (opsional)<input maxLength={255} className={`${input} mt-2`} value={form.data.courier_name} onChange={e => form.setData('courier_name', e.target.value)} /></label>
            <label className="block">Catatan mutasi / kondisi saat diterima (opsional)<textarea maxLength={2000} className={`${input} mt-2`} placeholder="Contoh: diterima lengkap, kondisi baik, bukti serah terima nomor 12." value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></label>
        </div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Barang yang telah diterima</h2>
            {products.length === 0 && <p className="text-red-600">Belum ada stok siap jual di saldo asal. Mutasi membutuhkan stok yang tersedia.</p>}
            {form.data.items.map((item, index) => {
                const product = products.find(p => String(p.id) === String(item.product_id));
                return <div key={index} className="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <div className="flex items-center justify-between gap-3"><strong>Barang {index + 1}</strong>{form.data.items.length > 1 && <button type="button" className="text-red-600" onClick={() => form.setData('items', form.data.items.filter((_, i) => i !== index))}>Hapus baris</button>}</div>
                    <label className="block">Produk<select required className={`${input} mt-1`} value={item.product_id} onChange={e => changeItem(index, 'product_id', e.target.value)}><option value="">Pilih produk dari stok asal</option>{products.map(p => <option key={p.id} value={p.id} disabled={form.data.items.some((other, i) => i !== index && String(other.product_id) === String(p.id))}>{p.name} · {p.quantity} {p.unit}</option>)}</select></label>
                    <label className="block">Jumlah dipindahkan dan diterima<input required type="number" min="1" step="1" max={product?.quantity ?? 999999999} className={`${input} mt-1`} value={item.quantity} onChange={e => changeItem(index, 'quantity', e.target.value)} /></label>
                    {product && <p className="text-sm text-gray-500">Tersedia {product.quantity} {product.unit} · Sisa stok asal setelah dicatat: {product.quantity - Number(item.quantity || 0)} {product.unit}</p>}
                    <label className="block">Catatan barang (opsional)<input maxLength={500} className={`${input} mt-1`} placeholder="Contoh: 2 kotak, kondisi baik." value={item.notes} onChange={e => changeItem(index, 'notes', e.target.value)} /></label>
                </div>;
            })}<button type="button" disabled={form.data.items.length >= 100 || form.processing} className="text-brand-500 disabled:opacity-50" onClick={() => form.setData('items', [...form.data.items, { product_id: '', quantity: '', notes: '' }])}>+ Tambah barang</button>
        </div>
        <div className={`${panel} space-y-4`}><p className="text-sm text-gray-500">Masukkan hanya jumlah yang sudah diterima. Periksa tanggal, asal, tujuan, nama petugas, dan barang sebelum menyimpan. Riwayat beserta perubahan saldo stok akan tersimpan.</p>
            <label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Barang sudah diterima oleh cabang tujuan dan belum pernah dicatat sebagai mutasi. Rincian ini sudah saya periksa.</label>
            <button disabled={form.processing || !form.data.confirmed || products.length === 0} className={button}>Simpan mutasi selesai</button>
        </div>
    </form>;
}
export default function Create(props) {
    return <Layout pageTitle="Catat Mutasi Antar Cabang"><Head title="Buat Mutasi" /><div className="mx-auto max-w-4xl space-y-6 text-gray-800 dark:text-gray-200"><Link className="text-brand-500" href={route('branch-transfers.index')}>← Daftar mutasi</Link><h1 className="text-2xl font-bold">Catat Mutasi Antar Cabang</h1><Form key={props.from.id} {...props} /></div></Layout>;
}
