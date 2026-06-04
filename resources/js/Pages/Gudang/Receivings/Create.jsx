import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);
const blankItem = () => ({ product_id: '', product_name: '', quantity_bagus: 1, quantity_rusak: 0, ordered_qty: 0, unit_id: '', unit_name: 'PCS', hpp_price: 0, batch_number: '', expired_date: '', notes: '' });

function itemsFromPo(po) {
    return po.details.map((d) => ({
        product_id: d.product_id, product_name: d.product_name,
        quantity_bagus: Math.max(0, d.quantity_ordered - d.quantity_received), quantity_rusak: 0,
        ordered_qty: d.quantity_ordered, unit_id: d.unit_id, unit_name: d.unit_name,
        hpp_price: d.price, batch_number: '', expired_date: '', notes: '',
    }));
}

export default function ReceivingCreate({ suppliers, products, purchaseOrders, selectedPoId }) {
    const initialPo = selectedPoId ? purchaseOrders.find((p) => p.id === selectedPoId) : null;

    const { data, setData, post, processing, errors } = useForm({
        po_id: initialPo?.id ?? '',
        supplier_id: initialPo?.supplier_id ?? '',
        date: todayISO(),
        notes: '',
        items: initialPo ? itemsFromPo(initialPo) : [blankItem()],
        photos: [],
    });

    const [previews, setPreviews] = useState([]);
    const fromPo = !!data.po_id;

    useEffect(() => () => previews.forEach((u) => URL.revokeObjectURL(u)), [previews]);

    const onPoChange = (value) => {
        if (!value) {
            setData((d) => ({ ...d, po_id: '', supplier_id: '', items: [blankItem()] }));
            return;
        }
        const po = purchaseOrders.find((p) => String(p.id) === String(value));
        setData((d) => ({ ...d, po_id: po.id, supplier_id: po.supplier_id, items: itemsFromPo(po) }));
    };

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, blankItem()]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onProductChange = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        setItem(i, p ? { product_id: p.id, unit_id: p.unit_id, unit_name: p.unit_name, hpp_price: p.hpp } : { product_id: '', unit_id: '', unit_name: 'PCS', hpp_price: 0 });
    };

    const onPhotos = (e) => {
        const files = Array.from(e.target.files);
        const merged = [...data.photos, ...files];
        setData('photos', merged);
        setPreviews(merged.map((f) => URL.createObjectURL(f)));
        e.target.value = '';
    };
    const removePhoto = (i) => {
        const merged = data.photos.filter((_, idx) => idx !== i);
        setData('photos', merged);
        setPreviews(merged.map((f) => URL.createObjectURL(f)));
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('gudang.receiving.store'), { forceFormData: true });
    };

    const fieldClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10';
    const cellInput = 'w-20 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-center text-xs font-bold tabular-nums text-slate-900 outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10';

    return (
        <GudangLayout title="Buat Penerimaan Barang" pageTitle="Penerimaan Barang">
            <Head title="Buat GRN" />

            <form onSubmit={submit} className="space-y-6">
                <Link href={route('gudang.receiving.index')} className="group inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition-colors hover:text-slate-800">
                    <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                </Link>

                {Object.keys(errors).length > 0 && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500" />
                        <ul className="list-inside list-disc space-y-0.5 text-sm text-rose-600">{Object.values(errors).map((e, i) => <li key={i}>{e}</li>)}</ul>
                    </div>
                )}

                {/* Header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 bg-slate-50/50 px-6 py-4"><h3 className="text-sm font-bold uppercase tracking-wider text-slate-700">Informasi Penerimaan</h3></div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Referensi PO <span className="font-normal normal-case text-slate-400">(opsional)</span></label>
                            <select value={data.po_id} onChange={(e) => onPoChange(e.target.value)} className={fieldClass}>
                                <option value="">— Input Manual (Tanpa PO) —</option>
                                {purchaseOrders.map((po) => <option key={po.id} value={po.id}>{po.po_number} — {po.supplier}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Supplier <span className="text-rose-500">*</span></label>
                            <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} required disabled={fromPo} className={`${fieldClass} disabled:opacity-60`}>
                                <option value="">Pilih supplier...</option>
                                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Terima <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={fieldClass} />
                        </div>
                        <div className="md:col-span-3">
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">Catatan</label>
                            <textarea rows={1} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Kondisi barang, kurir, dll..." className={`${fieldClass} resize-none`} />
                        </div>
                    </div>
                </div>

                {/* Items */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <div>
                            <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700">Verifikasi Barang Masuk</h3>
                            {fromPo && <p className="mt-0.5 text-xs font-bold text-indigo-600">↳ Item diisi otomatis dari PO</p>}
                        </div>
                        {!fromPo && (
                            <button type="button" onClick={addItem} className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                                <Icon name="add" className="text-[16px]" /> Tambah Item
                            </button>
                        )}
                    </div>

                    {data.items.length === 0 ? (
                        <div className="py-16 text-center"><Icon name="inventory_2" className="mb-3 block text-[56px] text-slate-200" /><p className="text-sm font-bold text-slate-400">Pilih PO di atas atau klik "Tambah Item".</p></div>
                    ) : (
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                                        <th className="px-6 py-3 text-left" style={{ minWidth: 220 }}>Produk</th>
                                        {fromPo && <th className="px-4 py-3 text-center" style={{ minWidth: 80 }}>Dipesan</th>}
                                        <th className="px-4 py-3 text-left" style={{ minWidth: 180 }}>Jumlah Terima</th>
                                        <th className="px-4 py-3 text-left" style={{ minWidth: 180 }}>Batch &amp; Kedaluwarsa</th>
                                        <th className="px-4 py-3 text-left" style={{ minWidth: 150 }}>Catatan</th>
                                        {!fromPo && <th className="px-3 py-3" style={{ minWidth: 48 }} />}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                {fromPo ? (
                                                    <p className="text-sm font-bold text-slate-800">{item.product_name}</p>
                                                ) : (
                                                    <select value={item.product_id} onChange={(e) => onProductChange(i, e.target.value)} required className="w-full rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs outline-none focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10">
                                                        <option value="">Pilih produk...</option>
                                                        {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                                    </select>
                                                )}
                                            </td>
                                            {fromPo && <td className="px-4 py-4 text-center text-sm font-bold tabular-nums text-slate-400">{item.ordered_qty} {item.unit_name}</td>}
                                            <td className="px-4 py-4">
                                                <div className="space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-14 text-xs font-bold text-green-600">Bagus:</span>
                                                        <input type="number" min="0" step="1" required value={item.quantity_bagus} onChange={(e) => setItem(i, { quantity_bagus: e.target.value })} className={cellInput} />
                                                        <span className="text-xs font-semibold text-slate-400">{item.unit_name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-14 text-xs font-bold text-rose-500">Rusak:</span>
                                                        <input type="number" min="0" step="1" required value={item.quantity_rusak} onChange={(e) => setItem(i, { quantity_rusak: e.target.value })} className={cellInput} />
                                                        <span className="text-xs font-semibold text-slate-400">{item.unit_name}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="space-y-2 px-4 py-4">
                                                <input type="text" value={item.batch_number} onChange={(e) => setItem(i, { batch_number: e.target.value })} placeholder="No. Batch" className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold uppercase text-slate-700 outline-none focus:border-indigo-500 focus:bg-white" />
                                                <input type="date" value={item.expired_date} onChange={(e) => setItem(i, { expired_date: e.target.value })} className="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700 outline-none focus:border-indigo-500 focus:bg-white" />
                                            </td>
                                            <td className="px-4 py-4">
                                                <textarea rows={2} value={item.notes} onChange={(e) => setItem(i, { notes: e.target.value })} placeholder="Catatan item..." className="w-full resize-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-700 outline-none focus:border-indigo-500 focus:bg-white" />
                                            </td>
                                            {!fromPo && (
                                                <td className="px-3 py-4 text-center">
                                                    <button type="button" onClick={() => removeItem(i)} className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition-all hover:bg-rose-50 hover:text-rose-500"><Icon name="delete" className="text-[18px]" /></button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Photos + submit */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
                        <label className="mb-3 block text-xs font-bold uppercase tracking-wider text-slate-500">Foto Bukti Penerimaan <span className="font-normal normal-case text-slate-400">(opsional, maks 5MB/foto)</span></label>
                        {previews.length > 0 ? (
                            <div className="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                {previews.map((url, i) => (
                                    <div key={i} className="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                        <img src={url} alt="" className="h-full w-full object-cover" />
                                        <div className="absolute inset-0 flex items-center justify-center bg-slate-950/40 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" onClick={() => removePhoto(i)} className="flex h-8 w-8 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg transition-colors hover:bg-rose-700"><Icon name="close" className="text-[16px]" /></button>
                                        </div>
                                    </div>
                                ))}
                                <label className="flex aspect-square cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 text-slate-400 transition-all hover:border-indigo-300 hover:bg-slate-50">
                                    <Icon name="add_a_photo" className="mb-1 text-[24px]" /><span className="text-[10px] font-bold uppercase tracking-wider">Tambah</span>
                                    <input type="file" accept="image/*" multiple className="hidden" onChange={onPhotos} />
                                </label>
                            </div>
                        ) : (
                            <label className="block cursor-pointer rounded-xl border-2 border-dashed border-slate-200 p-8 text-center transition-all hover:border-indigo-300 hover:bg-slate-50">
                                <Icon name="add_a_photo" className="mb-2 block text-[36px] text-slate-300" />
                                <p className="text-sm font-bold text-slate-500">Klik untuk upload foto</p>
                                <p className="mt-1 text-xs text-slate-400">JPG, PNG, WebP</p>
                                <input type="file" accept="image/*" multiple className="hidden" onChange={onPhotos} />
                            </label>
                        )}
                        {errors.photos && <p className="mt-2 text-xs font-bold text-rose-600">{errors.photos}</p>}
                    </div>

                    <div className="flex flex-col justify-between rounded-2xl bg-slate-900 p-6 text-white shadow-xl">
                        <div className="mb-6 space-y-2">
                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600"><Icon name="task_alt" className="text-[22px]" /></div>
                            <h3 className="text-sm font-bold uppercase tracking-wider">Konfirmasi Penerimaan</h3>
                            <p className="text-xs leading-relaxed text-slate-400">Stok gudang aktif akan bertambah sesuai kuantitas Bagus yang diterima setelah dikonfirmasi.</p>
                        </div>
                        <button type="submit" disabled={processing} className="w-full rounded-xl bg-indigo-600 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-lg shadow-indigo-600/30 transition-all hover:bg-indigo-500 disabled:opacity-50">
                            {processing ? 'Menyimpan...' : 'Konfirmasi & Simpan'}
                        </button>
                    </div>
                </div>
            </form>
        </GudangLayout>
    );
}
