import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const VISIBILITY = [
    { key: 'visible_gudang', label: 'Gudang Tempua', icon: 'warehouse' },
    { key: 'visible_jihans', label: "Jihan's Food", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys Brownies', icon: 'cake' },
];

export default function CustomerForm({ customer = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const c = customer?.data ?? customer;
    const isEdit = !!(c && c.id);

    const { data, setData, post, put, processing, errors } = useForm({
        name: c?.name ?? '',
        phone: c?.phone ?? '',
        email: c?.email ?? '',
        is_active: c ? c.is_active : true,
        province: c?.province ?? '',
        city: c?.city ?? '',
        district: c?.district ?? '',
        address: c?.address ?? '',
        notes: c?.notes ?? '',
        visible_gudang: c ? c.visible_gudang : true,
        visible_jihans: c ? c.visible_jihans : true,
        visible_hendhys: c ? c.visible_hendhys : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'customers.update', c.id)) : post(route(routePrefix + 'customers.store'));
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Customer' : 'Tambah Customer'} pageTitle="Master Data — Customer">
            <Head title={isEdit ? 'Edit Customer' : 'Tambah Customer'} />

            <form onSubmit={submit} className="w-full space-y-6 pb-20">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="space-y-5 p-6">
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label className={labelClass}>Nama Lengkap / Instansi <span className="text-rose-500">*</span></label>
                                <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Masukkan nama customer..." className={inputClass} />
                                {err('name')}
                            </div>
                            <div>
                                <label className={labelClass}>Nomor Telepon</label>
                                <input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="Nomor telepon..." className={inputClass} />
                                {err('phone')}
                            </div>
                            <div>
                                <label className={labelClass}>Alamat Email</label>
                                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="email@domain.com..." className={inputClass} />
                                {err('email')}
                            </div>
                            <div>
                                <label className={labelClass}>Status Akun</label>
                                <select value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')} className={inputClass}>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                            <div>
                                <label className={labelClass}>Provinsi</label>
                                <input type="text" value={data.province} onChange={(e) => setData('province', e.target.value)} placeholder="Provinsi..." className={inputClass} />
                            </div>
                            <div>
                                <label className={labelClass}>Kabupaten / Kota</label>
                                <input type="text" value={data.city} onChange={(e) => setData('city', e.target.value)} placeholder="Kota..." className={inputClass} />
                            </div>
                            <div>
                                <label className={labelClass}>Kecamatan</label>
                                <input type="text" value={data.district} onChange={(e) => setData('district', e.target.value)} placeholder="Kecamatan..." className={inputClass} />
                            </div>
                            <div className="md:col-span-2">
                                <label className={labelClass}>Alamat Lengkap</label>
                                <textarea rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Alamat lengkap..." className={areaClass} />
                            </div>
                            <div className="md:col-span-2">
                                <label className={labelClass}>Catatan Tambahan</label>
                                <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan tambahan..." className={inputClass} />
                            </div>
                        </div>

                        <div className="border-t border-gray-150 pt-5 dark:border-gray-800">
                            <label className={`${labelClass} mb-4`}>Tampilkan di Entitas</label>
                            <div className="flex flex-wrap gap-4">
                                {VISIBILITY.map((v) => {
                                    const on = data[v.key];
                                    return (
                                        <button type="button" key={v.key} onClick={() => setData(v.key, !on)}
                                            className={`flex min-w-[150px] flex-1 cursor-pointer flex-col items-center justify-center rounded-2xl border p-5 transition-all ${on ? 'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-850 dark:bg-brand-500/10 dark:text-brand-400' : 'border-gray-200 bg-gray-50 text-gray-450 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400'}`}>
                                            <Icon name={v.icon} filled={on} className="mb-2 text-[28px]" />
                                            <span className="text-xs font-bold uppercase tracking-wider">{v.label}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <Link href={route(routePrefix + 'customers.index')}>
                        <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                            Kembali ke Daftar
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Daftarkan Customer'}
                    </Button>
                </div>
            </form>
        </Layout>
    );
}
