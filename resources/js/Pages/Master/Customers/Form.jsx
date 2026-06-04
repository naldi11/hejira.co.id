import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';

const route = window.route;

const VISIBILITY = [
    { key: 'visible_gudang', label: 'Gudang Tempua', icon: 'warehouse' },
    { key: 'visible_jihans', label: "Jihan's Food", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys Brownies', icon: 'cake' },
];

export default function CustomerForm({ customer = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const isEdit = !!customer;

    const { data, setData, post, put, processing, errors } = useForm({
        name: customer?.name ?? '',
        phone: customer?.phone ?? '',
        email: customer?.email ?? '',
        is_active: customer ? customer.is_active : true,
        province: customer?.province ?? '',
        city: customer?.city ?? '',
        district: customer?.district ?? '',
        address: customer?.address ?? '',
        notes: customer?.notes ?? '',
        visible_gudang: customer ? customer.visible_gudang : true,
        visible_jihans: customer ? customer.visible_jihans : true,
        visible_hendhys: customer ? customer.visible_hendhys : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'customers.update', customer.id)) : post(route(routePrefix + 'customers.store'));
    };

    const field = 'w-full rounded-2xl border-2 border-slate-100 bg-slate-50 px-5 py-3.5 text-sm font-bold text-slate-900 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10';
    const label = 'mb-2 ml-1 block text-xs font-black uppercase tracking-widest text-slate-500';

    return (
        <Layout title={isEdit ? 'Edit Customer' : 'Tambah Customer'} pageTitle="Master Data — Customer">
            <Head title={isEdit ? 'Edit Customer' : 'Tambah Customer'} />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-8 pb-12">
                <div className="overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 bg-slate-50 px-10 py-8">
                        <h3 className="font-headline text-lg font-black uppercase tracking-widest text-slate-900">Informasi Customer</h3>
                        <p className="mt-1 text-xs font-bold uppercase tracking-tighter text-slate-400">Profil dan klasifikasi pelanggan</p>
                    </div>
                    <div className="space-y-8 p-10">
                        <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <div className="md:col-span-2"><label className={label}>Nama Lengkap / Instansi <span className="text-rose-500">*</span></label><input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Masukkan nama customer..." className={field} />{errors.name && <p className="ml-1 mt-2 text-[10px] font-black uppercase text-rose-500">{errors.name}</p>}</div>
                            <div><label className={label}>Nomor Telepon</label><input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className={field} /></div>
                            <div><label className={label}>Alamat Email</label><input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />{errors.email && <p className="ml-1 mt-2 text-[10px] font-black uppercase text-rose-500">{errors.email}</p>}</div>
                            <div><label className={label}>Status Akun</label><select value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')} className={`${field} cursor-pointer`}><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                            <div><label className={label}>Provinsi</label><input type="text" value={data.province} onChange={(e) => setData('province', e.target.value)} className={field} /></div>
                            <div><label className={label}>Kabupaten / Kota</label><input type="text" value={data.city} onChange={(e) => setData('city', e.target.value)} className={field} /></div>
                            <div><label className={label}>Kecamatan</label><input type="text" value={data.district} onChange={(e) => setData('district', e.target.value)} className={field} /></div>
                            <div className="md:col-span-2"><label className={label}>Alamat Lengkap</label><textarea rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} className={`${field} resize-none font-medium`} /></div>
                            <div className="md:col-span-2"><label className={label}>Catatan Tambahan</label><input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={field} /></div>
                        </div>

                        <div className="border-t border-slate-100 pt-10">
                            <label className={`${label} mb-6`}>Tampilkan di Entitas</label>
                            <div className="flex flex-wrap gap-6">
                                {VISIBILITY.map((v) => {
                                    const on = data[v.key];
                                    return (
                                        <button type="button" key={v.key} onClick={() => setData(v.key, !on)}
                                            className={`flex min-w-[200px] flex-1 cursor-pointer flex-col items-center justify-center rounded-[2rem] border-2 p-6 transition-all ${on ? 'border-indigo-600 bg-indigo-50 text-indigo-600' : 'border-slate-100 bg-slate-50 text-slate-400 hover:border-slate-200'}`}>
                                            <Icon name={v.icon} filled={on} className="mb-3 text-[32px]" />
                                            <span className="text-xs font-black uppercase tracking-widest">{v.label}</span>
                                            <div className={`mt-4 flex h-6 w-6 items-center justify-center rounded-full border-2 transition-all ${on ? 'border-indigo-600 bg-indigo-600' : 'border-slate-200'}`}>
                                                {on && <Icon name="check" className="text-[16px] font-black text-white" />}
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <button type="submit" disabled={processing} className="flex flex-1 items-center justify-center gap-3 rounded-[2rem] bg-indigo-600 px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700 disabled:opacity-50">
                        <Icon name={isEdit ? 'save' : 'person_add'} /> {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Daftarkan Customer'}
                    </button>
                    <Link href={route(routePrefix + 'customers.index')} className="flex items-center justify-center gap-3 rounded-[2rem] border-2 border-slate-200 bg-white px-10 py-4 text-xs font-black uppercase tracking-[0.2em] text-slate-500 transition-all hover:bg-slate-50">
                        <Icon name="arrow_back" /> Batal
                    </Link>
                </div>
            </form>
        </Layout>
    );
}
