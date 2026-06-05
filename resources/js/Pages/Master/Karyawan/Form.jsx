import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

export default function KaryawanForm({ karyawan = null, layout = 'GudangLayout', routePrefix = 'master.', currentScope = 'all' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const k = karyawan?.data ?? karyawan;
    const isEdit = !!(k && k.id);
    
    const { data, setData, post, put, processing, errors } = useForm({
        name: k?.name || '',
        phone: k?.phone || '',
        address: k?.address || '',
        is_active: k?.is_active ?? true,
        entity_scope: k?.entity_scope || currentScope,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route(routePrefix + 'karyawan.update', k.id));
        } else {
            post(route(routePrefix + 'karyawan.store'));
        }
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? "Edit Karyawan" : "Tambah Karyawan"} pageTitle="Master Data — Karyawan">
            <Head title={isEdit ? "Edit Karyawan" : "Tambah Karyawan"} />

            <form onSubmit={submit} className="w-full space-y-6 pb-20">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="space-y-5 p-6">
                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label className={labelClass}>Nama Lengkap <span className="text-rose-500">*</span></label>
                                <input type="text" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="Nama lengkap karyawan..." className={inputClass} required />
                                {err('name')}
                            </div>

                            <div>
                                <label className={labelClass}>Telepon</label>
                                <input type="text" value={data.phone} onChange={e => setData('phone', e.target.value)} placeholder="Nomor telepon..." className={inputClass} />
                                {err('phone')}
                            </div>

                            <div>
                                <label className={labelClass}>Status</label>
                                <select value={data.is_active ? 1 : 0} onChange={e => setData('is_active', e.target.value === '1')} className={inputClass}>
                                    <option value={1}>Aktif</option>
                                    <option value={0}>Nonaktif</option>
                                </select>
                                {err('is_active')}
                            </div>
                            
                            {(currentScope === 'all' || currentScope === 'gudang') && (
                                <div className="md:col-span-2">
                                    <label className={labelClass}>Entity Scope</label>
                                    <select value={data.entity_scope} onChange={e => setData('entity_scope', e.target.value)} className={inputClass}>
                                        <option value="gudang">Gudang</option>
                                        <option value="jihans">Jihans</option>
                                        <option value="hendhys">Hendhys</option>
                                        <option value="all">Semua Entitas</option>
                                    </select>
                                    {err('entity_scope')}
                                </div>
                            )}

                            <div className="md:col-span-2">
                                <label className={labelClass}>Alamat Lengkap</label>
                                <textarea value={data.address} onChange={e => setData('address', e.target.value)} rows="3" placeholder="Alamat lengkap..." className={areaClass}></textarea>
                                {err('address')}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <Link href={route(routePrefix + 'karyawan.index')}>
                        <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                            Kembali ke Daftar
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan Karyawan'}
                    </Button>
                </div>
            </form>
        </Layout>
    );
}
