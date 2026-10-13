import { Head, Link, useForm } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import Layout, { panel, input, button, Errors } from './Layout';

const route = window.route;
function OpenForm({ locations }) {
    const form = useForm({ request_key: crypto.randomUUID(), location_key: '', expires_at: '', notes: '', confirmed: false });
    const chosen = locations.find(l => l.key === form.data.location_key);
    return <form className={`${panel} space-y-4`} onSubmit={e => { e.preventDefault(); form.post(route('owner.stock-opname.open')); }}>
        <h2 className="text-lg font-bold">Buka akses stock opname</h2>
        <p className="text-sm text-gray-500">Pilih satu lokasi dan batas waktu dalam WIB. Anda dapat membuka sesi terpisah untuk lokasi lain. Hanya owner yang dapat membuka dan menutup sesi.</p>
        <Errors errors={form.errors} />
        <label className="block">Lokasi yang diberi akses
            <select required className={`${input} mt-2`} value={form.data.location_key} onChange={e => form.setData('location_key', e.target.value)}>
                <option value="">Pilih gudang, pusat produksi, atau cabang</option>
                {locations.map(l => <option key={l.key} value={l.key}>{l.name}</option>)}
            </select>
        </label>
        {chosen && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{chosen.description}</p>}
        <label className="block">Akses berakhir pada (WIB)
            <input required type="datetime-local" className={`${input} mt-2`} value={form.data.expires_at} onChange={e => form.setData('expires_at', e.target.value)} />
        </label>
        <label className="block">Instruksi untuk petugas
            <textarea required minLength={5} maxLength={1000} className={`${input} mt-2`} placeholder="Contoh: hitung stok siap jual setelah toko tutup." value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} />
        </label>
        <label className="flex items-start gap-3"><input type="checkbox" checked={form.data.confirmed} onChange={e => form.setData('confirmed', e.target.checked)} />Saya memberi akses petugas lokasi ini untuk menyesuaikan saldo sesuai hasil hitung fisik selama sesi aktif.</label>
        <button className={button} disabled={form.processing || !form.data.confirmed}>Buka sesi untuk lokasi ini</button>
    </form>;
}
export default function Index({ locations, sessions, isOwner }) {
    return <Layout><Head title="Sesi Stock Opname" /><div className="mx-auto max-w-5xl space-y-6 text-gray-800 dark:text-gray-200">
        <div><h1 className="text-2xl font-bold">{isOwner ? 'Sesi Stock Opname' : 'Stock Opname'}</h1>
            <p className="mt-2 text-gray-500">{isOwner ? 'Atur akses hitung fisik dan lihat hasil opname setiap lokasi.' : 'Buka sesi aktif untuk mulai menghitung. Jika belum ada akses, minta owner membuka sesi untuk lokasi Anda.'}</p></div>
        <Link className="inline-block rounded-lg border border-brand-300 px-4 py-3 font-semibold text-brand-500" href={route('stock-opname.history')}>Lihat riwayat SO per produk dan petugas →</Link>
        {isOwner && <OpenForm locations={locations} />}
        {!isOwner && locations.length === 0 && <div className={panel}>Tidak ada lokasi yang sesuai akun Anda. Pilih cabang aktif yang sesuai penempatan Anda.</div>}
        <div className={`${panel} space-y-4`}><h2 className="text-lg font-bold">Sesi dan riwayat</h2>
            {sessions.data.length === 0 && <p className="text-gray-500">Belum ada sesi stock opname untuk lokasi ini.</p>}
            {sessions.data.map(s => <Link key={s.id} href={route('stock-opname.show', s.id)} className="block rounded-xl border border-gray-200 p-4 hover:border-brand-500 dark:border-gray-700">
                <div className="flex flex-wrap justify-between gap-2"><strong>#{s.id} · {s.location_name}</strong><span className={s.active ? 'text-green-600' : 'text-gray-500'}>{s.state_label}</span></div>
                <p className="mt-2 text-sm">Dibuka {s.opened_at} oleh {s.opened_by_name} · Batas waktu {s.expires_at}</p>
                <p className="mt-2 text-sm text-brand-500">{isOwner ? 'Lihat hasil dan kelola sesi →' : s.active ? 'Mulai hitung fisik →' : 'Lihat riwayat →'}</p>
            </Link>)}<Pagination links={sessions.links} />
        </div>
    </div></Layout>;
}
