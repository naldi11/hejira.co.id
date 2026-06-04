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
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Master Karyawan</h2>
                    <Link href={route(routePrefix + 'karyawan.create')} className="inline-flex items-center gap-2 rounded-xl bg-gray-800 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-gray-900">
                        <Icon name="add" className="text-[20px]" /> Tambah Karyawan
                    </Link>
                </div>
                
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4">
                        <form onSubmit={handleSearch} className="flex max-w-md items-center gap-2">
                            <div className="relative flex-1">
                                <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                <input type="text" value={search} onChange={e => setSearch(e.target.value)} placeholder="Cari nama karyawan..." className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm" />
                            </div>
                            <button type="submit" className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Cari</button>
                        </form>
                    </div>
                    
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Nama</th>
                                    <th className="px-6 py-4 font-medium">Telepon</th>
                                    <th className="px-6 py-4 font-medium">Scope</th>
                                    <th className="px-6 py-4 text-center font-medium">Status</th>
                                    <th className="px-6 py-4 text-center font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {loading ? (
                                    <SkeletonTableRows rows={5} columns={5} />
                                ) : karyawans.data.length === 0 ? (
                                    <EmptyState colSpan={5} icon="badge" message="Belum ada data karyawan." />
                                ) : (
                                    karyawans.data.map(k => (
                                        <tr key={k.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 font-bold text-gray-800">{k.name}</td>
                                            <td className="px-6 py-4 text-gray-600">{k.phone || '-'}</td>
                                            <td className="px-6 py-4 text-gray-600 capitalize">{k.entity_scope}</td>
                                            <td className="px-6 py-4 text-center"><StatusBadge status={k.is_active ? 'active' : 'inactive'} /></td>
                                            <td className="px-6 py-4 text-center">
                                                <div className="flex items-center justify-center gap-3">
                                                    <Link href={route(routePrefix + 'karyawan.edit', k.id)} className="text-gray-400 transition-colors hover:text-blue-600"><Icon name="edit" className="text-[20px]" /></Link>
                                                    <button onClick={() => destroy(k.id)} className="text-gray-400 transition-colors hover:text-red-600"><Icon name="delete" className="text-[20px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {karyawans.links && <div className="border-t border-gray-100 p-4"><Pagination links={karyawans.links} /></div>}
                </div>
            </div>
        </Layout>
    );
}
