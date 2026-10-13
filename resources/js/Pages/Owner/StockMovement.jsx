import { Head, Link } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import { formatQty, formatRupiah } from '@/lib/format';

const statuses = { opname_recorded: 'Hasil hitung sudah disimpan', writeoff_reversed: 'Pembatalan tercatat · stok dikembalikan', writeoff_cancelled: 'Penghapusan sudah dibatalkan', written_off: 'Stok sudah dikurangi', sent: 'Terkirim', received: 'Sudah diterima', open: 'Terbuka', closed: 'Ditutup', aktual: 'Produksi aktual', prediksi: 'Prediksi produksi' };
const panel = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900';

export default function StockMovement({ movement, document, document_message }) {
    return <OwnerLayout pageTitle="Detail Mutasi Stok"><Head title={`Mutasi ${movement.product}`} />
        <div className="mx-auto max-w-4xl space-y-5 text-slate-800 dark:text-gray-200">
            <Link href={route('owner.dashboard.detail', { mode: 'stock', unit: 'movements' })} className="inline-block text-sm font-semibold text-indigo-600 dark:text-indigo-400">← Kembali ke daftar mutasi</Link>
            <section className={panel}>
                <p className="text-sm font-semibold text-slate-500">{movement.source}</p>
                <h1 className="mt-1 break-words text-2xl font-bold">{movement.product}</h1>
                <p className="mt-1 text-sm text-slate-500">{movement.code ?? 'Kode tidak tersedia'} · Gudang Utama</p>
                <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <p>Waktu dicatat: <strong>{movement.recorded_at}</strong></p>
                    <p>Dicatat oleh: <strong>{movement.user}</strong></p>
                    <p>Perubahan stok: <strong>{movement.delta === 0 ? 'Tidak berubah' : movement.delta > 0 ? 'Bertambah' : 'Berkurang'}</strong></p>
                    <p>Jumlah perubahan: <strong>{formatQty(Math.abs(movement.delta))} {movement.unit}</strong></p>
                </div>
                <p className="mt-4 rounded-xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-200">{movement.explanation}</p>
                <div className="mt-5 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3 dark:bg-gray-800">
                    <p className="text-sm">Stok di sistem sebelumnya<strong className="mt-1 block text-xl">{formatQty(movement.before)} <span className="text-sm font-normal">{movement.unit}</span></strong></p>
                    <p className="text-sm">Perubahan stok siap jual<strong className={`mt-1 block text-xl ${movement.delta < 0 ? 'text-red-600' : 'text-green-600'}`}>{movement.delta > 0 ? '+' : ''}{formatQty(movement.delta)} <span className="text-sm font-normal">{movement.unit}</span></strong></p>
                    <p className="text-sm">{movement.is_opname ? 'Hasil hitung fisik / stok setelah disimpan' : 'Stok setelah pencatatan'}<strong className="mt-1 block text-xl">{formatQty(movement.after)} <span className="text-sm font-normal">{movement.unit}</span></strong></p>
                </div>
                {movement.delta === 0 && movement.quantity > 0 && <p className="mt-3 text-sm text-amber-700 dark:text-amber-300">Sebanyak {formatQty(movement.quantity)} {movement.unit} barang dicatat tanpa mengubah stok siap jual. Periksa kondisi barang pada dokumen asal.</p>}
                {movement.notes && <p className="mt-4 whitespace-pre-wrap break-words text-sm">{movement.is_opname ? 'Alasan penyesuaian dari petugas' : 'Catatan pencatatan'}: {movement.notes}</p>}
            {movement.original_notes && movement.is_opname && <details className="mt-4 text-sm text-slate-500"><summary className="cursor-pointer">Informasi sistem untuk penelusuran</summary><p className="mt-2 whitespace-pre-wrap break-words">{movement.original_notes}</p><p className="mt-2">Nomor catatan stok: #{movement.id}. Stok pada halaman ini adalah saldo saat pencatatan, bukan saldo terbaru.</p></details>}
            </section>
            {document ? <section className={panel}>
                <h2 className="text-lg font-bold">Asal perubahan stok: {document.title}</h2>
                <p className="mt-2 break-words font-mono font-semibold">{document.number}</p>
                <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <p>Tanggal pencatatan: <strong>{document.date}</strong></p>
                    <p>Status: <strong>{statuses[document.status] ?? document.status}</strong></p>
                    {document.fields.map((field, index) => <p key={index} className="break-words">{field.label}: <strong>{field.value?.trim() || 'Belum tercatat'}</strong></p>)}
                </div>
                {document.notes && <p className="mt-4 whitespace-pre-wrap break-words text-sm">Catatan dokumen: {document.notes}</p>}
                {document.items.length > 0 && <h3 className="mb-3 mt-6 font-bold">Rincian barang dalam dokumen</h3>}
                <div className="space-y-3">{document.items.map(item => <div key={item.id} className={`rounded-xl border p-4 ${item.related ? 'border-indigo-200 bg-indigo-50/50 dark:border-indigo-800 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-gray-800'}`}>
                    <p className="break-words font-semibold">{item.product}</p>
                    {item.related && <p className="mt-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Produk yang terkait dengan mutasi ini</p>}
                    <div className="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                        <p>Jumlah dalam dokumen: <strong>{formatQty(item.quantity)} {item.unit}</strong></p>
                        {item.received != null && <p>Diterima: <strong>{formatQty(item.received)} {item.unit}</strong></p>}
                        {item.condition && <p>Kondisi: <strong>{item.condition}</strong></p>}
                        {item.worker && <p>Petugas produksi: <strong>{item.worker}</strong></p>}
                        {item.price != null && <p>Harga pokok per {item.unit}: {formatRupiah(item.price)}</p>}
                        {item.total != null && <p>Nilai barang: <strong>{formatRupiah(item.total)}</strong></p>}
                    </div>
                    {item.production_lots?.map((lot, index) => <p key={index} className="mt-2 text-sm">Asal produksi: <strong>{lot.number ?? 'Asal belum diketahui'}</strong> · {formatQty(lot.quantity)} {item.unit}</p>)}
                </div>)}</div>
                {document.links?.map(link => <Link key={link.href} href={link.href} className="mt-4 block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{link.label} →</Link>)}
                {document.photos?.length > 0 && <div className="mt-5 grid gap-4 sm:grid-cols-2">{document.photos.map((photo, index) => <figure key={index}><a href={photo.url} target="_blank" rel="noreferrer"><img src={photo.url} alt={photo.caption || `Foto penerimaan ${index + 1}`} className="max-h-64 w-full rounded-xl object-contain" /></a>{photo.caption && <figcaption className="mt-1 text-sm text-slate-500">{photo.caption}</figcaption>}</figure>)}</div>}
            </section> : <section className={panel}><h2 className="font-bold">Asal perubahan stok</h2><p className="mt-2 text-sm text-slate-500">{document_message}</p></section>}
        </div>
    </OwnerLayout>;
}
