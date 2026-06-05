import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const ENTITY_CLASS = {
    gudang: 'bg-blue-50 text-blue-705 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-900/30',
    jihans: 'bg-orange-50 text-orange-705 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-900/30',
    hendhys: 'bg-amber-50 text-amber-705 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-900/30',
    owner: 'bg-purple-50 text-purple-705 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-900/30',
};

export default function UsersIndex({ users, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const destroy = (user) => {
        if (window.confirm(`Hapus pengguna ${user.name}?`)) {
            router.delete(route(routePrefix + 'users.destroy', user.id), { preserveScroll: true });
        }
    };

    return (
        <Layout title="Manajemen User" pageTitle="Keamanan & Akses">
            <Head title="Manajemen User" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Manajemen Pengguna</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Kelola hak akses, entitas bisnis, dan kredensial pengguna sistem.</p>
                    </div>
                    <Link href={route(routePrefix + 'users.create')}>
                        <Button size="sm" startIcon={<Icon name="person_add" className="text-[18px]" />}>
                            TAMBAH PENGGUNA
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <span className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Total: <strong className="font-mono text-gray-850 dark:text-white">{(users ?? []).length}</strong> Pengguna Terdaftar
                        </span>
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-4.5">Profil Pengguna</th>
                                    <th className="px-6 py-4.5">Penempatan</th>
                                    <th className="px-6 py-4.5">Level Akses</th>
                                    <th className="px-6 py-4.5 text-center">Status</th>
                                    <th className="px-6 py-4.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {(users ?? []).length === 0 ? (
                                    <EmptyState colSpan={5} icon="group" message="Belum ada pengguna." />
                                ) : (
                                    (users ?? []).map((user) => (
                                        <tr key={user.id} className="group transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800 font-bold text-brand-650 dark:text-brand-400">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="text-sm font-bold text-gray-800 dark:text-white/90">{user.name}</span>
                                                        <span className="max-w-[200px] truncate text-xs text-gray-500 dark:text-gray-400">{user.email}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex flex-col items-start gap-1">
                                                    <span className={`rounded-lg border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${ENTITY_CLASS[user.entity] ?? 'bg-gray-55 text-gray-700 border-gray-200'}`}>
                                                        {user.entity}
                                                    </span>
                                                    {user.branch && (
                                                        <span className="text-[10px] font-semibold italic text-gray-400 dark:text-gray-550">
                                                            @ {user.branch}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles.map((r) => (
                                                        <span key={r} className="rounded border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                                            {r}
                                                        </span>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4.5 text-center">
                                                <span className={`inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ${user.is_active ? 'border-emerald-250 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-900/30' : 'border-gray-200 bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'}`}>
                                                    <span className={`h-1.5 w-1.5 rounded-full ${user.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />
                                                    {user.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4.5">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'users.edit', user.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition hover:bg-white hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-brand-400">
                                                        <Icon name="edit" className="text-[18px]" />
                                                    </Link>
                                                    <button onClick={() => destroy(user)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition hover:bg-white hover:text-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-rose-455">
                                                        <Icon name="delete" className="text-[18px]" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
