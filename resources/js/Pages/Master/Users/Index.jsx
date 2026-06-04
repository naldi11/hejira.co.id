import { Head, Link, router } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';

const route = window.route;

const ENTITY_CLASS = {
    gudang: 'bg-blue-50 text-blue-700 border-blue-100',
    jihans: 'bg-orange-50 text-orange-700 border-orange-100',
    hendhys: 'bg-amber-50 text-amber-700 border-amber-100',
    owner: 'bg-violet-50 text-violet-700 border-violet-100',
};

export default function UsersIndex({ users }) {
    const destroy = (user) => {
        if (window.confirm(`Hapus pengguna ${user.name}?`)) {
            router.delete(route('master.users.destroy', user.id), { preserveScroll: true });
        }
    };

    return (
        <GudangLayout title="Manajemen User" pageTitle="Keamanan & Akses">
            <Head title="Manajemen User" />

            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="font-headline text-2xl font-black tracking-tight text-slate-800">Manajemen Pengguna</h2>
                        <p className="text-sm font-medium text-slate-500">Kelola hak akses, entitas bisnis, dan kredensial pengguna sistem.</p>
                    </div>
                    <Link href={route('master.users.create')} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                        <Icon name="person_add" className="text-[20px]" /> Tambah Pengguna
                    </Link>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 p-6">
                        <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total: <strong className="tabular-nums text-slate-900">{users.data.length}</strong> Pengguna Terdaftar</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th className="px-6 py-4">Profil Pengguna</th>
                                    <th className="px-6 py-4">Penempatan</th>
                                    <th className="px-6 py-4">Level Akses</th>
                                    <th className="px-6 py-4 text-center">Status</th>
                                    <th className="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {users.data.length === 0 ? <EmptyState colSpan={5} icon="group" message="Belum ada pengguna." />
                                    : users.data.map((user) => (
                                        <tr key={user.id} className="group transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 font-black text-indigo-600 shadow-inner transition-all group-hover:bg-indigo-600 group-hover:text-white">{user.name.charAt(0).toUpperCase()}</div>
                                                    <div className="flex flex-col">
                                                        <span className="text-sm font-black tracking-tight text-slate-800">{user.name}</span>
                                                        <span className="max-w-[150px] truncate text-[10px] font-bold text-slate-400">{user.email}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-col gap-1">
                                                    <span className={`w-fit rounded-lg border px-2 py-0.5 text-[9px] font-black uppercase ${ENTITY_CLASS[user.entity] ?? 'bg-slate-50 text-slate-700 border-slate-100'}`}>{user.entity}</span>
                                                    {user.branch && <span className="text-[10px] font-bold italic text-slate-400">@ {user.branch}</span>}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles.map((r) => <span key={r} className="rounded border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase text-slate-500">{r}</span>)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex rounded-xl border px-3 py-1 text-[9px] font-black uppercase tracking-widest ${user.is_active ? 'border-emerald-100 bg-emerald-50 text-emerald-600' : 'border-rose-100 bg-rose-50 text-rose-600'}`}>{user.is_active ? 'Aktif' : 'Off'}</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2 opacity-40 transition-opacity group-hover:opacity-100">
                                                    <Link href={route('master.users.edit', user.id)} className="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-indigo-50 hover:text-indigo-600"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button onClick={() => destroy(user)} className="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600"><Icon name="delete" className="text-[18px]" /></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </GudangLayout>
    );
}
