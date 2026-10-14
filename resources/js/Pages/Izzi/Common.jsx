import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';
export { panel, input, button, Errors } from '../StockOpname/Layout';
export const money = n => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
export const status = { completed: 'Selesai', part_cancelled: 'Sebagian dibatalkan', cancelled: 'Dibatalkan' };
const route = window.route;
export function Navigation({ branch, branches = [], canManage = false }) {
    const { auth } = usePage().props;
    const params = branch ? { branch_id: branch.id } : {};
    return <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-white p-4 dark:bg-gray-900 dark:border-gray-700">
        {branch?.is_active === false && <p className="w-full text-amber-700">Cabang nonaktif. Riwayat tetap dapat dilihat; stok tidak dapat ditambah atau dijual.</p>}
        {branches.length > 0 ? <label>Cabang Izzi: <select aria-label="Cabang Izzi" value={branch?.id || ''} onChange={e => router.get(window.location.pathname, { branch_id: e.target.value })} className="ml-2 rounded-lg dark:bg-gray-800">{branches.map(b => <option key={b.id} value={b.id}>{b.name}{!b.is_active ? ' (nonaktif)' : ''}</option>)}</select></label> : <strong>{branch?.name || 'Izzi Kebab'}</strong>}
        <Link className="text-brand-500" href={route('izzi.dashboard', params)}>Ringkasan</Link>
        <Link className="text-brand-500" href={route('izzi.stock', params)}>Stok bahan</Link>
        <Link className="text-brand-500" href={route('izzi.sales', params)}>Penjualan</Link>
        <Link className="text-brand-500" href={route('izzi.movements', params)}>Pergerakan bahan</Link>
        <Link className="text-brand-500" href={route('izzi.shifts', params)}>Laci</Link>
        {auth.user.roles.includes('kasir_izzi') && <Link className="text-brand-500" href={route('izzi.pos')}>Kasir</Link>}
        {canManage && <><Link className="text-brand-500" href={route('izzi.ingredients')}>Bahan</Link><Link className="text-brand-500" href={route('izzi.menus')}>Menu & resep</Link>{branch?.is_active !== false && <Link className="text-brand-500" href={route('izzi.receipts.create', params)}>Terima bahan</Link>}</>}
    </div>;
}
export function Search({ filters = {}, path, branch, dates = false }) {
    const [form, setForm] = useState({ search: '', per_page: '20', date_from: '', date_to: '', ...filters });
    return <form className="flex flex-wrap gap-3" onSubmit={e => { e.preventDefault(); router.get(route(path), { ...form, ...(branch ? { branch_id: branch.id } : {}) }); }}>
        <input aria-label="Cari data" type="search" placeholder="Cari data di seluruh riwayat…" maxLength={100} value={form.search} onChange={e => setForm({ ...form, search: e.target.value })} className="flex-1 rounded-lg dark:bg-gray-800" />
        {dates && <><input aria-label="Tanggal mulai" type="date" value={form.date_from} onChange={e => setForm({ ...form, date_from: e.target.value })} className="rounded-lg dark:bg-gray-800" /><input aria-label="Tanggal akhir" type="date" value={form.date_to} onChange={e => setForm({ ...form, date_to: e.target.value })} className="rounded-lg dark:bg-gray-800" /></>}
        <select aria-label="Jumlah per halaman" value={form.per_page} onChange={e => setForm({ ...form, per_page: e.target.value })} className="rounded-lg dark:bg-gray-800">{[['20','20'],['50','50'],['100','100'],['all','Semua']].map(([v,l]) => <option key={v} value={v}>{l}</option>)}</select>
        <button className="rounded-lg bg-brand-500 px-4 py-2 text-white">Cari</button>
    </form>;
}
export function Paging({ rows }) { return <div className="space-y-3"><p className="text-sm text-gray-500">Menampilkan {rows.from || 0}–{rows.to || 0} dari {rows.total} data.</p><Pagination links={rows.links} /></div>; }
