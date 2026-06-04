import { Head, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

const route = window.route;

const SLOTS = [
    { key: 'tb_product_id', label: 'Tortilla Besar (TB)' },
    { key: 'ts_product_id', label: 'Tortilla Sedang (TS)' },
    { key: 'tk_product_id', label: 'Tortilla Kecil (TK)' },
    { key: 'tc_product_id', label: 'Tortilla Cone (TC)' },
    { key: 'kribab_product_id', label: 'Kulit Kribab' },
];

export default function JihansProductionConfig({ config, products }) {
    const { data, setData, put, processing } = useForm({
        tb_product_id: config.tb_product_id ?? '',
        ts_product_id: config.ts_product_id ?? '',
        tk_product_id: config.tk_product_id ?? '',
        tc_product_id: config.tc_product_id ?? '',
        kribab_product_id: config.kribab_product_id ?? '',
    });

    const submit = (e) => { e.preventDefault(); put(route('jihans.master.production-config.update'), { preserveScroll: true }); };

    return (
        <JihansLayout pageTitle="Konfigurasi Produksi Tortilla">
            <Head title="Konfigurasi Produksi" />

            <div className="mx-auto max-w-2xl">
                <div className="mb-6">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-800">Konfigurasi Produksi</h2>
                    <p className="text-sm text-gray-500">Petakan tiap tipe produksi tortilla ke produk jadi yang sesuai. Stok produk ini akan bertambah otomatis saat produksi dicatat.</p>
                </div>

                <form onSubmit={submit} className="space-y-6 rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                    {SLOTS.map((slot) => (
                        <div key={slot.key}>
                            <label className="mb-2 block text-sm font-medium text-gray-700">{slot.label}</label>
                            <select value={data[slot.key]} onChange={(e) => setData(slot.key, e.target.value)} className="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">— Belum dipetakan —</option>
                                {products.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
                            </select>
                        </div>
                    ))}

                    <button type="submit" disabled={processing} className="flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition-colors hover:bg-orange-700 disabled:opacity-50">
                        <Icon name="save" className="text-[18px]" /> {processing ? 'Menyimpan...' : 'Simpan Konfigurasi'}
                    </button>
                </form>
            </div>
        </JihansLayout>
    );
}
