import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout, { panel, input, button, Errors } from '../StockOpname/Layout';
const route = window.route;
function ReceiveForm({ transfer, items, onClose }) {
    const { auth } = usePage().props;
    const form = useForm({ request_key: crypto.randomUUID(), physical_receiver_name: auth.user.name, notes: '', items: items.map(i => ({ id: i.id, quantity: 0, expected_received_quantity: i.received_quantity })), confirmed: false });
    return <form className={`${panel} space-y-4 border-brand-300`} onSubmit={e => { e.preventDefault(); form.post(route('branch-transfers.receive', transfer.id), { preserveScroll: true, onSuccess: onClose }); }}>
        <h2 className="text-lg font-bold">Konfirmasi penerimaan di {transfer.to_branch_name}</h2><Errors errors={form.errors} />
        <p className="text-sm text-gray-500">Isi jumlah tambahan yang benar-benar diterima sekarang dan layak masuk stok siap jual. Sisanya tetap tercatat dalam perjalanan. Catat barang rusak atau belum tiba pada catatan penerimaan.</p>
        <button type="button" className="text-brand-500" onClick={() => form.setData('items', items.map(i => ({ id: i.id, quantity: i.remaining, expected_received_quantity: i.received_quantity })))}>Isi semua sesuai sisa kiriman</button>
        {items.map((item, index) => <label key={item.id} className="block rounded-lg border border-gray-200 p-3 dark:border-gray-700"><strong>{item.product_name}</strong><p className="my-2 text-sm">Dikirim {item.quantity} · Sudah diterima {item.received_quantity} · Belum diterima {item.remaining} {item.unit_name}</p><span className="text-sm">Jumlah diterima sekarang ({item.unit_name})</span><input aria-label={`Jumlah diterima ${item.product_name}`} required type="number" min="0" max={item.remaining} step={item.quantity_scale === 1000 ? "0.001" : "1"} className={`${input} mt-1`} value={form.data.items[index].quantity} onChange={e => form.setData('items', form.data.items.map((i, idx) => idx === index ? { ...i, quantity: e.target.value } : i))} /></label>)}
        <label className="block">Nama penerima barang<input required minLength={2} maxLength={255} className={`${input} mt-1`} value={form.data.physical_receiver_name} onChange={e => form.setData('physical_receiver_name', e.target.value)} /></label><p className="text-sm text-gray-500">Akun yang mengonfirmasi: {auth.user.name}. Nama penerima barang dan akun konfirmasi disimpan terpisah.</p>
        <label className="block">Catatan penerimaan / kondisi barang<textarea required minLength={5} maxLength={2000} className={`${input} mt-1`} placeholder="Contoh: semua barang diterima baik, atau 2 pcs belum tiba." value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></label>
        <label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Saya sudah memeriksa barang fisik. Jumlah tambahan ini akan masuk ke stok cabang tujuan.</label>
        <div className="flex gap-3"><button className={button} disabled={form.processing || !form.data.confirmed}>Simpan penerimaan</button><button type="button" disabled={form.processing} onClick={onClose}>Tutup form</button></div>
    </form>;
}
function CancelForm({ transfer, onClose }) {
    const form = useForm({ reason: '', confirmed: false });
    return <form className={`${panel} space-y-4`} onSubmit={e => { e.preventDefault(); form.post(route('branch-transfers.cancel', transfer.id), { preserveScroll: true, onSuccess: onClose }); }}>
        <h2 className="text-lg font-bold">Batalkan mutasi {transfer.number}</h2><Errors errors={form.errors} />
        <p>Seluruh barang akan kembali ke saldo {transfer.from_branch_name}. Pembatalan hanya dapat dilakukan sebelum ada penerimaan.</p>
        <label className="block">Alasan pembatalan<textarea required minLength={5} maxLength={1000} className={`${input} mt-1`} value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} /></label>
        <label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Seluruh barang dalam kondisi siap jual dan masih berada di cabang asal atau sudah benar-benar dikembalikan ke cabang asal.</label>
        <div className="flex gap-3"><button className="rounded-lg bg-red-600 px-5 py-3 text-white disabled:opacity-50" disabled={form.processing || !form.data.confirmed}>Batalkan dan kembalikan stok</button><button type="button" onClick={onClose} disabled={form.processing}>Tutup form</button></div>
    </form>;
}
export default function Show({ transfer: t, items, receipts, canReceive, canCancel, stockChanges = [] }) {
    const unitLabels = { gudang:'Gudang', jihans:'Jihan’s', hendhys:'Hendhys', izzi:'Izzi Kebab' };
    const afterReceipt = t.recording_mode === 'after_receipt';
    const [receive, openReceive] = useState(false); const [cancel, openCancel] = useState(false);
    return <Layout pageTitle="Detail Mutasi Cabang & Gudang"><Head title={t.number} /><div className="mx-auto max-w-5xl space-y-6 text-gray-800 dark:text-gray-200">
        <Link className="text-brand-500" href={route('branch-transfers.history')}>← Riwayat semua mutasi</Link>
        <div className={`${panel} space-y-4`}><div className="flex flex-wrap justify-between gap-2"><h1 className="text-2xl font-bold">{t.number}</h1><strong className={t.status === 'received' ? 'text-green-600' : t.status === 'partial' ? 'text-amber-600' : 'text-gray-500'}>{t.status_label}</strong></div>
            <p className="text-lg font-semibold">{unitLabels[t.from_entity]} · {t.from_branch_name} → {unitLabels[t.to_entity]} · {t.to_branch_name}</p>
            {(t.from_entity === 'jihans' || t.to_entity === 'jihans') && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Saldo penjualan Jihan’s masih bersama. Mutasi antar cabang Jihan’s untuk produk yang sama tidak mengubah total saldo bersama; lokasi pengirim dan penerima tetap tercatat.</p>}
            <dl className="grid gap-4 sm:grid-cols-2"><div><dt className="text-sm text-gray-500">Tanggal mutasi</dt><dd>{t.date}</dd></div><div><dt className="text-sm text-gray-500">{afterReceipt ? 'Waktu barang diterima (WIB)' : 'Waktu kirim'}</dt><dd>{t.sent_at}</dd></div><div><dt className="text-sm text-gray-500">Pengirim barang</dt><dd>{t.physical_sender_name || t.sent_by_name}</dd></div><div><dt className="text-sm text-gray-500">Penerima barang</dt><dd>{receipts.length ? [...new Set(receipts.map(r => r.physical_receiver_name))].join(', ') : 'Belum diterima'}</dd></div><div><dt className="text-sm text-gray-500">Akun pencatat</dt><dd>{t.sent_by_name}</dd></div><div><dt className="text-sm text-gray-500">Waktu dicatat di sistem</dt><dd>{t.recorded_at}</dd></div><div><dt className="text-sm text-gray-500">Petugas pengantar</dt><dd>{t.courier_name || 'Tidak dicatat'}</dd></div></dl>
            {afterReceipt && <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800">Dicatat setelah barang diterima. Penerimaan dan pengurangan stok asal disimpan bersama. Nama penerima barang adalah catatan dari akun pencatat.</p>}
            <p><strong>Alasan:</strong> {t.reason}</p>{t.notes && <p><strong>{afterReceipt ? 'Catatan mutasi:' : 'Catatan kirim:'}</strong> {t.notes}</p>}
            <div className="flex flex-wrap gap-3">{canReceive && <button className={button} onClick={() => { openReceive(true); openCancel(false); }}>Konfirmasi penerimaan</button>}{canCancel && <button className="rounded-lg border border-red-300 px-4 py-3 text-red-600" onClick={() => { openCancel(true); openReceive(false); }}>Batalkan mutasi</button>}</div>
            {t.cancelled_at && <div className="rounded-lg bg-red-50 p-4 text-red-800"><p>Dibatalkan {t.cancelled_at} oleh {t.cancelled_by_name}.</p><p className="mt-2">{t.cancel_reason}</p><p className="mt-2">Seluruh jumlah kirim kembali ke saldo asal. Dokumen dan riwayat tetap tersimpan.</p></div>}
        </div>
        {receive && canReceive && <ReceiveForm key={receipts.length} transfer={t} items={items} onClose={() => openReceive(false)} />}
        {cancel && canCancel && <CancelForm transfer={t} onClose={() => openCancel(false)} />}
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Rincian barang</h2>
            {items.map(i => <div key={i.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-700"><strong>{i.product_name}</strong><p className="text-sm text-gray-500">{i.product_code} · {i.unit_name}</p><div className="mt-3 grid grid-cols-3 gap-3 text-sm"><p>Dikirim<br /><strong>{i.quantity}</strong></p><p>Diterima<br /><strong>{i.received_quantity}</strong></p><p>{t.status === 'cancelled' ? 'Kembali ke asal' : afterReceipt ? 'Selisih' : 'Dalam perjalanan'}<br /><strong className={Number(i.remaining) > 0 ? 'text-amber-600' : ''}>{t.status === 'cancelled' ? i.quantity : i.remaining}</strong></p></div><p className="mt-3 text-sm">Barang tujuan: <strong>{i.to_product_name}</strong> · {i.to_product_code}<br />Konversi: {i.conversion_from} {i.unit_name} = {i.conversion_to} {i.to_unit_name}<br />Total tujuan {i.destination_quantity} {i.to_unit_name} · Sudah masuk {i.received_destination_quantity} {i.to_unit_name}</p>{i.notes && <p className="mt-3 text-sm">Catatan barang: {i.notes}</p>}</div>)}
        </div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Jejak perubahan stok</h2>
            <p className="text-sm text-gray-500">Saldo berikut adalah saldo sebelum dan sesudah mutasi dicatat, bukan saldo terbaru. Setiap perubahan terkait nomor {t.number}; perubahan dari transaksi lain tidak dimasukkan.</p>
            {stockChanges.map(m => <div key={m.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-700"><strong>{m.location} · {m.product}</strong><p className="mt-2">Saldo {m.before} → {m.after} {m.unit} <span className={m.delta < 0 ? 'text-red-600' : 'text-green-600'}>({m.delta > 0 ? '+' : ''}{m.delta})</span></p><p className="mt-2 text-sm text-gray-500">Dicatat {m.at}</p><p className="mt-1 text-sm">{m.notes}</p></div>)}
        </div>
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Riwayat serah terima</h2>{receipts.length === 0 && <p className="text-gray-500">Belum ada penerimaan. Stok tujuan belum bertambah dari mutasi ini.</p>}
            {receipts.map(r => <div key={r.id} className="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900"><strong>Penerimaan #{r.id} · {r.received_at}</strong><p className="mt-2">Penerima barang: {r.physical_receiver_name}</p><p className="mt-1 text-sm">{afterReceipt ? 'Dicatat oleh akun' : 'Dikonfirmasi oleh akun'}: {r.received_by_name}</p>{r.items.map((i, idx) => <p key={idx} className="mt-2">{i.to_product_name}: +{i.destination_quantity} {i.to_unit_name}</p>)}<p className="mt-3 text-sm">Catatan: {r.notes}</p></div>)}
        </div>
    </div></Layout>;
}
