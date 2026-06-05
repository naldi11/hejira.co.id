import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import { SkeletonTableRows } from '@/Components/Skeleton';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Button from '@/Components/ui/button/Button';

const Layouts = {
    GudangLayout,
    JihansLayout,
    HendhysLayout,
};

const route = window.route;

export default function KaryawanIndex({ karyawans, layout, routePrefix, currentScope }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(new URLSearchParams(window.location.search).get('search') || '');
    
    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route(routePrefix + 'karyawan.index'), { search }, { preserveState: true, replace: true, onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };

    const destroy = (id) => {
        if (confirm('Yakin ingin menghapus karyawan ini?')) {
            router.delete(route(routePrefix + 'karyawan.destroy', id));
        }
    };

    return (
        <Layout pageTitle="Data Karyawan">
            <Head title="Karyawan" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Master Karyawan</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Daftar staf dan karyawan terdaftar</p>
                    </div>
                    <Link href={route(routePrefix + 'karyawan.create')}>
                        <Button size="sm" startIcon={<Icon name="add" className="text-[18px]" />}>
                            TAMBAH KARYAWAN
                        </Button>
                    </Link>
                </div>
                
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <div className="border-b border-gray-150 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={handleSearch} className="flex max-w-md items-center gap-2">
                            <div className="relative flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 dark:text-gray-500" />
                                <input type="text" value={search} onChange={e => setSearch(e.target.value)} placeholder="Cari nama karyawan..."
                                    className="w-full h-11 rounded-lg border border-gray-300 bg-transparent pl-11 pr-4 text-sm text-gray-800 dark:text-white outline-hidden transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900/50" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 dark:bg-gray-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-widest transition-all hover:bg-gray-900 dark:hover:bg-gray-600">Cari</button>
                        </form>
                    </div>
                    
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4">Nama</th>
                                    <th className="px-6 py-4">Telepon</th>
                                    <th className="px-6 py-4">Scope</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {loading ? (
                                    <SkeletonTableRows rows={5} columns={5} />
                                ) : karyawans.data.length === 0 ? (
                                    <EmptyState colSpan={5} icon="badge" message="Belum ada data karyawan." />
                                ) : (
                                    karyawans.data.map(k => (
                                        <tr key={k.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">{k.name}</td>
                                            <td className="px-6 py-4 text-gray-650 dark:text-gray-400">{k.phone || '-'}</td>
                                            <td className="px-6 py-4 text-gray-650 dark:text-gray-400 capitalize">{k.entity_scope}</td>
                                            <td className="px-6 py-4 text-center"><StatusBadge status={k.is_active ? 'active' : 'inactive'} /></td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-3">
                                                    <Link href={route(routePrefix + 'karyawan.edit', k.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition-all hover:bg-indigo-50 hover:text-indigo-650 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-indigo-400"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(k.id)} className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition-all hover:bg-rose-50 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-750 dark:hover:text-rose-455"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {karyawans.links && <div className="border-t border-gray-100 p-4 dark:border-gray-800"><Pagination links={karyawans.links} /></div>}
                </div>
            </div>
        </Layout>
    );
}
