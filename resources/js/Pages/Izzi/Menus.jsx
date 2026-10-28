import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import { Search, Paging, money } from './Common';

const route = window.route;

export default function Menus({ menus, filters }) {
    return (
        <IzziLayout pageTitle="Daftar Menu & Resep Izzi">
            <Head title="Menu & Resep Izzi" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Daftar Menu & Resep Gramasi
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Kelola menu jualan kasir beserta takaran bahan baku per 1 porsi menu
                        </p>
                    </div>
                    <Link
                        href={route('izzi.menus.create')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                    >
                        <Icon name="add" className="text-[18px]" />
                        <span>Tambah Menu Baru</span>
                    </Link>
                </div>

                {/* Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <Search
                            path="izzi.menus"
                            filters={filters}
                            placeholder="Cari nama menu, kode, atau kategori…"
                        />
                    </div>

                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3.5">Menu</th>
                                    <th className="px-6 py-3.5">Kategori</th>
                                    <th className="px-6 py-3.5 text-right">Harga Jual</th>
                                    <th className="px-6 py-3.5 text-center">Komposisi Resep</th>
                                    <th className="px-6 py-3.5 text-center">Status</th>
                                    <th className="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {menus.data.length === 0 ? (
                                    <EmptyState
                                        colSpan={6}
                                        icon="restaurant_menu"
                                        message="Belum ada menu yang terdaftar."
                                    />
                                ) : (
                                    menus.data.map((m) => (
                                        <tr
                                            key={m.id}
                                            className="hover:bg-gray-50/60 dark:hover:bg-white/[0.01]"
                                        >
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-800 dark:text-white">{m.name}</p>
                                                <p className="font-mono text-xs text-gray-400">{m.code}</p>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                    {m.category}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right font-bold text-gray-900 tabular-nums dark:text-white">
                                                {money(m.price)}
                                            </td>
                                            <td className="px-6 py-4 text-center text-xs text-gray-500">
                                                <strong>{m.ingredient_count}</strong> bahan • v{m.recipe_version}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                        m.is_active
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                                    }`}
                                                >
                                                    {m.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                <Link
                                                    href={route('izzi.menus.edit', m.id)}
                                                    className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                >
                                                    <Icon name="edit" className="text-[15px]" />
                                                    <span>Edit Resep</span>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <Paging rows={menus} />
                </div>
            </div>
        </IzziLayout>
    );
}
