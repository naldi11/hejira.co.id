import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';

const route = window.route;

export default function BranchForm({ branch = null }) {
    const isEdit = !!branch;

    const { data, setData, post, put, processing, errors } = useForm({
        code: branch?.code ?? '',
        type: branch?.type ?? 'cabang',
        name: branch?.name ?? '',
        phone: branch?.phone ?? '',
        address: branch?.address ?? '',
        is_active: branch ? branch.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route('master.branches.update', branch.id)) : post(route('master.branches.store'));
    };

    const field = 'w-full rounded-2xl border-2 border-slate-100 bg-slate-50 px-5 py-3.5 text-sm font-bold text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10';
    const label = 'mb-2 ml-1 block text-xs font-black uppercase tracking-widest text-slate-500';

    return (
        <GudangLayout title={isEdit ? 'Edit Cabang' : 'Tambah Cabang'} pageTitle="Konfigurasi Cabang">
            <Head title={isEdit ? 'Edit Cabang' : 'Tambah Cabang'} />

            <div className="mx-auto max-w-4xl space-y-8 pb-20">
                <div className="flex items-center justify-between">
                    <Link href={route('master.branches.index')} className="group inline-flex items-center gap-2 font-bold text-slate-500 transition-colors hover:text-slate-900">
                        <Icon name="arrow_back" className="text-[20px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                    </Link>
                    <h2 className="font-headline text-xl font-black tracking-tight text-slate-800">{isEdit ? 'Edit Data Cabang' : 'Pendaftaran Cabang Baru'}</h2>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <form onSubmit={submit} className="space-y-8 rounded-[2.5rem] border border-slate-200 bg-white p-8 shadow-sm sm:p-10 lg:col-span-2">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className={label}>Kode Cabang <span className="text-rose-500">*</span></label>
                                <input type="text" required value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="cth: HND-CB3" className={field} />
                                {errors.code && <p className="ml-2 mt-1 text-[10px] font-bold uppercase text-rose-500">{errors.code}</p>}
                            </div>
                            <div className="space-y-2">
                                <label className={label}>Tipe Unit <span className="text-rose-500">*</span></label>
                                <select required value={data.type} onChange={(e) => setData('type', e.target.value)} className={field}>
                                    <option value="cabang">Outlet / Cabang</option>
                                    <option value="pusat">Kantor Pusat / Gudang</option>
                                </select>
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <label className={label}>Nama Cabang <span className="text-rose-500">*</span></label>
                                <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: Hendhys Brownies SM Raja" className={field} />
                                {errors.name && <p className="ml-2 mt-1 text-[10px] font-bold uppercase text-rose-500">{errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <label className={label}>Nomor Telepon</label>
                                <input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="0812xxxx" className={field} />
                            </div>
                            <div className="flex items-center px-2 pt-8">
                                <label className="group inline-flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span className="text-xs font-black uppercase tracking-widest text-slate-500">Status Aktif</span>
                                </label>
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <label className={label}>Alamat Lengkap</label>
                                <textarea rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Jl. Raya No. 123..." className={`${field} resize-none font-medium`} />
                            </div>
                        </div>

                        <button type="submit" disabled={processing} className="w-full rounded-2xl bg-slate-900 px-8 py-4 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/10 transition-all hover:bg-indigo-600 disabled:opacity-50">
                            {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Daftarkan Cabang'}
                        </button>
                    </form>

                    <div className="rounded-[2rem] bg-indigo-600 p-8 text-white shadow-xl shadow-indigo-600/20">
                        <Icon name="info" className="mb-4 text-[32px] text-indigo-300" />
                        <h3 className="mb-4 text-sm font-black uppercase tracking-[0.2em]">Informasi Tipe</h3>
                        <ul className="space-y-4">
                            <li className="flex gap-3"><span className="text-xs text-indigo-300">●</span><p className="text-xs font-medium leading-relaxed"><strong className="text-white">Pusat:</strong> Gudang Utama dan Kantor Administrasi.</p></li>
                            <li className="flex gap-3"><span className="text-xs text-indigo-300">●</span><p className="text-xs font-medium leading-relaxed"><strong className="text-white">Cabang:</strong> Outlet penjualan retail.</p></li>
                        </ul>
                    </div>
                </div>
            </div>
        </GudangLayout>
    );
}
