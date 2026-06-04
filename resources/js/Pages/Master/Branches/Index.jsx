import { Head, Link, router } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';

const route = window.route;

export default function BranchesIndex({ branches }) {
    const destroy = (branch) => {
        if (window.confirm(`Hapus cabang ${branch.name}?`)) {
            router.delete(route('master.branches.destroy', branch.id), { preserveScroll: true });
        }
    };

    return (
        <GudangLayout title="Cabang" pageTitle="Master Data — Cabang">
            <Head title="Cabang" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Data Cabang</h2>
                        <p className="text-sm font-medium text-slate-500">Manajemen unit bisnis dan outlet resmi.</p>
                    </div>
                    <Link href={route('master.branches.create')} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="add" className="text-[20px]" /> Tambah Cabang
                    </Link>
                </div>

                <div className="max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total: <strong className="tabular-nums text-slate-900">{branches.meta?.total ?? branches.data.length}</strong> Cabang</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Identitas</th>
                                    <th className="px-6 py-4">Tipe</th>
                                    <th className="px-6 py-4">Kontak</th>
                                    <th className="px-6 py-4 text-center">User</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {branches.data.length === 0 ? <EmptyState colSpan={6} icon="storefront" message="Belum ada data cabang." />
                                    : branches.data.map((branch) => (
                                        <tr key={branch.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col">
                                                    <span className="text-sm font-black tracking-tight text-slate-800 transition-colors group-hover:text-indigo-600">{branch.name}</span>
                                                    <span className="mt-0.5 font-mono text-[10px] font-bold uppercase tracking-widest text-slate-400">{branch.code}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex rounded-xl border px-3 py-1 text-[9px] font-black uppercase tracking-widest ${branch.type === 'pusat' ? 'border-amber-100 bg-amber-50 text-amber-600' : 'border-blue-100 bg-blue-50 text-blue-600'}`}>{branch.type}</span>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-bold text-slate-500">{branch.phone ?? '-'}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-[11px] font-black tabular-nums text-slate-600">{branch.users_count}</span>
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex rounded-xl border px-3 py-1 text-[9px] font-black uppercase tracking-widest ${branch.is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-rose-100 bg-rose-50 text-rose-600'}`}>{branch.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={route('master.branches.edit', branch.id)} className="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-indigo-50 hover:text-indigo-600"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(branch)} className="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                    {branches.meta?.links && <div className="border-t border-slate-100 p-6"><Pagination links={branches.meta.links} /></div>}
                </div>
            </div>
        </GudangLayout>
    );
}
