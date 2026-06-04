import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';

const route = window.route;

export default function SupplierForm({ supplier = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const isEdit = !!supplier;

    const { data, setData, post, put, processing, errors } = useForm({
        name: supplier?.name ?? '',
        contact_person: supplier?.contact_person ?? '',
        phone: supplier?.phone ?? '',
        email: supplier?.email ?? '',
        address: supplier?.address ?? '',
        notes: supplier?.notes ?? '',
        is_active: supplier ? supplier.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'suppliers.update', supplier.id)) : post(route(routePrefix + 'suppliers.store'));
    };

    const field = 'w-full rounded-2xl border-2 border-slate-100 bg-slate-50 px-5 py-3.5 text-sm font-bold text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10';
    const label = 'mb-2 ml-1 block text-xs font-black uppercase tracking-widest text-slate-500';
    const err = (k) => errors[k] && <p className="ml-2 mt-1 text-[10px] font-bold uppercase text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Supplier' : 'Tambah Supplier'} pageTitle="Master Data — Supplier">
            <Head title={isEdit ? 'Edit Supplier' : 'Tambah Supplier'} />

            <div className="mx-auto max-w-3xl space-y-8 pb-20">
                <div className="flex items-center justify-between">
                    <Link href={route(routePrefix + 'suppliers.index')} className="group inline-flex items-center gap-2 font-bold text-slate-500 transition-colors hover:text-slate-900">
                        <Icon name="arrow_back" className="text-[20px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                    </Link>
                    <h2 className="font-headline text-xl font-black tracking-tight text-slate-800">{isEdit ? `Edit ${supplier.code}` : 'Supplier Baru'}</h2>
                </div>

                <form onSubmit={submit} className="space-y-8 rounded-[2.5rem] border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="space-y-2 md:col-span-2"><label className={label}>Nama Supplier <span className="text-rose-500">*</span></label><input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: PT Sumber Pangan" className={field} />{err('name')}</div>
                        <div className="space-y-2"><label className={label}>Kontak Personal</label><input type="text" value={data.contact_person} onChange={(e) => setData('contact_person', e.target.value)} className={field} /></div>
                        <div className="space-y-2"><label className={label}>Telepon</label><input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className={field} /></div>
                        <div className="space-y-2 md:col-span-2"><label className={label}>Email</label><input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />{err('email')}</div>
                        <div className="space-y-2 md:col-span-2"><label className={label}>Alamat</label><textarea rows={2} value={data.address} onChange={(e) => setData('address', e.target.value)} className={`${field} resize-none font-medium`} /></div>
                        <div className="space-y-2 md:col-span-2"><label className={label}>Catatan</label><textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={`${field} resize-none font-medium`} /></div>
                        <label className="flex cursor-pointer items-center gap-3 px-2 md:col-span-2">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <span className="text-xs font-black uppercase tracking-widest text-slate-500">Supplier Aktif</span>
                        </label>
                    </div>

                    <button type="submit" disabled={processing} className="w-full rounded-2xl bg-slate-900 px-8 py-4 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/10 transition-all hover:bg-indigo-600 disabled:opacity-50">
                        {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Supplier'}
                    </button>
                </form>
            </div>
        </Layout>
    );
}
