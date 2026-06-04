import { Head, Link, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

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

    const field = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500';

    return (
        <JihansLayout pageTitle="Form Request Bahan Baku ke Gudang">
            <Head title="Buat Request" />

            <div className="mx-auto max-w-4xl">
                <Link href={route('jihans.transfer-requests.index')} className="mb-6 inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Daftar Request
                </Link>

                <form onSubmit={submit} className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-orange-50/50 p-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">Tanggal Request <span className="text-red-500">*</span></label>
                                <input type="date" required value={data.date} onChange={(e) => setData('date', e.target.value)} className={field} />
                                {errors.date && <p className="mt-1 text-sm text-red-600">{errors.date}</p>}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">Catatan Tambahan</label>
                                <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Misal: Urgen untuk produksi besok" className={field} />
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="mb-4 flex items-end justify-between">
                            <h3 className="font-bold text-gray-800">Daftar Item Barang yang Direquest</h3>
                            <button type="button" onClick={addItem} className="flex items-center gap-1 rounded-lg bg-orange-100 px-3 py-1.5 text-sm font-medium text-orange-700 transition-colors hover:bg-orange-200">
                                <Icon name="add" className="text-[16px]" /> Tambah Baris
                            </button>
                        </div>

                        {errors.items && <p className="mb-2 text-sm text-red-600">{errors.items}</p>}

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-y border-gray-200 bg-gray-50 text-gray-500">
                                    <tr>
                                        <th className="w-1/2 px-4 py-3 font-medium">Pilih Barang <span className="text-red-500">*</span></th>
                                        <th className="px-4 py-3 font-medium">Qty Request <span className="text-red-500">*</span></th>
                                        <th className="px-4 py-3 font-medium">Satuan <span className="text-red-500">*</span></th>
                                        <th className="w-16 px-4 py-3 text-center font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {data.items.map((item, i) => (
                                        <tr key={i}>
                                            <td className="px-4 py-3">
                                                <select required value={item.product_id} onChange={(e) => onProduct(i, e.target.value)} className={field}>
                                                    <option value="">-- Pilih Barang --</option>
                                                    {products.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
                                                </select>
                                            </td>
                                            <td className="px-4 py-3">
                                                <input type="number" step="1" min="1" required value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} placeholder="0" className={field} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <select required value={item.unit_id} onChange={(e) => setItem(i, { unit_id: e.target.value })} className={`${field} bg-gray-50`}>
                                                    <option value="">—</option>
                                                    {units.map((u) => <option key={u.id} value={u.id}>{u.abbreviation}</option>)}
                                                </select>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {data.items.length > 1 && (
                                                    <button type="button" onClick={() => removeItem(i)} className="rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 hover:text-red-700"><Icon name="delete" className="text-[20px]" /></button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/50 p-6">
                        <Link href={route('jihans.transfer-requests.index')} className="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">Batal</Link>
                        <button type="submit" disabled={processing} className="flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-orange-700 disabled:opacity-50">
                            <Icon name="send" className="text-[18px]" /> {processing ? 'Mengirim...' : 'Kirim Request'}
                        </button>
                    </div>
                </form>
            </div>
        </JihansLayout>
    );
}
