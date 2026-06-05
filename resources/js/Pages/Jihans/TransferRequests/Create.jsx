import { Head, Link, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import SearchableSelect from '@/Components/SearchableSelect';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);
const blankItem = () => ({ product_id: '', quantity: '', unit_id: '' });

export default function JihansTransferRequestCreate({ products, units }) {
    const { data, setData, post, processing, errors } = useForm({
        date: todayISO(),
        notes: '',
        items: [blankItem()],
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, blankItem()]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onProduct = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        setItem(i, { product_id: productId, unit_id: p?.unit_id ?? '' });
    };

    const submit = (e) => { e.preventDefault(); post(route('jihans.transfer-requests.store')); };

    const field = 'w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3 py-2.5 text-sm text-gray-800 dark:text-white/90 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm transition-all';

    const productOptions = products.map(p => ({ value: p.id, label: p.name, sublabel: p.code }));
    const unitOptions = units.map(u => ({ value: u.id, label: u.abbreviation }));

    return (
        <JihansLayout pageTitle="Form Request Bahan Baku ke Gudang">
            <Head title="Buat Request" />

            <div className="mx-auto w-full">

                <form onSubmit={submit} className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 dark:border-gray-800 bg-orange-50/30 dark:bg-orange-500/[0.02] p-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Request <span className="text-red-500">*</span></label>
                                <input type="date" required value={data.date} onChange={(e) => setData('date', e.target.value)} className={field} />
                                {errors.date && <p className="mt-1 text-sm text-red-600">{errors.date}</p>}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan Tambahan</label>
                                <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Misal: Urgen untuk produksi besok" className={field} />
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="mb-4 flex items-end justify-between">
                            <h3 className="text-base font-bold text-gray-800 dark:text-white/90">Daftar Item Barang yang Direquest</h3>
                            <button type="button" onClick={addItem} className="flex items-center gap-1 rounded-lg bg-orange-50 dark:bg-orange-950/30 px-3 py-1.5 text-sm font-medium text-orange-600 dark:text-orange-400 transition-colors hover:bg-orange-100 dark:hover:bg-orange-950/50">
                                <Icon name="add" className="text-[16px]" /> Tambah Baris
                            </button>
                        </div>

                        {errors.items && <p className="mb-2 text-sm text-red-600">{errors.items}</p>}

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-y border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="w-1/2 px-4 py-3">Pilih Barang <span className="text-red-500">*</span></th>
                                        <th className="px-4 py-3">Qty Request <span className="text-red-500">*</span></th>
                                        <th className="px-4 py-3">Satuan <span className="text-red-500">*</span></th>
                                        <th className="w-16 px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-4 py-4">
                                                <SearchableSelect
                                                    options={productOptions}
                                                    value={item.product_id}
                                                    onChange={(val) => onProduct(i, val)}
                                                    placeholder="-- Cari & pilih barang --"
                                                    accentColor="orange"
                                                />
                                                {errors[`items.${i}.product_id`] && (
                                                    <p className="mt-1 text-xs text-red-500">{errors[`items.${i}.product_id`]}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <input type="number" step="1" min="1" required value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} placeholder="0" className={field} />
                                            </td>
                                            <td className="px-4 py-4">
                                                <SearchableSelect
                                                    options={unitOptions}
                                                    value={item.unit_id}
                                                    onChange={(val) => setItem(i, { unit_id: val })}
                                                    placeholder="Satuan"
                                                    accentColor="orange"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {data.items.length > 1 && (
                                                    <button type="button" onClick={() => removeItem(i)} className="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400">
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
                        <Link href={route('jihans.transfer-requests.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                        </Link>
                        <button type="submit" disabled={processing} className="flex items-center gap-2 rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-orange-600 disabled:opacity-50">
                            <Icon name="send" className="text-[18px]" /> {processing ? 'Mengirim...' : 'Kirim Request'}
                        </button>
                    </div>
                </form>
            </div>
        </JihansLayout>
    );
}
