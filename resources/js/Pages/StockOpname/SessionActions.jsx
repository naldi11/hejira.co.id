import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { useOpnameActive } from '@/Components/StockOpnameAccess';
import { dateTimeLocalWib } from '@/lib/date';
import { input, button, Errors } from './Layout';
const route = window.route;

export function SessionState({ session }) {
    const active = useOpnameActive(session.active, session);
    return <strong className={active ? 'text-green-600' : 'text-gray-500'}>{session.status === 'closed' ? 'Ditutup Owner' : active ? 'Aktif' : 'Waktu habis — menunggu Owner'}</strong>;
}

export default function SessionActions({ session }) {
    const active = useOpnameActive(session.active, session);
    const [action, setAction] = useState(null);
    const later = new Date(Date.now() + 2 * 60 * 60 * 1000);
    const form = useForm({ request_key: crypto.randomUUID(), confirmed: false, notes: '', expires_at: dateTimeLocalWib(later) });
    const reopen = !active;
    return <div className="mt-4 space-y-3">
        <p className="text-sm text-gray-500">{reopen ? 'Lanjutkan sesi yang sama. Produk yang sudah dihitung tetap tersimpan; petugas melanjutkan produk yang belum dihitung.' : 'Sudah selesai menghitung? Tutup akses petugas sekarang. Hasil SO tetap tersimpan.'}</p>
        <div className="flex flex-wrap gap-3">
            {session.status !== 'closed' && <button type="button" className="rounded-lg bg-red-600 px-4 py-3 font-semibold text-white" onClick={() => { setAction('close'); form.setData('confirmed', false); }}>Tutup sesi SO</button>}
            {reopen && <button type="button" className={button} onClick={() => { setAction('reopen'); form.setData('confirmed', false); }}>Buka kembali & lanjutkan SO</button>}
        </div>
        {action && <form className="space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900" onSubmit={e => { e.preventDefault(); form.post(route(`owner.stock-opname.${action}`, session.id), { preserveScroll: true, onSuccess: () => { setAction(null); form.reset('confirmed', 'notes'); form.setData('request_key', crypto.randomUUID()); } }); }}>
            <h3 className="font-bold">{action === 'close' ? 'Konfirmasi tutup sesi' : 'Buka kembali sesi yang sama'}</h3><Errors errors={form.errors} />
            {action === 'reopen' && <><label className="block">Batas waktu akses baru (WIB)<input required className={`${input} mt-2`} type="datetime-local" value={form.data.expires_at} onChange={e => form.setData('expires_at', e.target.value)} /></label><label className="block">Alasan dibuka kembali<textarea required minLength={5} maxLength={1000} className={`${input} mt-2`} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} placeholder="Contoh: penghitungan belum selesai saat batas waktu habis." /></label></>}
            <label className="flex gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />{action === 'close' ? 'Saya menghentikan akses SO. Hasil yang sudah disimpan tetap berlaku.' : 'Saya memberi akses kembali untuk melanjutkan sesi ini.'}</label>
            <div className="flex gap-3"><button className={button} disabled={form.processing || !form.data.confirmed}>{form.processing ? 'Menyimpan…' : action === 'close' ? 'Ya, tutup akses SO' : 'Aktifkan kembali sesi'}</button><button type="button" disabled={form.processing} className="px-4 py-3" onClick={() => setAction(null)}>Batal</button></div>
        </form>}
    </div>;
}
