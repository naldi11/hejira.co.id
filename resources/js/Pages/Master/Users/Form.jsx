import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import SelectField from '@/Components/SelectField';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Button from '@/Components/ui/button/Button';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
const route = window.route;
const branchTypes = { pusat: 'Pusat', cabang: 'Cabang', gudang: 'Gudang' };
const cardClass = 'rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]';
const labelClass = 'mb-2 block text-sm font-medium text-gray-800 dark:text-gray-200';
const hintClass = 'mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400';

function AccessDetails({ role, entity }) {
    const can = [...role.can, ...(entity === 'all' ? role.all_unit_can ?? [] : [])];
    const cannot = [...role.cannot, ...(entity === 'gudang' ? role.gudang_only_cannot ?? [] : [])];

    return (
        <div className="space-y-5 text-sm">
            <div>
                <h4 className="font-semibold text-emerald-700 dark:text-emerald-400">Bisa dilakukan</h4>
                <ul className="mt-2 list-disc space-y-2 pl-5 leading-relaxed text-gray-600 dark:text-gray-300">
                    {can.map((item) => <li key={item}>{item}</li>)}
                </ul>
            </div>
            {role.centre_only && (
                <div className="rounded-xl bg-amber-50 p-3 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                    <h4 className="font-semibold">Hanya saat berada di pusat Hendhys</h4>
                    {role.centre_only.map((item) => <p key={item} className="mt-1 leading-relaxed">{item}</p>)}
                </div>
            )}
            <div>
                <h4 className="font-semibold text-rose-700 dark:text-rose-400">Tidak bisa dilakukan</h4>
                <ul className="mt-2 list-disc space-y-2 pl-5 leading-relaxed text-gray-600 dark:text-gray-300">
                    {cannot.map((item) => <li key={item}>{item}</li>)}
                </ul>
            </div>
        </div>
    );
}

export default function UserForm({ branches = [], roles = [], entity_roles = {}, unit_options = [], role_descriptions = {}, user = null, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const u = user?.data ?? user;
    const isEdit = !!u?.id;
    const isOwner = u?.role === 'owner';
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, put, processing, errors, clearErrors } = useForm({
        name: u?.name ?? '',
        email: u?.email ?? '',
        password: '',
        password_confirmation: '',
        entity: u?.entity ?? '',
        branch_id: ['hendhys', 'jihans', 'izzi'].includes(u?.entity) ? u?.branch_id ?? '' : '',
        role: u?.role ?? '',
        is_active: u ? u.is_active : true,
    });

    const unit = unit_options.find((option) => option.value === data.entity);
    const role = role_descriptions[data.role];
    const needsBranch = !isOwner && ['hendhys', 'jihans', 'izzi'].includes(data.entity);
    const filteredBranches = branches.filter((branch) => branch.entity === data.entity);
    const selectedBranch = filteredBranches.find((branch) => String(branch.id) === String(data.branch_id));
    const invalidBranch = needsBranch && !!data.branch_id && (!selectedBranch || !selectedBranch.is_active);
    const allowedRoles = entity_roles[data.entity] ?? [];
    const filteredRoles = allowedRoles.filter((name) => roles.includes(name) && role_descriptions[name]);
    const incomplete = !data.entity || !role || (!isOwner && !filteredRoles.includes(data.role)) || invalidBranch;

    const submit = (event) => {
        event.preventDefault();
        if (incomplete) return;
        isEdit ? put(route(routePrefix + 'users.update', u.id)) : post(route(routePrefix + 'users.store'));
    };
    const changeUnit = (entity) => {
        setData((current) => ({ ...current, entity, role: '', branch_id: '' }));
        clearErrors('entity', 'role', 'branch_id');
    };
    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const err = (key) => errors[key] && <p id={`${key}-error`} className="mt-2 text-sm text-rose-600 dark:text-rose-400" role="alert">{errors[key]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} pageTitle="Manajemen Pengguna">
            <Head title={isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'} />
            <div className="space-y-6 pb-10">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{isEdit ? 'Edit akun pengguna' : 'Buat akun pengguna'}</h1>
                    <p className={hintClass}>Isi data untuk masuk, pilih unit usaha, lalu tentukan pekerjaan pengguna. Penjelasan hak akses akan membantu Anda memilih peran.</p>
                    <p className="mt-2 text-xs text-gray-500">Kolom bertanda <span className="text-rose-500">*</span> wajib diisi.</p>
                </div>
                <div className={`${cardClass} sticky top-40 z-20 flex flex-wrap items-center justify-between gap-3 p-4 lg:top-24`} aria-label="Tombol simpan akun">
                    <div className="text-sm text-gray-600 dark:text-gray-300">
                        <p>{isEdit ? 'Simpan setelah selesai mengubah data akun.' : 'Simpan setelah data akun dan hak akses lengkap.'}</p>
                        {incomplete && <p className="mt-1 text-amber-700 dark:text-amber-400">{invalidBranch ? 'Perbaiki pilihan cabang untuk mengaktifkan tombol simpan.' : 'Pilih unit usaha dan peran yang sesuai untuk mengaktifkan tombol simpan.'}</p>}
                        {Object.keys(errors).length > 0 && <p role="alert" className="mt-1 text-rose-600">Perubahan belum tersimpan. Periksa isian yang ditandai di bawah.</p>}
                    </div>
                    <div className="flex shrink-0 flex-wrap items-center gap-3">
                        <Link href={route(routePrefix + 'users.index')} className="rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Kembali ke daftar</Link>
                        <Button type="submit" form="user-account-form" disabled={processing || incomplete}>{processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Buat Akun'}</Button>
                    </div>
                </div>
                <form id="user-account-form" onSubmit={submit} className="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
                    <div className="space-y-6 xl:col-span-2">
                        <section className={`${cardClass} p-5 sm:p-6`} aria-labelledby="account-heading">
                            <h2 id="account-heading" className="text-lg font-semibold text-gray-900 dark:text-white">1. Data untuk masuk</h2>
                            <div className="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="user-name" className={labelClass}>Nama lengkap <span className="text-rose-500">*</span></label>
                                    <input id="user-name" name="name" autoComplete="name" maxLength={100} required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Contoh: Ahmad Suherman" className={inputClass} aria-invalid={!!errors.name} aria-describedby={errors.name ? 'name-error' : undefined} />
                                    {err('name')}
                                </div>
                                <div>
                                    <label htmlFor="user-email" className={labelClass}>Email untuk masuk <span className="text-rose-500">*</span></label>
                                    <input id="user-email" name="email" type="email" autoComplete="off" maxLength={100} required value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="nama@perusahaan.com" className={inputClass} aria-invalid={!!errors.email} aria-describedby="email-help email-error" />
                                    <p id="email-help" className={hintClass}>Gunakan email yang berbeda untuk setiap pengguna.</p>
                                    {err('email')}
                                </div>
                                <div>
                                    <label htmlFor="user-password" className={labelClass}>{isEdit ? 'Kata sandi baru' : 'Kata sandi'} {!isEdit && <span className="text-rose-500">*</span>}</label>
                                    <input id="user-password" name="password" type={showPassword ? 'text' : 'password'} autoComplete="new-password" minLength={8} required={!isEdit} value={data.password} onChange={(e) => setData('password', e.target.value)} className={inputClass} aria-invalid={!!errors.password} aria-describedby="password-help password-error" />
                                    <p id="password-help" className={hintClass}>{isEdit ? 'Kosongkan jika tidak ingin mengganti kata sandi. Minimal 8 karakter jika diisi.' : 'Minimal 8 karakter. Pengguna memakai kata sandi ini untuk masuk.'}</p>
                                    {err('password')}
                                </div>
                                <div>
                                    <label htmlFor="user-password-confirm" className={labelClass}>Ulangi kata sandi {(!isEdit || data.password) && <span className="text-rose-500">*</span>}</label>
                                    <input id="user-password-confirm" name="password_confirmation" type={showPassword ? 'text' : 'password'} autoComplete="new-password" required={!isEdit || !!data.password} value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={inputClass} aria-describedby="confirmation-help" />
                                    <p id="confirmation-help" className={hintClass}>Ketik ulang kata sandi yang sama.</p>
                                    {err('password_confirmation')}
                                </div>
                            </div>
                            <label className="mt-4 inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <input type="checkbox" checked={showPassword} onChange={(e) => setShowPassword(e.target.checked)} className="h-4 w-4 rounded border-gray-300 text-brand-500" /> Tampilkan kata sandi
                            </label>
                        </section>

                        <section className={`${cardClass} p-5 sm:p-6`} aria-labelledby="access-heading">
                            <h2 id="access-heading" className="text-lg font-semibold text-gray-900 dark:text-white">2. Unit usaha dan pekerjaan</h2>
                            <div className="mt-5 space-y-6">
                                {isOwner ? (
                                    <p className="rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">Peran Pemilik (Owner) sudah ditetapkan oleh sistem dan tidak dapat diganti melalui form ini.</p>
                                ) : (
                                    <>
                                        <div>
                                            <label htmlFor="user-unit" className={labelClass}>Bekerja di unit mana? <span className="text-rose-500">*</span></label>
                                            <SelectField id="user-unit" name="entity" options={unit_options} value={data.entity} onChange={changeUnit} placeholder="Pilih unit usaha..." required aria-describedby="unit-help" aria-invalid={!!errors.entity} />
                                            <p id="unit-help" className={hintClass}>Pilih Hendhys atau Jihan’s untuk karyawan unit tersebut. “Semua unit usaha” untuk pengelola yang perlu administrasi lintas unit.</p>
                                            {err('entity')}
                                        </div>
                                        <fieldset>
                                            <legend className={labelClass}>Apa pekerjaan pengguna? <span className="text-rose-500">*</span></legend>
                                            {!data.entity ? <p className="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/50">Pilih unit usaha untuk melihat peran yang tersedia.</p> : filteredRoles.length === 0 ? <p role="alert" className="text-sm text-rose-600">Belum ada peran tersedia untuk unit ini. Hubungi pengelola sistem.</p> : (
                                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    {filteredRoles.map((name) => {
                                                        const choice = role_descriptions[name];
                                                        return (
                                                            <label key={name} className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${data.role === name ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-200 hover:border-brand-300 dark:border-gray-700'}`}>
                                                                <input type="radio" name="role" value={name} required checked={data.role === name} onChange={() => { setData('role', name); clearErrors('role'); }} className="mt-1 h-4 w-4 shrink-0 text-brand-500" />
                                                                <span><span className="block text-sm font-semibold text-gray-900 dark:text-white">{choice.label}</span><span className="mt-1 block text-sm leading-relaxed text-gray-500 dark:text-gray-400">{choice.description}</span></span>
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                            {err('role')}
                                            {role && <a href="#user-access" className="mt-3 inline-block text-sm font-medium text-brand-600 xl:hidden">Lihat apa yang bisa dan tidak bisa dilakukan ↓</a>}
                                        </fieldset>
                                    </>
                                )}
                                {needsBranch ? (
                                    <div>
                                        <label htmlFor="user-branch" className={labelClass}>Cabang awal <span className="font-normal text-gray-400">(opsional)</span></label>
                                        <SelectField id="user-branch" name="branch_id" options={filteredBranches.map((branch) => ({ value: branch.id, label: branch.name, sublabel: `${branchTypes[branch.type] ?? branch.type} · ${branch.code}${branch.is_active ? '' : ' · Tidak aktif'}`, isDisabled: !branch.is_active }))} value={data.branch_id} onChange={(value) => { setData('branch_id', value); clearErrors('branch_id'); }} placeholder="Belum ditentukan" aria-describedby="branch-help" aria-invalid={invalidBranch || !!errors.branch_id} />
                                        <p id="branch-help" className={hintClass}>Hanya cabang aktif milik {unit?.label} yang bisa dipilih. Pengguna dapat mengganti cabang aktif melalui menu pilihan cabang.</p>
                                        {!data.branch_id && <p className={hintClass}>{data.entity === 'hendhys' ? 'Jika dikosongkan, pengguna diminta memilih cabang sebelum masuk operasional Hendhys.' : 'Jika dikosongkan, cabang dapat dipilih kemudian melalui menu pilihan cabang.'}</p>}
                                        {data.entity === 'jihans' && <p className={hintClass}>Stok dan transaksi Jihan’s dikelola bersama dalam satu unit; pilihan cabang tidak membatasi data tersebut.</p>}
                                        {data.role === 'admin_hendhys' && <p className="mt-2 text-sm leading-relaxed text-amber-700 dark:text-amber-400">{selectedBranch?.type === 'cabang' ? 'Di cabang ini, admin tidak dapat mencatat produksi atau distribusi pusat. Fitur tersebut tersedia saat cabang aktif adalah pusat.' : 'Produksi dan distribusi dari pusat tersedia hanya saat cabang aktif adalah pusat Hendhys.'}</p>}
                                        {filteredBranches.filter((branch) => branch.is_active).length === 0 && <p className="mt-2 text-sm text-amber-700 dark:text-amber-400">Belum ada cabang aktif untuk unit ini. Tambahkan melalui Data Master → Cabang sebelum digunakan untuk operasional cabang.</p>}
                                        {invalidBranch && <p className="mt-2 text-sm text-rose-600" role="alert">Cabang lama tidak tersedia atau tidak aktif. Pilih cabang aktif atau kosongkan pilihan.</p>}
                                        {err('branch_id')}
                                    </div>
                                ) : data.entity && !isOwner && <p className="rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-600 dark:bg-gray-900/50 dark:text-gray-300">{data.entity === 'all' ? 'Akses administrasi mencakup Gudang Utama, Hendhys, Jihan’s, dan Izzi Kebab. Akun ini tidak ditempatkan pada satu cabang.' : 'Akses administrasi hanya untuk Gudang Utama dan data master. Akun ini tidak memerlukan cabang.'}</p>}
                                <div className="border-t border-gray-200 pt-5 dark:border-gray-800">
                                    <label className="flex cursor-pointer items-start gap-3">
                                        <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="mt-1 h-5 w-5 rounded border-gray-300 text-brand-500" />
                                        <span><span className="block text-sm font-medium text-gray-900 dark:text-white">Izinkan pengguna masuk</span><span className="mt-1 block text-sm text-gray-500 dark:text-gray-400">{data.is_active ? 'Akun aktif dan bisa digunakan setelah disimpan.' : 'Akun disimpan sebagai nonaktif dan tidak bisa digunakan untuk masuk.'}</span></span>
                                    </label>
                                    {err('is_active')}
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside id="user-access" className={`${cardClass} scroll-mt-24 p-5 sm:p-6 xl:sticky xl:top-6`} aria-label="Penjelasan hak akses">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Akses pengguna ini</h2>
                        {role ? (
                            <div className="mt-4 space-y-5" aria-live="polite">
                                <div className="border-b border-gray-200 pb-4 dark:border-gray-800">
                                    <p className="font-semibold text-brand-600 dark:text-brand-400">{role.label}</p>
                                    <p className="mt-1 text-sm text-gray-500">{isOwner ? 'Dashboard pemilik' : unit?.label}{needsBranch && selectedBranch ? ` · ${selectedBranch.name}` : ''}</p>
                                </div>
                                <AccessDetails role={role} entity={data.entity} />
                            </div>
                        ) : <p className={`${hintClass} mt-4`}>Pilih unit usaha dan peran. Kemampuan dan batasan pengguna akan muncul di sini sebelum akun dibuat.</p>}
                        <details className="mt-6 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <summary className="cursor-pointer text-sm font-medium text-brand-600 dark:text-brand-400">Bandingkan semua peran</summary>
                            <p className={hintClass}>Pilih peran sesuai pekerjaan. Admin mengelola operasional, sedangkan Kasir melayani penjualan POS.</p>
                            <div className="mt-4 space-y-3">
                                {Object.entries(role_descriptions).map(([name, description]) => (
                                    <details key={name} className="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                        <summary className="cursor-pointer text-sm font-semibold text-gray-800 dark:text-gray-200">{description.label}</summary>
                                        <p className="my-3 text-sm leading-relaxed text-gray-500">{description.description}</p>
                                        <AccessDetails role={description} entity="all" />
                                        {name === 'super_admin' && <p className="mt-3 text-sm leading-relaxed text-amber-700 dark:text-amber-400">Jika unit yang dipilih hanya Gudang Utama, operasional Hendhys dan Jihan’s tidak dapat diakses.</p>}
                                    </details>
                                ))}
                            </div>
                        </details>
                    </aside>
                    <div className={`${cardClass} flex flex-col gap-4 p-5 sm:p-6 xl:col-span-3 sm:flex-row sm:items-center sm:justify-between`}>
                        <div className="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                            <p className="font-semibold text-gray-900 dark:text-white">Periksa sebelum menyimpan</p>
                            <p className="mt-1">{role ? `${data.name.trim() || 'Pengguna baru'} akan menjadi ${role.label}${isOwner ? '' : ` di ${unit?.label ?? data.entity}`}${needsBranch ? `, ${selectedBranch ? `cabang awal ${selectedBranch.name}` : 'cabang belum ditentukan'}` : ''}. Akun ${data.is_active ? 'aktif' : 'nonaktif'}.` : 'Lengkapi data, lalu pilih unit usaha dan peran pengguna.'}</p>
                            {invalidBranch && <p className="mt-1 text-rose-600">Perbaiki pilihan cabang sebelum menyimpan.</p>}
                        </div>
                        <div className="flex shrink-0 flex-wrap items-center gap-3">
                            <Link href={route(routePrefix + 'users.index')} className="rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Kembali ke daftar</Link>
                            <Button type="submit" disabled={processing || incomplete}>{processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Buat Akun'}</Button>
                        </div>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
