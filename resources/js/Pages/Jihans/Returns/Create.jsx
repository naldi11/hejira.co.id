import { Head, Link, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);
const CONDITIONS = ['Rusak', 'Kadaluwarsa', 'Tidak Sesuai', 'Kelebihan Stok', 'Lainnya'];
const blankItem = () => ({ product_id: '', quantity: '', unit_id: '', unit_name: '', stock: 0, condition: CONDITIONS[0], notes: '' });

export default function JihansReturnsCreate({ products, units }) {
    const { data, setData, post, processing, errors } = useForm({ date: todayISO(), notes: '', items: [blankItem()] });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, blankItem()]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onProduct = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        setItem(i, p ? { product_id: p.id, unit_id: p.unit_id, unit_name: p.unit_name, stock: p.stock } : { product_id: '', unit_id: '', unit_name: '', stock: 0 });
    };

    const submit = (e) => { e.preventDefault(); post(route('jihans.returns-to-gudang.store')); };

    const field = 'w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500';

    return (
        <JihansLayout pageTitle="Buat Retur ke Gudang">
            <Head title="Buat Retur" />

            <div className="mx-auto max-w-5xl">
                <Link href={route('jihans.returns-to-gudang.index')} className="mb-6 inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Daftar Retur
                </Link>

                {errors.items && <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{errors.items}</div>}

                <form onSubmit={submit} className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="grid grid-cols-1 gap-6 border-b border-gray-100 bg-orange-50/50 p-6 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700">Tanggal Retur <span className="text-red-500">*</span></label>
                            <input type="date" required value={data.date} onChange={(e) => setData('date', e.target.value)} className={field} />
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700">Catatan</label>
                            <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Alasan umum retur..." className={field} />
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="mb-4 flex items-end justify-between">
                            <h3 className="font-bold text-gray-800">Barang yang Diretur</h3>
                            <button type="button" onClick={addItem} className="flex items-center gap-1 rounded-lg bg-orange-100 px-3 py-1.5 text-sm font-medium text-orange-700 transition-colors hover:bg-orange-200"><Icon name="add" className="text-[16px]" /> Tambah Baris</button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-y border-gray-200 bg-gray-50 text-gray-500">
                                    <tr><th className="px-3 py-3 font-medium" style={{ minWidth: 200 }}>Barang <span className="text-red-500">*</span></th><th className="px-3 py-3 text-center font-medium">Stok</th><th className="px-3 py-3 font-medium">Qty <span className="text-red-500">*</span></th><th className="px-3 py-3 font-medium">Kondisi <span className="text-red-500">*</span></th><th className="px-3 py-3 font-medium">Catatan</th><th className="w-12 px-3 py-3" /></tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {data.items.map((item, i) => {
                                        const over = Number(item.quantity) > item.stock;
                                        return (
                                            <tr key={i}>
                                                <td className="px-3 py-3">
                                                    <select required value={item.product_id} onChange={(e) => onProduct(i, e.target.value)} className={field}>
                                                        <option value="">-- Pilih --</option>
                                                        {products.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
                                                    </select>
                                                </td>
                                                <td className="px-3 py-3 text-center"><span className={`rounded px-2 py-0.5 text-xs font-medium ${over ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}`}>{formatQty(item.stock)} {item.unit_name}</span></td>
                                                <td className="px-3 py-3"><input type="number" step="any" min="0.001" max={item.stock} required value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} className={`${field} ${over ? 'border-red-500' : ''}`} placeholder="0" /></td>
                                                <td className="px-3 py-3">
                                                    <select required value={item.condition} onChange={(e) => setItem(i, { condition: e.target.value })} className={field}>
                                                        {CONDITIONS.map((c) => <option key={c} value={c}>{c}</option>)}
                                                    </select>
                                                </td>
                                                <td className="px-3 py-3"><input type="text" value={item.notes} onChange={(e) => setItem(i, { notes: e.target.value })} className={field} placeholder="Opsional" /></td>
                                                <td className="px-3 py-3 text-center">{data.items.length > 1 && <button type="button" onClick={() => removeItem(i)} className="rounded-lg p-1.5 text-red-500 hover:bg-red-50"><Icon name="delete" className="text-[18px]" /></button>}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/50 p-6">
                        <Link href={route('jihans.returns-to-gudang.index')} className="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">Batal</Link>
                        <button type="submit" disabled={processing} className="flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-orange-700 disabled:opacity-50"><Icon name="local_shipping" className="text-[18px]" /> {processing ? 'Memproses...' : 'Kirim Retur'}</button>
                    </div>
                </form>
            </div>
        </JihansLayout>
    );
}
