import { Head, Link, useForm } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import SearchableSelect from '@/Components/SearchableSelect';

const route = window.route;

export default function TransferRequestsCreate({ products, units }) {
    const { data, setData, post, processing, errors } = useForm({ 
        date: new Date().toISOString().slice(0, 10), 
        notes: '', 
        items: [{ product_id: '', quantity: 1, unit_id: '' }] 
    });

    const addItem = () => setData('items', [...data.items, { product_id: '', quantity: 1, unit_id: '' }]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));
    const updateItem = (i, field, value) => { 
        const items = [...data.items]; 
        items[i][field] = value; 
        if (field === 'product_id') { 
            const p = products.find(pr => pr.id == value); 
            if (p) items[i].unit_id = p.unit_id; 
        } 
        setData('items', items); 
    };

    const submit = (e) => { e.preventDefault(); post(route('hendhys.transfer-requests.store')); };

    const fieldClass = 'w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3 py-2.5 text-sm text-gray-800 dark:text-white/90 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-sm transition-all';

    const productOptions = products.map(p => ({ value: p.id, label: p.name, sublabel: p.code }));
    const unitOptions = units.map(u => ({ value: u.id, label: u.abbreviation }));

    return (
        <HendhysLayout pageTitle="Buat Request ke Gudang">
            <Head title="Buat Request" />
            
            <form onSubmit={submit} className="mx-auto w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="border-b border-gray-100 dark:border-gray-800 bg-amber-50/10 dark:bg-amber-500/[0.01] p-6">
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Request <span className="text-red-500 dark:text-red-400">*</span></label>
                            <input type="date" required value={data.date} onChange={(e) => setData('date', e.target.value)} className={fieldClass} />
                            {errors.date && <p className="mt-1 text-xs text-red-500 dark:text-red-400">{errors.date}</p>}
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                            <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={fieldClass} placeholder="Misal: Urgen untuk produksi besok" />
                        </div>
                    </div>
                </div>

                <div className="p-6">
                    <div className="mb-4 flex items-end justify-between">
                        <h3 className="text-base font-bold text-gray-850 dark:text-white/90">Daftar Item Barang yang Direquest</h3>
                        <button type="button" onClick={addItem} className="flex items-center gap-1 rounded-lg bg-amber-50 dark:bg-amber-955/30 px-3 py-1.5 text-sm font-medium text-amber-600 dark:text-amber-400 transition-colors hover:bg-amber-100 dark:hover:bg-amber-955/50 dark:bg-amber-500/10">
                            <Icon name="add" className="text-[16px]" /> Tambah Baris
                        </button>
                    </div>

                    {errors.items && <p className="mb-2 text-sm text-red-500 dark:text-red-400">{errors.items}</p>}

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-y border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="w-1/2 px-4 py-3">Pilih Barang <span className="text-red-500 dark:text-red-400">*</span></th>
                                    <th className="px-4 py-3">Qty Request <span className="text-red-500 dark:text-red-400">*</span></th>
                                    <th className="px-4 py-3">Satuan <span className="text-red-500 dark:text-red-400">*</span></th>
                                    <th className="w-16 px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {data.items.map((item, i) => (
                                    <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                        <td className="px-4 py-3">
                                            <SearchableSelect
                                                options={productOptions}
                                                value={item.product_id}
                                                onChange={(val) => updateItem(i, 'product_id', val)}
                                                placeholder="-- Cari & pilih barang --"
                                            />
                                            {errors[`items.${i}.product_id`] && (
                                                <p className="mt-1 text-xs text-red-500">{errors[`items.${i}.product_id`]}</p>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <input type="number" min="1" required value={item.quantity} onChange={(e) => updateItem(i, 'quantity', e.target.value)} placeholder="0" className={fieldClass} />
                                        </td>
                                        <td className="px-4 py-3">
                                            <SearchableSelect
                                                options={unitOptions}
                                                value={item.unit_id}
                                                onChange={(val) => updateItem(i, 'unit_id', val)}
                                                placeholder="Satuan"
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {data.items.length > 1 && (
                                                <button type="button" onClick={() => removeItem(i)} className="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 dark:text-red-400">
                                                    <Icon name="delete" className="text-[20px]" />
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.01] p-6">
                    <Link href={route('hendhys.transfer-requests.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                    <button type="submit" disabled={processing} className="rounded-xl bg-amber-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50">
                        {processing ? 'Mengirim...' : 'Kirim Request'}
                    </button>
                </div>
            </form>
        </HendhysLayout>
    );
}
