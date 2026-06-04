import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatRupiah } from '@/lib/format';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);
const blankItem = () => ({ product_id: '', unit_id: '', quantity: 1, price: 0, notes: '' });

export default function PurchaseOrderForm({ suppliers, products, units, po = null }) {
    const isEdit = !!po;

    const { data, setData, post, put, processing, errors } = useForm({
        supplier_id: po?.supplier_id ?? '',
        date: po?.date ?? todayISO(),
        notes: po?.notes ?? '',
        items: isEdit
            ? po.details.map((d) => ({ product_id: d.product_id, unit_id: d.unit_id, quantity: d.quantity_ordered, price: d.price, notes: d.notes ?? '' }))
            : [blankItem()],
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, blankItem()]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onProductChange = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        setItem(i, p ? { product_id: p.id, unit_id: p.unit_id ?? '', price: p.hpp || 0 } : { product_id: '', unit_id: '', price: 0 });
    };

    const grandTotal = data.items.reduce((s, it) => s + Number(it.quantity || 0) * Number(it.price || 0), 0);

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route('gudang.po.update', po.id)) : post(route('gudang.po.store'));
    };

    const fieldClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10';

    return (
        <GudangLayout title={isEdit ? `Edit PO ${po.po_number}` : 'Buat Purchase Order'} pageTitle={isEdit ? `Edit PO ${po.po_number}` : 'Buat Purchase Order Baru'}>
            <Head title={isEdit ? 'Edit PO' : 'Buat PO'} />

            <form onSubmit={submit} className="space-y-6">
                <Link href={route('gudang.po.index')} className="group inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition-colors hover:text-slate-800">
                    <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Kembali ke Daftar PO
                </Link>

                {Object.keys(errors).length > 0 && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500" />
                        <div>
                            <p className="mb-1 text-sm font-bold text-rose-700">Perbaiki kesalahan berikut:</p>
                            <ul className="list-inside list-disc space-y-0.5 text-sm text-rose-600">
                                {Object.values(errors).map((e, i) => <li key={i}>{e}</li>)}
                            </ul>
                        </div>
                    </div>
                )}

                {/* Header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 px-6 py-4"><h3 className="text-sm font-bold uppercase tracking-wider text-slate-700">Informasi Pesanan</h3></div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Supplier / Vendor <span className="text-rose-500">*</span></label>
                            <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} required className={fieldClass}>
                                <option value="">Pilih supplier...</option>
                                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal PO <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={fieldClass} />
                        </div>
                        <div className="md:col-span-2">
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Catatan</label>
                            <textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Instruksi pengiriman, termin pembayaran, dsb (opsional)..." className={`${fieldClass} resize-none`} />
                        </div>
                    </div>
                </div>

                {/* Items */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700">Daftar Item Pesanan</h3>
                        <button type="button" onClick={addItem} className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                            <Icon name="add" className="text-[16px]" /> Tambah Item
                        </button>
                    </div>

                    {data.items.length === 0 ? (
                        <div className="py-16 text-center">
                            <Icon name="shopping_cart" className="mb-3 block text-[56px] text-slate-200" />
                            <p className="text-sm font-bold text-slate-400">Belum ada item. Klik "Tambah Item" untuk mulai.</p>
                        </div>
                    ) : (
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                                        <th className="px-6 py-3 text-left" style={{ minWidth: 260 }}>Produk</th>
                                        <th className="px-4 py-3 text-center" style={{ minWidth: 130 }}>Satuan</th>
                                        <th className="px-4 py-3 text-center" style={{ minWidth: 100 }}>Qty</th>
                                        <th className="px-4 py-3 text-right" style={{ minWidth: 160 }}>Harga Satuan</th>
                                        <th className="px-6 py-3 text-right" style={{ minWidth: 140 }}>Subtotal</th>
                                        <th className="px-3 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-3">
                                                <select value={item.product_id} onChange={(e) => onProductChange(i, e.target.value)} required className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10">
                                                    <option value="">Pilih produk...</option>
                                                    {products.map((p) => <option key={p.id} value={p.id}>{p.name} ({p.code})</option>)}
                                                </select>
                                            </td>
                                            <td className="px-4 py-3">
                                                <select value={item.unit_id} onChange={(e) => setItem(i, { unit_id: e.target.value })} required className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10">
                                                    <option value="">—</option>
                                                    {units.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                                                </select>
                                            </td>
                                            <td className="px-4 py-3">
                                                <input type="number" min="1" step="1" required value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })}
                                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-center text-sm font-bold tabular-nums text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10" />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/10">
                                                    <span className="shrink-0 border-r border-slate-200 bg-white px-2.5 py-2.5 text-xs font-bold text-slate-400">Rp</span>
                                                    <input type="number" min="0" step="1" required value={item.price} onChange={(e) => setItem(i, { price: e.target.value })}
                                                        className="flex-1 bg-transparent px-3 py-2.5 text-right text-sm font-bold tabular-nums text-slate-900 outline-none" />
                                                </div>
                                            </td>
                                            <td className="px-6 py-3 text-right font-bold tabular-nums text-slate-800">{formatRupiah(Number(item.quantity || 0) * Number(item.price || 0))}</td>
                                            <td className="px-3 py-3 text-center">
                                                <button type="button" onClick={() => removeItem(i)} className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition-all hover:bg-rose-50 hover:text-rose-500"><Icon name="delete" className="text-[18px]" /></button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {data.items.length > 0 && (
                        <div className="flex items-center justify-between border-t-2 border-indigo-200 bg-indigo-50 px-6 py-4">
                            <div className="flex items-center gap-2 text-indigo-700"><span className="text-xs font-bold uppercase tracking-wider">Total Item:</span><span className="font-bold">{data.items.length}</span></div>
                            <div className="flex items-baseline gap-2"><span className="text-xs font-bold uppercase tracking-wider text-indigo-600">Grand Total</span><span className="text-2xl font-bold tabular-nums text-indigo-700">{formatRupiah(grandTotal)}</span></div>
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <Link href={route('gudang.po.index')} className="rounded-xl border border-slate-200 px-6 py-3 text-sm font-bold text-slate-500 transition-all hover:bg-slate-50">Batal</Link>
                    <button type="submit" disabled={processing} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-8 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-xl shadow-indigo-600/25 transition-all hover:bg-indigo-700 disabled:opacity-50">
                        <Icon name="save" className="text-[20px]" /> {isEdit ? 'Simpan Perubahan' : 'Buat Purchase Order'}
                    </button>
                </div>
            </form>
        </GudangLayout>
    );
}
