import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatRupiah } from '@/lib/format';
import Button from '@/components/ui/button/Button';

const route = window.route;

const todayISO = () => new Date().toISOString().slice(0, 10);
const blankItem = () => ({ product_id: '', unit_id: '', quantity: 1, price: 0, notes: '', search_text: '' });

export default function PurchaseOrderForm({ suppliers, products, units, po = null }) {
    const isEdit = !!po;

    const { data, setData, post, put, processing, errors } = useForm({
        supplier_id: po?.supplier_id ?? '',
        supplier_search_text: isEdit ? (suppliers.find((s) => String(s.id) === String(po.supplier_id))?.name ?? '') : '',
        date: po?.date ?? todayISO(),
        notes: po?.notes ?? '',
        items: isEdit
            ? po.details.map((d) => {
                const p = products.find((x) => String(x.id) === String(d.product_id));
                return {
                    product_id: d.product_id,
                    unit_id: d.unit_id,
                    quantity: d.quantity_ordered,
                    price: d.price,
                    notes: d.notes ?? '',
                    search_text: p ? `${p.name} (${p.code})` : '',
                };
            })
            : [blankItem()],
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const addItem = () => setData('items', [...data.items, blankItem()]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));

    const onSupplierInputChange = (val) => {
        const s = suppliers.find((x) => x.name === val);
        setData({
            ...data,
            supplier_search_text: val,
            supplier_id: s ? s.id : '',
        });
    };

    const onProductInputChange = (i, val) => {
        const p = products.find((x) => `${x.name} (${x.code})` === val);
        if (p) {
            setItem(i, {
                search_text: val,
                product_id: p.id,
                unit_id: p.unit_id ?? '',
                price: p.hpp || 0
            });
        } else {
            setItem(i, {
                search_text: val,
                product_id: '',
                unit_id: '',
                price: 0
            });
        }
    };

    const grandTotal = data.items.reduce((s, it) => s + Number(it.quantity || 0) * Number(it.price || 0), 0);

    const submit = (e) => {
        e.preventDefault();
        isEdit ? put(route('gudang.po.update', po.id)) : post(route('gudang.po.store'));
    };

    const selectClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-700 outline-hidden transition focus:border-brand-500 dark:border-gray-700 dark:text-gray-250 dark:bg-gray-900/50';
    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';

    return (
        <GudangLayout title={isEdit ? `Edit PO ${po.po_number}` : 'Buat Purchase Order'} pageTitle={isEdit ? `Edit PO ${po.po_number}` : 'Buat Purchase Order Baru'}>
            <Head title={isEdit ? 'Edit PO' : 'Buat PO'} />

            <datalist id="suppliers-list">
                {suppliers.map((s) => (
                    <option key={s.id} value={s.name} />
                ))}
            </datalist>

            <datalist id="products-list">
                {products.map((p) => (
                    <option key={p.id} value={`${p.name} (${p.code})`} />
                ))}
            </datalist>

            <form onSubmit={submit} className="space-y-6">
                <Link href={route('gudang.po.index')} className="group inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition-colors hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Kembali ke Daftar PO
                </Link>

                {Object.keys(errors).length > 0 && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/30 dark:bg-rose-500/5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500 dark:text-rose-400" />
                        <div>
                            <p className="mb-1 text-sm font-bold text-rose-700 dark:text-rose-400">Perbaiki kesalahan berikut:</p>
                            <ul className="list-inside list-disc space-y-0.5 text-sm text-rose-600 dark:text-rose-350">
                                {Object.values(errors).map((e, i) => <li key={i}>{e}</li>)}
                            </ul>
                        </div>
                    </div>
                )}

                {/* Header Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Informasi Pesanan</h3>
                    </div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Supplier / Vendor <span className="text-rose-500">*</span></label>
                            <input
                                type="text"
                                list="suppliers-list"
                                value={data.supplier_search_text ?? ''}
                                onChange={(e) => onSupplierInputChange(e.target.value)}
                                placeholder="Cari & pilih supplier..."
                                required
                                className={inputClass}
                            />
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal PO <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={inputClass} />
                        </div>
                        <div className="md:col-span-2">
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan</label>
                            <textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Instruksi pengiriman, termin pembayaran, dsb (opsional)..." className={areaClass} />
                        </div>
                    </div>
                </div>

                {/* Items Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Daftar Item Pesanan</h3>
                        <Button type="button" onClick={addItem} size="sm" startIcon={<Icon name="add" className="text-[16px]" />}>
                            Tambah Item
                        </Button>
                    </div>

                    {data.items.length === 0 ? (
                        <div className="py-16 text-center">
                            <Icon name="shopping_cart" className="mb-3 block text-[56px] text-gray-300 dark:text-gray-600" />
                            <p className="text-sm font-bold text-gray-400 dark:text-gray-500">Belum ada item. Klik "Tambah Item" untuk mulai.</p>
                        </div>
                    ) : (
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                        <th className="px-6 py-3.5 text-left" style={{ minWidth: 260 }}>Produk</th>
                                        <th className="px-4 py-3.5 text-center" style={{ minWidth: 130 }}>Satuan</th>
                                        <th className="px-4 py-3.5 text-center" style={{ minWidth: 100 }}>Qty</th>
                                        <th className="px-4 py-3.5 text-right" style={{ minWidth: 160 }}>Harga Satuan</th>
                                        <th className="px-6 py-3.5 text-right" style={{ minWidth: 140 }}>Subtotal</th>
                                        <th className="px-3 py-3.5" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-3">
                                                <input 
                                                    type="text" 
                                                    list="products-list"
                                                    value={item.search_text ?? ''} 
                                                    onChange={(e) => onProductInputChange(i, e.target.value)} 
                                                    placeholder="Cari & pilih produk..."
                                                    required 
                                                    className="w-full h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-850 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50" 
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                {(() => {
                                                    const activeUnit = units.find((u) => String(u.id) === String(item.unit_id));
                                                    const unitName = activeUnit ? activeUnit.name : '—';
                                                    return (
                                                        <div className="w-full h-10 flex items-center justify-center rounded-lg border border-gray-350 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/30 px-3 text-sm text-gray-500 dark:text-gray-400 font-medium select-none">
                                                            {unitName}
                                                        </div>
                                                    );
                                                })()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <input type="number" min="1" step="1" required value={item.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })}
                                                    className="w-full h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-center text-sm font-bold tabular-nums text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50" />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center h-10 overflow-hidden rounded-lg border border-gray-300 bg-transparent focus-within:border-brand-300 focus-within:ring-3 focus-within:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900/50">
                                                    <span className="shrink-0 border-r border-gray-350 bg-gray-50/50 dark:bg-gray-900/70 px-2.5 py-2 text-xs font-bold text-gray-400 dark:border-gray-700">Rp</span>
                                                    <input type="number" min="0" step="1" required value={item.price} onChange={(e) => setItem(i, { price: e.target.value })}
                                                        className="flex-1 bg-transparent px-3 py-2 text-right text-sm font-bold tabular-nums text-gray-800 dark:text-white/90 outline-hidden" />
                                                </div>
                                            </td>
                                            <td className="px-6 py-3 text-right font-bold tabular-nums text-gray-800 dark:text-white/90">{formatRupiah(Number(item.quantity || 0) * Number(item.price || 0))}</td>
                                            <td className="px-3 py-3 text-center">
                                                <button type="button" onClick={() => removeItem(i)} className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-gray-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-455">
                                                    <Icon name="delete" className="text-[18px]" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {data.items.length > 0 && (
                        <div className="flex items-center justify-between border-t border-brand-100 bg-brand-50/20 dark:border-brand-900/50 dark:bg-brand-500/5 px-6 py-4">
                            <div className="flex items-center gap-2 text-brand-600 dark:text-brand-400">
                                <span className="text-xs font-bold uppercase tracking-wider">Total Item:</span>
                                <span className="font-bold">{data.items.length}</span>
                            </div>
                            <div className="flex items-baseline gap-2">
                                <span className="text-xs font-bold uppercase tracking-wider text-brand-500 dark:text-brand-400">Grand Total</span>
                                <span className="text-xl font-bold tabular-nums text-brand-600 dark:text-brand-400">{formatRupiah(grandTotal)}</span>
                            </div>
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <Link href={route('gudang.po.index')}>
                        <Button variant="outline" type="button">Batal</Button>
                    </Link>
                    <Button type="submit" disabled={processing} startIcon={<Icon name="save" className="text-[18px]" />}>
                        {isEdit ? 'Simpan Perubahan' : 'Buat Purchase Order'}
                    </Button>
                </div>
            </form>
        </GudangLayout>
    );
}

