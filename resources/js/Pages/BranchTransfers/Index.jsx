import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
import Layout, { panel, input, button } from '../StockOpname/Layout';
const route = window.route;
export default function Index({ transfers, branches, filters, canCreate, isHistory }) {
    const defaults = { search: '', status: '', from_branch_id: '', to_branch_id: '', date_from: '', date_to: '', per_page:'20' };
    const [form, setForm] = useState({ ...defaults, ...Object.fromEntries(Object.entries(filters).map(([key, value]) => [key, value ?? ''])) });
    const set = (key, value) => setForm({ ...form, [key]: value });
    const indexRoute = isHistory ? 'branch-transfers.history' : 'branch-transfers.index';
    const title = isHistory ? 'Riwayat Mutasi Stok' : 'Mutasi Stok Cabang & Gudang';
    return <Layout pageTitle={title}><Head title={title} /><div className="mx-auto max-w-6xl space-y-6 text-gray-800 dark:text-gray-200">
        <div className="flex flex-wrap items-center justify-between gap-4"><div><h1 className="text-2xl font-bold">{title}</h1><p className="mt-2 text-gray-500">Mutasi baru dicatat setelah barang diterima. Status selesai otomatis; pengirim, penerima, dan perubahan stok tersimpan dalam riwayat.</p></div>{canCreate && <Link className={button} href={route('branch-transfers.create')}>Catat mutasi baru</Link>}</div>
        <nav aria-label="Tampilan mutasi" className="flex flex-wrap gap-3">
            <Link aria-current={!isHistory ? 'page' : undefined} className={`rounded-lg px-4 py-3 ${!isHistory ? 'bg-brand-500 text-white' : 'border border-gray-300 dark:border-gray-700'}`} href={route('branch-transfers.index')}>Daftar mutasi</Link>
            <Link aria-current={isHistory ? 'page' : undefined} className={`rounded-lg px-4 py-3 ${isHistory ? 'bg-brand-500 text-white' : 'border border-gray-300 dark:border-gray-700'}`} href={route('branch-transfers.history')}>Riwayat semua mutasi</Link>
        </nav>
        <form className={`${panel} grid gap-4 sm:grid-cols-2 lg:grid-cols-3`} onSubmit={e => { e.preventDefault(); router.get(route(indexRoute), form, { preserveState: true }); }}>
            <label className="text-sm sm:col-span-2 lg:col-span-3">Cari riwayat mutasi<input type="search" aria-label="Cari mutasi" maxLength={100} className={`${input} mt-2`} placeholder="Nomor, barang/kode, cabang, pengirim, penerima, pencatat, atau alasan" value={form.search} onChange={e => set('search', e.target.value)} /><span className="mt-2 block text-gray-500">Pencarian mencakup nama saat transaksi dicatat, catatan barang, penerimaan, dan alasan pembatalan.</span></label>
            <label className="text-sm">Status<select className={`${input} mt-2`} value={form.status} onChange={e => set('status', e.target.value)}><option value="">Semua status</option><option value="received">Selesai diterima</option><option value="cancelled">Dibatalkan</option><option value="sent">Dalam perjalanan (dokumen lama)</option><option value="partial">Diterima sebagian (dokumen lama)</option></select></label>
            <label className="text-sm">Cabang asal<select className={`${input} mt-2`} value={form.from_branch_id} onChange={e => set('from_branch_id', e.target.value)}><option value="">Semua cabang asal</option>{branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</select></label>
            <label className="text-sm">Lokasi tujuan<select className={`${input} mt-2`} value={form.to_branch_id} onChange={e => set('to_branch_id', e.target.value)}><option value="">Semua cabang tujuan</option>{branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</select></label>
            <label className="text-sm">Tanggal mutasi mulai<input type="date" className={`${input} mt-2`} value={form.date_from} onChange={e => set('date_from', e.target.value)} /></label>
            <label className="text-sm">Tanggal mutasi sampai<input type="date" min={form.date_from || undefined} className={`${input} mt-2`} value={form.date_to} onChange={e => set('date_to', e.target.value)} /></label>
            <label className="text-sm">Data per halaman<select className={`${input} mt-2`} value={form.per_page} onChange={e => set('per_page', e.target.value)}>{[['20','20'],['50','50'],['100','100'],['all','Semua']].map(([v,l]) => <option key={v} value={v}>{l}</option>)}</select></label><div className="flex items-end gap-3"><button className={button}>Cari mutasi</button><Link href={route(indexRoute)} className="py-3 text-brand-500">Reset</Link></div>
            <p className="text-sm text-gray-500 sm:col-span-2 lg:col-span-3">{isHistory ? 'Riwayat mencakup semua tanggal. Gunakan filter untuk menelusuri transaksi tertentu.' : 'Saat pertama dibuka, daftar menampilkan tanggal hari ini (WIB). Tanggal dapat diubah, atau buka Riwayat semua mutasi.'}</p>
        </form>
        <div className={`${panel} space-y-4`}>
            <div className="flex flex-wrap justify-between gap-2"><h2 className="font-bold">{transfers.total} mutasi ditemukan</h2><p className="text-sm text-gray-500">Diurutkan dari tanggal mutasi terbaru</p></div>
            {transfers.data.length === 0 && <p className="text-gray-500">Tidak ada mutasi sesuai pencarian dan akses cabang Anda. Coba ubah rentang tanggal atau kata pencarian.</p>}
            {transfers.data.map(t => <article key={t.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <div className="flex flex-wrap justify-between gap-2"><Link className="font-mono font-bold text-brand-500 underline" href={route('branch-transfers.show', t.id)}>{t.number}</Link><span className={`rounded-full px-3 py-1 text-sm font-semibold ${t.status === 'received' ? 'bg-green-50 text-green-700' : t.status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'}`}>{t.status_label}</span></div>
                <p className="mt-3 font-semibold">{t.from_branch_name} → {t.to_branch_name}</p>
                <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2"><div><dt className="text-gray-500">Tanggal mutasi</dt><dd>{t.date}</dd></div><div><dt className="text-gray-500">Pengirim barang</dt><dd>{t.physical_sender_name || t.sent_by_name}</dd></div><div><dt className="text-gray-500">Penerima barang</dt><dd>{t.receiver_names.length ? t.receiver_names.join(', ') : 'Belum diterima'}</dd></div><div><dt className="text-gray-500">Dicatat oleh</dt><dd>{t.sent_by_name} · {t.recorded_at}</dd></div></dl>
                <div className="mt-3 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800"><strong>Barang ({t.items.length} jenis)</strong>{t.items.slice(0, 4).map((i, idx) => <p key={idx} className="mt-1">{i.name} · {i.code} · {i.quantity} {i.unit}</p>)}{t.items.length > 4 && <p className="mt-1">+{t.items.length - 4} jenis lainnya di detail mutasi</p>}</div>
                <p className="mt-3 text-sm"><strong>Alasan:</strong> {t.reason}</p>{t.cancel_reason && <p className="mt-2 text-sm text-red-700">Pembatalan: {t.cancel_reason}</p>}
                <Link className="mt-3 inline-block text-sm font-semibold text-brand-500 underline" href={route('branch-transfers.show', t.id)}>Lihat barang, petugas, dan jejak perubahan stok →</Link>
            </article>)}<Pagination links={transfers.links} />
        </div>
    </div></Layout>;
}
