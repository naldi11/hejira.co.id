import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = {
    GudangLayout,
    JihansLayout,
    HendhysLayout,
};

const route = window.route;

export default function KaryawanForm({ karyawan, layout, routePrefix, currentScope }) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const isEdit = !!karyawan;
    
    const { data, setData, post, put, processing, errors } = useForm({
        name: karyawan?.name || '',
        phone: karyawan?.phone || '',
        address: karyawan?.address || '',
        is_active: karyawan?.is_active ?? true,
        entity_scope: karyawan?.entity_scope || currentScope,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route(routePrefix + 'karyawan.update', karyawan.id));
        } else {
            post(route(routePrefix + 'karyawan.store'));
        }
    };

    return (
        <Layout pageTitle={isEdit ? "Edit Karyawan" : "Tambah Karyawan"}>
            <Head title={isEdit ? "Edit Karyawan" : "Tambah Karyawan"} />
            <form onSubmit={submit} className="mx-auto max-w-3xl space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">
                        {isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru'}
                    </h2>
                    <Link href={route(routePrefix + 'karyawan.index')} className="text-sm font-medium text-gray-500 hover:text-gray-700">
                        &larr; Kembali
                    </Link>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" value={data.name} onChange={e => setData('name', e.target.value)} className="w-full rounded-lg border-gray-300 py-2.5 text-sm" required />
                            {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700">Telepon</label>
                            <input type="text" value={data.phone} onChange={e => setData('phone', e.target.value)} className="w-full rounded-lg border-gray-300 py-2.5 text-sm" />
                            {errors.phone && <p className="mt-1 text-xs text-red-500">{errors.phone}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <select value={data.is_active ? 1 : 0} onChange={e => setData('is_active', e.target.value === '1')} className="w-full rounded-lg border-gray-300 py-2.5 text-sm">
                                <option value={1}>Aktif</option>
                                <option value={0}>Nonaktif</option>
                            </select>
                            {errors.is_active && <p className="mt-1 text-xs text-red-500">{errors.is_active}</p>}
                        </div>
                        
                        {(currentScope === 'all' || currentScope === 'gudang') && (
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-gray-700">Entity Scope</label>
                                <select value={data.entity_scope} onChange={e => setData('entity_scope', e.target.value)} className="w-full rounded-lg border-gray-300 py-2.5 text-sm">
                                    <option value="gudang">Gudang</option>
                                    <option value="jihans">Jihans</option>
                                    <option value="hendhys">Hendhys</option>
                                    <option value="all">Semua Entitas</option>
                                </select>
                                {errors.entity_scope && <p className="mt-1 text-xs text-red-500">{errors.entity_scope}</p>}
                            </div>
                        )}

                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                            <textarea value={data.address} onChange={e => setData('address', e.target.value)} rows="3" className="w-full rounded-lg border-gray-300 py-2.5 text-sm"></textarea>
                            {errors.address && <p className="mt-1 text-xs text-red-500">{errors.address}</p>}
                        </div>
                    </div>

                    <div className="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <Link href={route(routePrefix + 'karyawan.index')} className="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </Link>
                        <button type="submit" disabled={processing} className="rounded-xl bg-gray-800 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-gray-900 disabled:opacity-50">
                            {processing ? 'Menyimpan...' : 'Simpan Karyawan'}
                        </button>
                    </div>
                </div>
            </form>
        </Layout>
    );
}
