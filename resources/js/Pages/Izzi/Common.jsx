import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';

export { panel, input, button, Errors } from '../StockOpname/Layout';

export const uuid = () => {
    if (typeof window !== 'undefined' && window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
};

export const money = (n) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(n || 0);

export const status = {
    completed: 'Selesai',
    part_cancelled: 'Sebagian Dibatalkan',
    cancelled: 'Dibatalkan',
};

export const statusBadgeClass = {
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    part_cancelled: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
};

const route = window.route;

export function Navigation({ branch, branches = [] }) {
    if (!branches.length && branch?.is_active !== false) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            {branches.length > 0 && (
                <div className="flex items-center gap-2.5">
                    <Icon name="storefront" className="text-[20px] text-brand-500" />
                    <span className="text-sm font-medium text-gray-600 dark:text-gray-400">Cabang:</span>
                    <select
                        aria-label="Cabang Izzi"
                        value={branch?.id || ''}
                        onChange={(e) => router.get(window.location.pathname, { branch_id: e.target.value })}
                        className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm font-semibold text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        {branches.map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.name}
                                {!b.is_active ? ' (Nonaktif)' : ''}
                            </option>
                        ))}
                    </select>
                </div>
            )}

            {branch?.is_active === false && (
                <div className="flex items-center gap-2 text-xs font-medium text-amber-700 dark:text-amber-400">
                    <Icon name="warning" className="text-[18px]" />
                    <span>Cabang nonaktif (hanya lihat riwayat).</span>
                </div>
            )}
        </div>
    );
}

export function Search({ filters = {}, path, branch, dates = false, placeholder = 'Cari data…' }) {
    const [form, setForm] = useState({ search: '', per_page: '20', date_from: '', date_to: '', ...filters });
    const hasFilter = Boolean(form.search || form.date_from || form.date_to || (form.per_page && form.per_page !== '20'));

    return (
        <form
            className="flex flex-wrap items-center gap-3"
            onSubmit={(e) => {
                e.preventDefault();
                router.get(route(path), { ...form, ...(branch ? { branch_id: branch.id } : {}) });
            }}
        >
            <div className="relative min-w-[240px] flex-1">
                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400" />
                <input
                    aria-label="Cari data"
                    type="search"
                    placeholder={placeholder}
                    maxLength={100}
                    value={form.search}
                    onChange={(e) => setForm({ ...form, search: e.target.value })}
                    className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            {dates && (
                <div className="flex flex-wrap items-center gap-2">
                    <input
                        aria-label="Tanggal mulai"
                        type="date"
                        value={form.date_from}
                        onChange={(e) => setForm({ ...form, date_from: e.target.value })}
                        className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <span className="text-xs text-gray-400">s/d</span>
                    <input
                        aria-label="Tanggal akhir"
                        type="date"
                        value={form.date_to}
                        onChange={(e) => setForm({ ...form, date_to: e.target.value })}
                        className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>
            )}

            <select
                aria-label="Jumlah per halaman"
                value={form.per_page}
                onChange={(e) => setForm({ ...form, per_page: e.target.value })}
                className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option value="20">20 baris</option>
                <option value="50">50 baris</option>
                <option value="100">100 baris</option>
                <option value="all">Semua</option>
            </select>

            <button
                type="submit"
                className="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
            >
                Filter
            </button>

            {hasFilter && (
                <Link
                    href={route(path, branch ? { branch_id: branch.id } : {})}
                    className="rounded-lg bg-red-50 px-3.5 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400"
                >
                    Reset
                </Link>
            )}
        </form>
    );
}

export function Paging({ rows }) {
    if (!rows?.total) return null;
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
            <p className="text-xs text-gray-500 dark:text-gray-400">
                Menampilkan <strong>{rows.from || 0}–{rows.to || 0}</strong> dari <strong>{rows.total}</strong> data
            </p>
            <Pagination links={rows.links} />
        </div>
    );
}
