import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const ENTITIES = [
    { value: 'gudang', label: 'Gudang Utama' },
    { value: 'jihans', label: "Jihan's Food" },
    { value: 'hendhys', label: 'Hendhys Brownies' },
    { value: 'owner', label: 'Pemilik / Owner' },
];

export default function UserForm({ branches, roles, user = null, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const u = user?.data ?? user;
    const isEdit = !!(u && u.id);

    const { data, setData, post, put, processing, errors } = useForm({
        name: u?.name ?? '',
        email: u?.email ?? '',
        password: '',
        password_confirmation: '',
        entity: u?.entity ?? 'gudang',
        branch_id: u?.branch_id ?? '',
        role: u?.role ?? '',
        is_active: u ? u.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'users.update', u.id)) : post(route(routePrefix + 'users.store'));
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} pageTitle="Manajemen Akses">
            <Head title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} />

            <div className="w-full space-y-6 pb-20">

                <form onSubmit={submit} className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {/* Basic Info Card */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Informasi Dasar Kredensial</h3>
                            </div>
                            <div className="space-y-5 p-6">
                                <div>
                                    <label className={labelClass}>Nama Lengkap <span className="text-rose-500">*</span></label>
                                    <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: Ahmad Suherman" className={inputClass} />
                                    {err('name')}
                                </div>
                                <div>
                                    <label className={labelClass}>Alamat Email <span className="text-rose-500">*</span></label>
                                    <input type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="email@perusahaan.com" className={inputClass} />
                                    {err('email')}
                                </div>
                                <div className="grid grid-cols-1 gap-5 border-t border-gray-150 pt-5 dark:border-gray-800 sm:grid-cols-2">
                                    <div>
                                        <label className={labelClass}>Password {isEdit ? '(Kosongkan jika tetap)' : '*'}</label>
                                        <input type="password" required={!isEdit} value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="••••••••" className={inputClass} />
                                        {err('password')}
                                    </div>
                                    <div>
                                        <label className={labelClass}>Konfirmasi Password</label>
                                        <input type="password" required={!isEdit} value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} placeholder="••••••••" className={inputClass} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <Link href={route(routePrefix + 'users.index')}>
                                <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                                    Kembali ke Daftar
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Daftarkan Pengguna'}
                            </Button>
                        </div>
                    </div>

                    {/* Sidebar Configurations */}
                    <div className="space-y-6">
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Hak Akses & Penempatan</h3>
                            </div>
                            <div className="space-y-5 p-6">
                                <div>
                                    <label className={labelClass}>Entitas Bisnis <span className="text-rose-500">*</span></label>
                                    <select required value={data.entity} onChange={(e) => setData('entity', e.target.value)} className={inputClass}>
                                        {ENTITIES.map((e) => <option key={e.value} value={e.value}>{e.label}</option>)}
                                    </select>
                                    {err('entity')}
                                </div>

                                <div>
                                    <label className={labelClass}>Cabang Penempatan</label>
                                    <select value={data.branch_id ?? ''} onChange={(e) => setData('branch_id', e.target.value)} className={inputClass}>
                                        <option value="">Tidak Terikat Cabang</option>
                                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                    </select>
                                    {err('branch_id')}
                                </div>

                                <div>
                                    <label className={labelClass}>Hak Akses (Role) <span className="text-rose-500">*</span></label>
                                    <div className="grid grid-cols-1 gap-2">
                                        {roles.map((r) => (
                                            <label key={r} className="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/50 p-3 transition-colors hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900/30 dark:hover:bg-gray-800">
                                                <input type="radio" name="role" value={r} checked={data.role === r} onChange={(e) => setData('role', e.target.value)} className="h-5 w-5 border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900/50" />
                                                <span className="text-xs font-bold uppercase tracking-wider text-gray-750 dark:text-gray-300">{r}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {err('role')}
                                </div>

                                <div className="border-t border-gray-150 pt-4 dark:border-gray-800">
                                    <label className="inline-flex cursor-pointer items-center gap-3">
                                        <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-md border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900/50" />
                                        <span className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Akses Pengguna Aktif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </Layout>
    );
}

