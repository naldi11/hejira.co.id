import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { formatQty } from '@/lib/format';

const labels = { tidak_habis: 'Tidak habis dalam satu hari', rusak: 'Rusak / tidak layak', kedaluwarsa: 'Kedaluwarsa', lainnya: 'Alasan lain' };

function ReverseForm({ row, onClose, onRestored }) {
    const [requestKey] = useState(() => crypto.randomUUID());
    const { data, setData, post, processing, errors } = useForm({ request_key: requestKey, notes: '', confirmed: false });
    const submit = e => { e.preventDefault(); post(route('owner.production-writeoffs.reverse', row.id), { preserveScroll: true, onSuccess: () => { onClose(); onRestored?.(); } }); };
    return <form onSubmit={submit} className="mt-4 space-y-3 border-t border-slate-200 pt-4 dark:border-gray-700">
        <h4 className="font-semibold">Batalkan penghapusan #{row.id}</h4>
        <p className="text-sm">Sebanyak <strong>{formatQty(row.quantity)} {row.unit_name}</strong> akan kembali ke <strong>{row.production_number ?? 'sumber stok tanpa nomor produksi yang sama'}</strong>. Catatan penghapusan awal tetap tersimpan.</p>
        <label className="block text-sm font-semibold">Alasan pembatalan<textarea required minLength="5" maxLength="1000" rows="3" value={data.notes} onChange={e => { setData('notes', e.target.value); setData('confirmed', false); }} placeholder="Contoh: Salah memilih nomor produksi; barang sebenarnya masih ada." className="mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2.5 dark:border-gray-700" /></label>
        <label className="flex items-start gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200"><input className="mt-1" type="checkbox" required checked={data.confirmed} onChange={e => setData('confirmed', e.target.checked)} /><span>Saya memastikan pembatalan ini benar dan barang masih ada di lokasi produksi tersebut.</span></label>
        {Object.entries(errors).map(([key, error]) => <p key={key} role="alert" className="text-sm font-semibold text-red-600">{error}</p>)}
        <div className="flex flex-wrap gap-3"><button type="submit" disabled={processing || !data.confirmed || data.notes.trim().length < 5} className="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">{processing ? 'Mengembalikan stok...' : 'Batalkan dan kembalikan stok'}</button><button type="button" disabled={processing} onClick={onClose} className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm">Tutup</button></div>
    </form>;
}

export default function History({ history, onChanged }) {
    const [reversing, setReversing] = useState(null);
    return <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <h2 className="text-lg font-bold">Riwayat penghapusan sisa produksi</h2>
        <p className="mt-1 text-sm text-slate-500">Penghapusan tercatat terpisah dari pengiriman ke cabang.</p>
        <div className="my-4 space-y-3">{history?.data?.map(row => <article key={row.id} className="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
            <div className="flex flex-wrap justify-between gap-2"><h3 className="font-bold">{row.product_name}</h3><p className={`font-bold ${row.reversal_id ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600'}`}>{row.reversal_id ? 'Dibatalkan · dikembalikan' : 'Dihapus'} {formatQty(row.quantity)} {row.unit_name}</p></div>
            <p className="mt-1 break-words text-sm">{row.entity === 'hendhys' ? 'Hendhys Produksi' : 'Jihans Produksi'} · {row.production_number ?? 'Tanpa nomor produksi (stok lama / asal belum diketahui)'}</p>
            <p className="mt-2 text-sm">Sisa saat penghapusan awal: <strong>{formatQty(row.remaining_before)} → {formatQty(row.remaining_after)} {row.unit_name}</strong></p>
            <p className="mt-1 text-sm">Alasan: <strong>{labels[row.reason]}</strong></p>
            <p className="mt-1 whitespace-pre-wrap break-words text-sm">{row.notes}</p>
            <p className="mt-2 text-xs text-slate-500">Penghapusan #{row.id} · {row.recorded_at} · Owner: {row.owner_name}</p>
            {row.batch_id && <p className="mt-1 text-xs text-slate-500">Kelompok penghapusan sekaligus #{row.batch_id}</p>}
            {row.reversal_id ? <div className="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200"><p className="font-semibold">Pembatalan #{row.reversal_id} · {row.reversed_at_label} · Owner: {row.reversal_owner}</p><p className="mt-2">Sisa sumber saat pembatalan: <strong>{formatQty(row.reversal_remaining_before)} → {formatQty(row.reversal_remaining_after)} {row.unit_name}</strong></p><p className="mt-2 whitespace-pre-wrap break-words">Alasan pembatalan: {row.reversal_notes}</p></div>
                : reversing === row.id ? <ReverseForm row={row} onClose={() => setReversing(null)} onRestored={onChanged} />
                    : <button type="button" onClick={() => setReversing(row.id)} className="mt-3 mr-4 rounded-lg border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400" aria-label={`Batalkan penghapusan #${row.id}`}>Batalkan penghapusan</button>}
            {row.production_id != null && <Link href={route('owner.production.show', { entity: row.entity, id: row.production_id })} className="mt-3 inline-block text-sm font-semibold text-indigo-600 dark:text-indigo-400">Lihat rincian produksi →</Link>}
        </article>)}{!history?.data?.length && <p className="py-4 text-sm text-slate-500">Belum ada penghapusan sisa produksi untuk pilihan ini.</p>}</div>
        <Pagination links={history?.links} />
    </section>;
}
