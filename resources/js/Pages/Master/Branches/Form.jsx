import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

export default function BranchForm({ branch = null, layout = 'GudangLayout', routePrefix = 'master.' }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const b = branch?.data ?? branch;
    const isEdit = !!(b && b.id);

    const { data, setData, post, put, processing, errors } = useForm({
        code: b?.code ?? '',
        type: b?.type ?? 'cabang',
        name: b?.name ?? '',
        phone: b?.phone ?? '',
        address: b?.address ?? '',
        is_active: b ? b.is_active : true,
    });

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route(routePrefix + 'branches.update', b.id)) : post(route(routePrefix + 'branches.store'));
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Cabang' : 'Tambah Cabang'} pageTitle="Konfigurasi Cabang">
            <Head title={isEdit ? 'Edit Cabang' : 'Tambah Cabang'} />

            <div className="w-full space-y-6 pb-20">

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <form onSubmit={submit} className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-2">

                        <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <label className={labelClass}>Kode Cabang <span className="text-rose-500">*</span></label>
                                <input type="text" required value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="cth: HND-CB3" className={inputClass} />
                                {err('code')}
                            </div>
                            <div>
                                <label className={labelClass}>Tipe Unit / Cabang <span className="text-rose-500">*</span></label>
                                <select required value={data.type} onChange={(e) => setData('type', e.target.value)} className={inputClass}>
                                    <option value="cabang">Outlet / Cabang</option>
                                    <option value="pusat">Kantor Pusat / Gudang</option>
                                </select>
                                {err('type')}
                            </div>
                            <div className="md:col-span-2">
                                <label className={labelClass}>Nama Cabang <span className="text-rose-500">*</span></label>
                                <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: Hendhys Brownies SM Raja" className={inputClass} />
                                {err('name')}
                            </div>
                            <div>
                                <label className={labelClass}>Nomor Telepon</label>
                                <input type="text" value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="0812xxxx" className={inputClass} />
                                {err('phone')}
                            </div>
                            <div className="flex items-center pt-5">
                                <label className="inline-flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-5 w-5 rounded-md border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900/50" />
                                    <span className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cabang Aktif</span>
                                </label>
                            </div>
                            <div className="md:col-span-2">
                                <label className={labelClass}>Alamat Lengkap</label>
                                <textarea rows={3} value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Jl. Raya No. 123..." className={areaClass} />
                                {err('address')}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <Link href={route(routePrefix + 'branches.index')}>
                                <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                                    Kembali ke Daftar
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Daftarkan Cabang'}
                            </Button>
                        </div>
                    </form>

                    <div className="rounded-2xl border border-transparent bg-brand-500 p-6 text-white shadow-theme-xs dark:bg-brand-500/10 dark:border-brand-500/20">
                        <Icon name="info" className="mb-4 text-[32px] text-brand-200 dark:text-brand-400" />
                        <h3 className="mb-4 text-xs font-bold uppercase tracking-wider text-white dark:text-brand-300">Informasi Tipe Cabang</h3>
                        <ul className="space-y-4">
                            <li className="flex gap-3">
                                <span className="text-xs text-brand-200 dark:text-brand-400">●</span>
                                <p className="text-xs font-medium leading-relaxed dark:text-gray-300">
                                    <strong className="text-white">Pusat/Gudang:</strong> Berfungsi sebagai gudang utama penyimpanan stok barang dan kantor administrasi operasional.
                                </p>
                            </li>
                            <li className="flex gap-3">
                                <span className="text-xs text-brand-200 dark:text-brand-400">●</span>
                                <p className="text-xs font-medium leading-relaxed dark:text-gray-300">
                                    <strong className="text-white">Outlet/Cabang:</strong> Unit bisnis retail tempat penjualan barang langsung ke pelanggan.
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </Layout>
    );
}

