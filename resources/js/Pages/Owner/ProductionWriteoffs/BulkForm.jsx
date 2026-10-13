import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { formatQty } from '@/lib/format';

const field = 'mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2.5 dark:border-gray-700';

export default function BulkForm({ stocks, reasons, onClose }) {
    const [requestKey] = useState(() => crypto.randomUUID());
    const { data, setData, post, processing, errors } = useForm({
        request_key: requestKey, items: stocks.map(stock => ({
            entity: stock.entity, product_id: stock.product_id, lot_id: stock.lot_id,
            expected_remaining: Number(stock.remaining), quantity: Number(stock.remaining),
        })), reason: 'tidak_habis', notes: '', confirmed: false,
    });
    const valid = data.items.every(item => Number.isInteger(Number(item.quantity)) && Number(item.quantity) > 0 && Number(item.quantity) <= item.expected_remaining);
    const submit = e => { e.preventDefault(); post(route('owner.production-writeoffs.bulk'), { preserveScroll: true, onSuccess: onClose }); };
    const changeQuantity = (index, quantity) => {
        setData('items', data.items.map((item, i) => i === index ? { ...item, quantity } : item));
        setData('confirmed', false);
    };
    return <section className="rounded-2xl border border-red-200 bg-white p-5 dark:border-red-900 dark:bg-gray-900">
        <h2 className="text-lg font-bold">Hapus {stocks.length} pilihan sekaligus</h2>
        <p className="mt-2 text-sm text-slate-500">Periksa jumlah setiap produk. Alasan dan catatan di bawah berlaku untuk seluruh pilihan.</p>
        <form onSubmit={submit} className="mt-4 space-y-4">
            <div className="space-y-3">{stocks.map((stock, index) => <div key={`${stock.entity}-${stock.product_id}-${stock.lot_id ?? 'legacy'}`} className="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                <h3 className="font-semibold">{stock.product_name}</h3><p className="mt-1 break-words text-sm text-slate-500">{stock.entity === 'hendhys' ? 'Hendhys Produksi' : 'Jihans Produksi'} · {stock.production_number ?? 'Tanpa nomor produksi'}</p>
                <label className="mt-3 block text-sm">Jumlah dihapus untuk {stock.production_number ?? stock.product_name} ({stock.unit_name})<input required type="number" min="1" max={Number(stock.remaining)} step="1" value={data.items[index].quantity} onChange={e => changeQuantity(index, e.target.value)} className={field} /></label>
                <p className="mt-2 text-sm">Sisa sekarang: <strong>{formatQty(stock.remaining)} {stock.unit_name}</strong> · Sisa setelah penghapusan: <strong>{Number(data.items[index].quantity) > 0 && Number(data.items[index].quantity) <= Number(stock.remaining) ? formatQty(Number(stock.remaining) - Number(data.items[index].quantity)) : '—'} {stock.unit_name}</strong></p>
            </div>)}</div>
            <label className="block text-sm font-semibold">Alasan untuk semua pilihan<select value={data.reason} onChange={e => { setData('reason', e.target.value); setData('confirmed', false); }} className={field}>{Object.entries(reasons).map(([key, value]) => <option key={key} value={key}>{value}</option>)}</select></label>
            <label className="block text-sm font-semibold">Catatan penghapusan sekaligus<textarea required minLength="5" maxLength="1000" rows="3" className={field} value={data.notes} onChange={e => { setData('notes', e.target.value); setData('confirmed', false); }} placeholder="Jelaskan kejadian dan penanganan barang yang dipilih." /></label>
            <div className="rounded-xl bg-red-50 p-4 text-sm text-red-900 dark:bg-red-950/30 dark:text-red-200"><p>Setiap nomor produksi dicatat terpisah dalam satu kelompok penghapusan. Jika ada pilihan yang gagal, seluruh proses dibatalkan.</p><label className="mt-3 flex items-start gap-2"><input type="checkbox" required className="mt-1" checked={data.confirmed} onChange={e => setData('confirmed', e.target.checked)} /><span>Saya memastikan seluruh sumber stok, jumlah, dan alasan sudah benar.</span></label></div>
            {Object.entries(errors).map(([key, error]) => <p key={key} role="alert" className="text-sm font-semibold text-red-600">{error}</p>)}
            <div className="flex flex-wrap gap-3"><button type="submit" disabled={processing || !data.confirmed || !valid || data.notes.trim().length < 5} className="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-40">{processing ? 'Mencatat...' : `Hapus ${stocks.length} pilihan dan catat`}</button><button type="button" onClick={onClose} disabled={processing} className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm">Batalkan pilihan</button></div>
        </form>
    </section>;
}
