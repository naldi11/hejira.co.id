import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';

const route = window.route;

const ENTITIES = [
    { value: 'gudang', label: 'Gudang Utama' },
    { value: 'jihans', label: "Jihan's Food" },
    { value: 'hendhys', label: 'Hendhys Brownies' },
    { value: 'owner', label: 'Pemilik / Owner' },
];

export default function UserForm({ branches, roles, user = null }) {
    const isEdit = !!user;

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        entity: user?.entity ?? 'gudang',
        branch_id: user?.branch_id ?? '',
        role: user?.role ?? '',
        is_active: user ? user.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route('master.users.update', user.id)) : post(route('master.users.store'));
    };

    const field = 'w-full rounded-2xl border-2 border-slate-100 bg-slate-50 px-5 py-3.5 text-sm font-bold text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10';
    const label = 'mb-2 ml-1 block text-xs font-black uppercase tracking-widest text-slate-500';
    const err = (k) => errors[k] && <p className="ml-2 mt-1 text-[10px] font-bold uppercase text-rose-500">{errors[k]}</p>;

    return (
        <GudangLayout title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} pageTitle="Manajemen Akses">
            <Head title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} />

            <div className="mx-auto max-w-4xl space-y-8 pb-20">
                <div className="flex items-center justify-between">
                    <Link href={route('master.users.index')} className="group inline-flex items-center gap-2 font-bold text-slate-500 transition-colors hover:text-slate-900">
                        <Icon name="arrow_back" className="text-[20px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                    </Link>
                    <h2 className="font-headline text-xl font-black tracking-tight text-slate-800">{isEdit ? 'Ubah Profil Pengguna' : 'Registrasi User Baru'}</h2>
                </div>

                <form onSubmit={submit} className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div className="space-y-8 lg:col-span-2">
                        <div className="space-y-8 rounded-[2.5rem] border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
                            <div className="mb-4 flex items-center gap-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-600 shadow-inner"><Icon name="badge" className="text-[28px]" /></div>
                                <div>
                                    <h3 className="font-headline text-lg font-black tracking-tight text-slate-900">Informasi Dasar</h3>
                                    <p className="mt-1 text-xs font-bold uppercase tracking-widest text-slate-500">Identitas dan kredensial login</p>
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div className="space-y-2"><label className={label}>Nama Lengkap <span className="text-rose-500">*</span></label><input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: Ahmad Suherman" className={field} />{err('name')}</div>
                                <div className="space-y-2"><label className={label}>Alamat Email <span className="text-rose-500">*</span></label><input type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="email@perusahaan.com" className={field} />{err('email')}</div>
                                <div className="grid grid-cols-1 gap-6 border-t border-slate-100 pt-4 md:grid-cols-2">
                                    <div className="space-y-2"><label className={label}>Password {isEdit ? '(Kosongkan jika tetap)' : '*'}</label><input type="password" required={!isEdit} value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />{err('password')}</div>
                                    <div className="space-y-2"><label className={label}>Konfirmasi Password</label><input type="password" required={!isEdit} value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={field} /></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" disabled={processing} className="w-full rounded-3xl bg-slate-900 py-5 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/10 transition-all hover:bg-indigo-600 disabled:opacity-50">
                            {processing ? 'Menyimpan...' : isEdit ? 'Update Data Pengguna' : 'Daftarkan Pengguna'}
                        </button>
                    </div>

                    <div className="space-y-8">
                        <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                            <div className="space-y-2">
                                <label className={label}>Entitas Bisnis <span className="text-rose-500">*</span></label>
                                <select required value={data.entity} onChange={(e) => setData('entity', e.target.value)} className={field}>
                                    {ENTITIES.map((e) => <option key={e.value} value={e.value}>{e.label}</option>)}
                                </select>
                                {err('entity')}
                            </div>
                            <div className="space-y-2">
                                <label className={label}>Cabang Penempatan</label>
                                <select value={data.branch_id ?? ''} onChange={(e) => setData('branch_id', e.target.value)} className={field}>
                                    <option value="">Tidak Terikat Cabang</option>
                                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <label className={label}>Hak Akses (Role) <span className="text-rose-500">*</span></label>
                                <div className="grid grid-cols-1 gap-2">
                                    {roles.map((r) => (
                                        <label key={r} className="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3 transition-all hover:bg-slate-100">
                                            <input type="radio" name="role" value={r} checked={data.role === r} onChange={(e) => setData('role', e.target.value)} className="h-5 w-5 border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                            <span className="text-xs font-bold uppercase tracking-tight text-slate-700">{r}</span>
                                        </label>
                                    ))}
                                </div>
                                {err('role')}
                            </div>
                            <label className="flex cursor-pointer items-center gap-3 px-2 pt-4">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-lg border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                <span className="text-xs font-black uppercase tracking-widest text-slate-500">Akses Aktif</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </GudangLayout>
    );
}
