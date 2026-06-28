import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

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

    const { data, setData, post, processing, errors, transform } = useForm({
        po_id: initialPo?.id ?? '',
        supplier_id: initialPo?.supplier_id ?? '',
        date: todayISO(),
        notes: '',
        received_by_name: '',
        supplier_rep_name: '',
        items: initialPo ? itemsFromPo(initialPo) : [blankItem()],
        photos: [],
        photo_urls: [],
    });

    const [previews, setPreviews] = useState([]);
    const [isDragging, setIsDragging] = useState(false);
    const fromPo = !!data.po_id;

    useEffect(() => {
        const filePreviews = data.photos.map((f) => URL.createObjectURL(f));
        const urlPreviews = data.photo_urls;
        setPreviews([...filePreviews, ...urlPreviews]);
        return () => filePreviews.forEach((url) => URL.revokeObjectURL(url));
    }, [data.photos, data.photo_urls]);

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

    const handleBagusChange = (i, val) => {
        const b = Math.max(0, Number(val) || 0);
        const item = data.items[i];
        if (fromPo) {
            const maxRusak = Math.max(0, item.ordered_qty - b);
            const newRusak = item.quantity_rusak > maxRusak ? maxRusak : item.quantity_rusak;
            setItem(i, { quantity_bagus: b, quantity_rusak: newRusak });
        } else {
            setItem(i, { quantity_bagus: b });
        }
    };

    const handleRusakChange = (i, val) => {
        const r = Math.max(0, Number(val) || 0);
        const item = data.items[i];
        if (fromPo) {
            const maxBagus = Math.max(0, item.ordered_qty - r);
            const newBagus = item.quantity_bagus > maxBagus ? maxBagus : item.quantity_bagus;
            setItem(i, { quantity_rusak: r, quantity_bagus: newBagus });
        } else {
            setItem(i, { quantity_rusak: r });
        }
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = () => {
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        const files = Array.from(e.dataTransfer.files).filter((f) => f.type.startsWith('image/'));
        if (files.length > 0) {
            setData('photos', [...data.photos, ...files]);
            return;
        }

        let imageUrl = '';
        
        // 1. Try to get image src from HTML first
        const html = e.dataTransfer.getData('text/html');
        if (html) {
            const match = html.match(/src="([^"]+)"/i);
            if (match && match[1]) {
                imageUrl = match[1];
            }
        }
        
        // 2. Fallback to uri-list or plain text
        if (!imageUrl) {
            imageUrl = e.dataTransfer.getData('text/uri-list') || e.dataTransfer.getData('text/plain');
        }
        
        // 3. Extract parameter if Google redirect URL
        if (imageUrl) {
            try {
                const urlObj = new URL(imageUrl);
                const imgUrlParam = urlObj.searchParams.get('imgurl');
                if (imgUrlParam) {
                    imageUrl = decodeURIComponent(imgUrlParam);
                }
            } catch (err) {}

            if (imageUrl && (imageUrl.startsWith('http://') || imageUrl.startsWith('https://') || imageUrl.startsWith('data:image/'))) {
                setData('photo_urls', [...data.photo_urls, imageUrl]);
            }
        }
    };

    const onPhotos = (e) => {
        const files = Array.from(e.target.files);
        setData('photos', [...data.photos, ...files]);
        e.target.value = '';
    };

    const removePhoto = (i) => {
        if (i < data.photos.length) {
            setData('photos', data.photos.filter((_, idx) => idx !== i));
        } else {
            const urlIdx = i - data.photos.length;
            setData('photo_urls', data.photo_urls.filter((_, idx) => idx !== urlIdx));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        transform((d) => ({
            ...d,
            items: d.items.map(item => ({
                product_id: item.product_id,
                quantity_bagus: item.quantity_bagus,
                quantity_rusak: item.quantity_rusak,
                unit_id: item.unit_id,
                hpp_price: item.hpp_price,
                batch_number: item.batch_number,
                expired_date: item.expired_date,
                notes: item.notes,
            }))
        }));
        post(route('gudang.receiving.store'), { forceFormData: true });
    };

    const selectClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-755 outline-hidden transition focus:border-brand-500 dark:border-gray-700 dark:text-gray-250 dark:bg-gray-900/50';
    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    
    const cellSelect = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const cellInput = 'w-20 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-center text-xs font-bold text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const cellText = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const cellArea = 'w-full resize-none rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';

    return (
        <GudangLayout title="Buat Penerimaan Barang" pageTitle="Penerimaan Barang">
            <Head title="Buat GRN" />

            <form onSubmit={submit} className="space-y-6">
                <Link href={route('gudang.receiving.index')} className="group inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition-colors hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Batal &amp; Kembali
                </Link>

                {Object.keys(errors).length > 0 && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/30 dark:bg-rose-500/5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500 dark:text-rose-400" />
                        <ul className="list-inside list-disc space-y-0.5 text-sm text-rose-600 dark:text-rose-350">{Object.values(errors).map((e, i) => <li key={i}>{e}</li>)}</ul>
                    </div>
                )}

                {/* Header Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Informasi Penerimaan</h3>
                    </div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Referensi PO <span className="font-normal normal-case text-gray-400">(opsional)</span></label>
                            <select value={data.po_id} onChange={(e) => onPoChange(e.target.value)} className={selectClass}>
                                <option value="">— Input Manual (Tanpa PO) —</option>
                                {purchaseOrders.map((po) => <option key={po.id} value={po.id}>{po.po_number} — {po.supplier}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Supplier <span className="text-rose-500">*</span></label>
                            <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} required disabled={fromPo} className={`${selectClass} disabled:opacity-60`}>
                                <option value="">Pilih supplier...</option>
                                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal Terima <span className="text-rose-500">*</span></label>
                            <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required className={inputClass} />
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Penerima Gudang <span className="text-rose-500">*</span></label>
                            <input type="text" value={data.received_by_name} onChange={(e) => setData('received_by_name', e.target.value)} required placeholder="Nama penerima..." className={inputClass} />
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Perwakilan Supplier <span className="text-rose-500">*</span></label>
                            <input type="text" value={data.supplier_rep_name} onChange={(e) => setData('supplier_rep_name', e.target.value)} required placeholder="Nama perwakilan supplier..." className={inputClass} />
                        </div>
                        <div>
                            <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan / No. Surat Jalan</label>
                            <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="No. Surat Jalan, kondisi box, dll..." className={inputClass} />
                        </div>
                    </div>
                </div>

                {/* Items Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div>
                            <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Verifikasi Barang Masuk</h3>
                            {fromPo && <p className="mt-1 text-xs font-semibold text-brand-500 dark:text-brand-400">↳ Item diisi otomatis dari PO</p>}
                        </div>
                        {!fromPo && (
                            <Button type="button" onClick={addItem} size="sm" startIcon={<Icon name="add" className="text-[16px]" />}>
                                Tambah Item
                            </Button>
                        )}
                    </div>

                    {data.items.length === 0 ? (
                        <div className="py-16 text-center">
                            <Icon name="inventory_2" className="mb-3 block text-[56px] text-gray-300 dark:text-gray-650" />
                            <p className="text-sm font-bold text-gray-400 dark:text-gray-500">Pilih PO di atas atau klik "Tambah Item".</p>
                        </div>
                    ) : (
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                        <th className="px-6 py-3.5 text-left" style={{ minWidth: 220 }}>Produk</th>
                                        {fromPo && <th className="px-4 py-3.5 text-center" style={{ minWidth: 80 }}>Dipesan</th>}
                                        <th className="px-4 py-3.5 text-left" style={{ minWidth: 180 }}>Jumlah Terima</th>
                                        <th className="px-4 py-3.5 text-left" style={{ minWidth: 180 }}>Batch &amp; Kedaluwarsa</th>
                                        <th className="px-4 py-3.5 text-left" style={{ minWidth: 150 }}>Catatan</th>
                                        {!fromPo && <th className="px-3 py-3.5" style={{ minWidth: 48 }} />}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {data.items.map((item, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4">
                                                {fromPo ? (
                                                    <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{item.product_name}</p>
                                                ) : (
                                                    <select value={item.product_id} onChange={(e) => onProductChange(i, e.target.value)} required className={cellSelect}>
                                                        <option value="">Pilih produk...</option>
                                                        {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                                    </select>
                                                )}
                                            </td>
                                            {fromPo && <td className="px-4 py-4 text-center text-sm font-bold tabular-nums text-gray-400 dark:text-gray-500">{item.ordered_qty} {item.unit_name}</td>}
                                            <td className="px-4 py-4">
                                                <div className="space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-14 text-xs font-bold text-green-600 dark:text-green-400">Bagus:</span>
                                                        <input type="number" min="0" step="1" required value={item.quantity_bagus} onChange={(e) => handleBagusChange(i, e.target.value)} className={cellInput} />
                                                        <span className="text-xs font-semibold text-gray-400 dark:text-gray-500">{item.unit_name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="w-14 text-xs font-bold text-rose-500">Rusak:</span>
                                                        <input type="number" min="0" step="1" required value={item.quantity_rusak} onChange={(e) => handleRusakChange(i, e.target.value)} className={cellInput} />
                                                        <span className="text-xs font-semibold text-gray-400 dark:text-gray-550">{item.unit_name}</span>
                                                    </div>
                                                    <div className="pt-1 border-t border-dashed border-gray-205 dark:border-gray-800 text-[10px] text-gray-400 dark:text-gray-500 flex justify-between">
                                                        <span>Total:</span>
                                                        <span className="font-bold">{Number(item.quantity_bagus) + Number(item.quantity_rusak)} {item.unit_name}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="space-y-2 px-4 py-4">
                                                <input type="text" value={item.batch_number} onChange={(e) => setItem(i, { batch_number: e.target.value })} placeholder="No. Batch" className={cellText} />
                                                <input type="date" value={item.expired_date} onChange={(e) => setItem(i, { expired_date: e.target.value })} className={cellText} />
                                            </td>
                                            <td className="px-4 py-4">
                                                <textarea rows={2} value={item.notes} onChange={(e) => setItem(i, { notes: e.target.value })} placeholder="Catatan item..." className={cellArea} />
                                            </td>
                                            {!fromPo && (
                                                <td className="px-3 py-4 text-center">
                                                    <button type="button" onClick={() => removeItem(i)} className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:text-gray-650 dark:hover:bg-rose-500/10 dark:hover:text-rose-455">
                                                        <Icon name="delete" className="text-[18px]" />
                                                    </button>
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
                    <div 
                        className={`rounded-2xl border p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] md:col-span-2 transition-all ${isDragging ? 'border-brand-500 bg-brand-500/5 ring-3 ring-brand-500/10' : 'border-gray-200 bg-white'}`}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                    >
                        <label className="mb-3 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {isDragging ? 'Lepas Foto di Sini' : 'Foto Bukti Penerimaan (opsional, maks 5MB/foto)'}
                        </label>
                        {previews.length > 0 ? (
                            <div className="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                {previews.map((url, i) => (
                                    <div key={i} className="group relative aspect-square overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                                        <img src={url} alt="" className="h-full w-full object-cover" />
                                        <div className="absolute inset-0 flex items-center justify-center bg-gray-950/40 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" onClick={() => removePhoto(i)} className="flex h-8 w-8 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg transition-colors hover:bg-rose-700"><Icon name="close" className="text-[16px]" /></button>
                                        </div>
                                    </div>
                                ))}
                                <label className="flex aspect-square cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 text-gray-400 transition hover:border-brand-500 dark:border-gray-700 hover:bg-gray-55 dark:hover:bg-gray-900/50">
                                    <Icon name="add_a_photo" className="mb-1 text-[24px]" />
                                    <span className="text-[10px] font-bold uppercase tracking-wider">Tambah</span>
                                    <input type="file" accept="image/*" multiple className="hidden" onChange={onPhotos} />
                                </label>
                            </div>
                        ) : (
                            <label className="block cursor-pointer rounded-xl border border-dashed border-gray-300 p-8 text-center transition hover:border-brand-500 dark:border-gray-700 hover:bg-gray-55 dark:hover:bg-gray-900/30">
                                <Icon name="add_a_photo" className="mb-2 block text-[36px] text-gray-300 dark:text-gray-600 mx-auto" />
                                <p className="text-sm font-bold text-gray-500 dark:text-gray-400">Klik atau Drag & Drop Foto di Sini</p>
                                <p className="mt-1 text-xs text-gray-400 dark:text-gray-550">JPG, PNG, WebP</p>
                                <input type="file" accept="image/*" multiple className="hidden" onChange={onPhotos} />
                            </label>
                        )}
                        {errors.photos && <p className="mt-2 text-xs font-bold text-rose-600 dark:text-rose-455">{errors.photos}</p>}
                    </div>

                    <div className="flex flex-col justify-between rounded-2xl bg-gray-950 p-6 text-white dark:bg-white/[0.03] dark:border dark:border-gray-800 shadow-xl">
                        <div className="mb-6 space-y-2">
                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500"><Icon name="task_alt" className="text-[22px]" /></div>
                            <h3 className="text-sm font-bold uppercase tracking-wider">Konfirmasi Penerimaan</h3>
                            <p className="text-xs leading-relaxed text-gray-400 dark:text-gray-500">Stok gudang aktif akan bertambah sesuai kuantitas Bagus yang diterima setelah dikonfirmasi.</p>
                        </div>
                        <Button type="submit" disabled={processing} className="w-full">
                            {processing ? 'Menyimpan...' : 'Konfirmasi & Simpan'}
                        </Button>
                    </div>
                </div>
            </form>
        </GudangLayout>
    );
}

