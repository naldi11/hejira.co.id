import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import SelectField from '@/Components/SelectField';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';
import Button from '@/Components/ui/button/Button';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
const route = window.route;
const branchTypes = { pusat: 'Pusat', cabang: 'Cabang', gudang: 'Gudang' };
const unitClasses = {
    gudang: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-900/30',
    jihans: 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-900/30',
    hendhys: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-900/30',
    all: 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-900/30',
    owner: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-900/30',
};
const humanize = (value) => value ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : 'Belum ditentukan';
const labelClass = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300';

export default function UsersIndex({ users = [], filters = {}, totalUsers = 0, roleOptions = [], branchOptions = [], unit_options = [], role_descriptions = {}, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role ?? '');
    const [entity, setEntity] = useState(filters.entity ?? '');
    const [branchId, setBranchId] = useState(filters.branch_id ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [loading, setLoading] = useState(false);
    const unitLabel = (value) => unit_options.find((unit) => unit.value === value)?.label ?? humanize(value);

    const applyFilter = (override = {}) => {
        router.get(route(routePrefix + 'users.index'), {
            search: search || undefined,
            role: role || undefined,
            entity: entity || undefined,
            branch_id: branchId || undefined,
            status: status || undefined,
            ...override,
        }, {
            preserveState: true, preserveScroll: true, replace: true,
            onStart: () => setLoading(true), onFinish: () => setLoading(false),
        });
    };
    const changeUnitFilter = (value) => {
        const branch = branchOptions.find((option) => String(option.id) === String(branchId));
        const keepBranch = !value || branchId === 'none' || branch?.entity === value;
        setEntity(value);
        if (!keepBranch) setBranchId('');
        applyFilter({ entity: value || undefined, branch_id: keepBranch ? branchId || undefined : undefined });
    };
    const resetFilter = () => {
        setSearch(''); setRole(''); setEntity(''); setBranchId(''); setStatus('');
        router.get(route(routePrefix + 'users.index'), {}, { preserveScroll: true, replace: true });
    };
    const hasFilter = !!(filters.search || filters.role || filters.entity || filters.branch_id || filters.status);
    const branchChoices = [
        { value: 'none', label: 'Tanpa cabang awal', sublabel: 'Belum ditentukan atau tidak memerlukan cabang' },
        ...branchOptions.filter((branch) => !entity || branch.entity === entity).map((branch) => ({
            value: branch.id, label: branch.name,
            sublabel: `${unitLabel(branch.entity)} · ${branchTypes[branch.type] ?? humanize(branch.type)} · ${branch.code}${branch.is_active ? '' : ' · Nonaktif'}`,
        })),
    ];
    const destroy = (user) => {
        if (window.confirm(`Hapus akun ${user.name}? Pengguna ini tidak dapat masuk lagi setelah akunnya dihapus.`)) {
            router.delete(route(routePrefix + 'users.destroy', user.id), { preserveScroll: true });
        }
    };

    return (
        <Layout title="Manajemen Pengguna" pageTitle="Manajemen Pengguna">
            <Head title="Daftar Pengguna" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Daftar pengguna</h1>
                        <p className="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">Lihat pekerjaan pengguna, unit usaha, cabang awal, dan apakah akunnya bisa digunakan untuk masuk.</p>
                    </div>
                    <Link href={route(routePrefix + 'users.create')} className="shrink-0">
                        <Button size="sm" startIcon={<Icon name="person_add" className="text-[18px]" />}>Buat akun pengguna</Button>
                    </Link>
                </div>
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="space-y-4 border-b border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <form onSubmit={(event) => { event.preventDefault(); applyFilter(); }} className="space-y-4" aria-label="Cari dan saring pengguna">
                            <div>
                                <label htmlFor="users-search" className={labelClass}>Cari pengguna</label>
                                <div className="relative">
                                    <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                                    <input id="users-search" type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Ketik nama atau email..." className="h-11 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-4 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-white" />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label htmlFor="users-role" className={labelClass}>Peran pengguna</label>
                                    <SelectField id="users-role" options={roleOptions.map((name) => ({ value: name, label: role_descriptions[name]?.label ?? humanize(name) }))} value={role} onChange={(value) => { setRole(value); applyFilter({ role: value || undefined }); }} placeholder="Semua peran" />
                                </div>
                                <div>
                                    <label htmlFor="users-unit" className={labelClass}>Unit usaha</label>
                                    <SelectField id="users-unit" options={unit_options} value={entity} onChange={changeUnitFilter} placeholder="Semua pilihan unit" />
                                </div>
                                <div>
                                    <label htmlFor="users-branch" className={labelClass}>Cabang awal</label>
                                    <SelectField id="users-branch" options={branchChoices} value={branchId} onChange={(value) => { setBranchId(value); applyFilter({ branch_id: value || undefined }); }} placeholder="Semua cabang" />
                                </div>
                                <div>
                                    <label htmlFor="users-status" className={labelClass}>Status akun</label>
                                    <SelectField id="users-status" options={[{ value: 'aktif', label: 'Aktif — bisa masuk' }, { value: 'nonaktif', label: 'Nonaktif — tidak bisa masuk' }]} value={status} onChange={(value) => { setStatus(value); applyFilter({ status: value || undefined }); }} placeholder="Semua status" />
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-3">
                                <Button type="submit" size="sm" disabled={loading}>Cari pengguna</Button>
                                {hasFilter && <button type="button" onClick={resetFilter} className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300">Hapus filter</button>}
                                <p className="text-sm text-gray-500 dark:text-gray-400" role="status">{loading ? 'Memuat pengguna…' : hasFilter ? `Menampilkan ${users.length} dari ${totalUsers} pengguna` : `${totalUsers} pengguna terdaftar`}</p>
                            </div>
                        </form>
                        <p className="text-xs leading-relaxed text-gray-500 dark:text-gray-400">Peran menentukan pekerjaan dan hak akses. Cabang awal adalah penempatan akun; pengguna unit dapat memilih cabang aktif melalui menu cabang.</p>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto" role="region" aria-label="Tabel daftar pengguna" tabIndex={0}>
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-gray-200 bg-gray-50/50 text-xs font-semibold text-gray-600 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    {['Pengguna', 'Peran (hak akses)', 'Unit usaha', 'Cabang awal', 'Status akun', 'Tindakan'].map((heading) => <th key={heading} scope="col" className={`px-5 py-4 ${heading === 'Tindakan' ? 'text-right' : ''}`}>{heading}</th>)}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {users.length === 0 ? <EmptyState colSpan={6} icon="group" message={hasFilter ? 'Tidak ada pengguna yang cocok. Coba ubah atau hapus filter.' : 'Belum ada pengguna. Mulai dengan membuat akun pengguna.'} /> : users.map((user) => {
                                    const branch = user.branch_details;
                                    const needsBranch = ['hendhys', 'jihans'].includes(user.entity) && !user.roles.includes('owner');
                                    return (
                                        <tr key={user.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-5 py-5">
                                                <div className="flex min-w-[180px] items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400" aria-hidden="true">{user.name.charAt(0).toUpperCase()}</div>
                                                    <div><p className="text-sm font-semibold text-gray-900 dark:text-white">{user.name}</p><p className="mt-1 max-w-[200px] truncate text-xs text-gray-500 dark:text-gray-400" title={user.email}>{user.email}</p></div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-5">
                                                <div className="min-w-[180px] space-y-2">
                                                    {user.roles.length === 0 ? <span className="text-sm text-amber-700">Peran belum ditentukan</span> : user.roles.map((name) => <div key={name}><span className="inline-block rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">{role_descriptions[name]?.label ?? humanize(name)}</span><p className="mt-1 max-w-[240px] text-xs leading-relaxed text-gray-500 dark:text-gray-400">{role_descriptions[name]?.description}</p></div>)}
                                                </div>
                                            </td>
                                            <td className="px-5 py-5">
                                                <span className={`inline-block whitespace-nowrap rounded-lg border px-2.5 py-1 text-xs font-medium ${unitClasses[user.entity] ?? 'border-gray-200 bg-gray-50 text-gray-700'}`}>{unitLabel(user.entity)}</span>
                                            </td>
                                            <td className="px-5 py-5">
                                                <div className="min-w-[150px] text-sm text-gray-800 dark:text-gray-200">
                                                    {user.branch ? <><p className="font-medium">{user.branch}</p>{branch && <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{branchTypes[branch.type] ?? humanize(branch.type)} · {branch.code}</p>}{branch && !branch.is_active && <p className="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">Cabang nonaktif — perbarui penempatan</p>}</> : <><p>{needsBranch ? 'Belum ditentukan' : 'Tidak memerlukan cabang'}</p><p className="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{needsBranch ? 'Dapat dipilih melalui menu cabang' : user.roles.includes('owner') ? 'Pemantauan oleh pemilik' : user.entity === 'all' ? 'Administrasi lintas unit' : 'Administrasi Gudang Utama'}</p></>}
                                                </div>
                                            </td>
                                            <td className="px-5 py-5">
                                                <span className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ${user.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'}`}><span className={`h-1.5 w-1.5 rounded-full ${user.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} aria-hidden="true" />{user.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                                <p className="mt-1 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{user.is_active ? 'Bisa masuk' : 'Tidak bisa masuk'}</p>
                                            </td>
                                            <td className="px-5 py-5">
                                                {user.can_manage ? <div className="flex items-center justify-end gap-2">
                                                    <Link href={route(routePrefix + 'users.edit', user.id)} aria-label={`Edit akun ${user.name}`} title="Edit akun" className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400"><Icon name="edit" className="text-[18px]" /></Link>
                                                    <button type="button" onClick={() => destroy(user)} aria-label={`Hapus akun ${user.name}`} title="Hapus akun" className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-rose-50 hover:text-rose-600 dark:border-gray-700 dark:text-gray-400"><Icon name="delete" className="text-[18px]" /></button>
                                                </div> : <p className="text-right text-xs text-gray-400">Akun dilindungi</p>}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
