import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

export default function SupplierForm({ supplier = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const s = supplier?.data ?? supplier;
    const isEdit = !!(s && s.id);

    const { data, setData, post, put, processing, errors } = useForm({
        name: s?.name ?? '',
        contact_person: s?.contact_person ?? '',
        phone: s?.phone ?? '',
        email: s?.email ?? '',
        address: s?.address ?? '',
        notes: s?.notes ?? '',
        is_active: s ? s.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'suppliers.update', s.id)) : post(route(routePrefix + 'suppliers.store'));
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Supplier' : 'Tambah Supplier'} pageTitle="Master Data — Supplier">
            <Head title={isEdit ? 'Edit Supplier' : 'Tambah Supplier'} />

            <div className="w-full space-y-6 pb-20">
                <form onSubmit={submit} className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="space-y-5 p-6">
                        <div>
                            <label className={labelClass}>Nama Supplier <span className="text-rose-500">*</span></label>
                            <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: PT Sumber Pangan" className={inputClass} />
                            {err('name')}
                        </div>

                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label className={labelClass}>Kontak Personal</label>
                                <input type="text" value={data.contact_person} onChange={(e) => setData('contact_person', e.target.value)} placeholder="Nama kontak pic..." className={inputClass} />
                                {err('contact_person')}
                            </div>
                            <div>
                                <label className={labelClass}>Telepon</label>
                                <input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="Nomor telepon..." className={inputClass} />
                                {err('phone')}
                            </div>
                        </div>

                        <div>
                            <label className={labelClass}>Email</label>
                            <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="alamat@email.com..." className={inputClass} />
                            {err('email')}
                        </div>

                        <div>
                            <label className={labelClass}>Alamat</label>
                            <textarea rows={2} value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Alamat lengkap supplier..." className={areaClass} />
                            {err('address')}
                        </div>

                        <div>
                            <label className={labelClass}>Catatan</label>
                            <textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan tambahan..." className={areaClass} />
                            {err('notes')}
                        </div>

                        <div className="pt-2">
                            <label className="inline-flex cursor-pointer items-center gap-3">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-md border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900/50" />
                                <span className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Supplier Aktif / Dapat Digunakan</span>
                            </label>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-3 border-t border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <Link href={route(routePrefix + 'suppliers.index')}>
                            <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                                Kembali ke Daftar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Supplier'}
                        </Button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}

