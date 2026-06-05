import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';
import Button from '@/components/ui/button/Button';

const route = window.route;

const KONDISI = {
    baik: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border border-green-200 dark:border-green-800',
    rusak: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-800',
    kurang: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800'
};

function EditForm({ receiving, onCancel }) {
    const hasOrdered = receiving.details?.some((d) => d.quantity_ordered !== null) ?? false;
    const { data, setData, put, processing } = useForm({
        notes: receiving.notes ?? '',
        received_by_name: receiving.received_by_name ?? '',
        supplier_rep_name: receiving.supplier_rep_name ?? '',
        kendala: receiving.kendala ?? '',
        items: (receiving.details ?? []).map((d) => ({ detail_id: d.id, quantity: d.quantity, kondisi: d.kondisi ?? '', hpp_price: d.hpp_price, notes: d.notes ?? '' })),
    });

    const setItem = (i, patch) => setData('items', data.items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)));
    const submit = (e) => { e.preventDefault(); put(route('gudang.receiving.update', receiving.id), { preserveScroll: true, onSuccess: onCancel }); };
    
    const inputClass = 'w-full h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div className="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                    <h3 className="text-xs font-bold uppercase tracking-wider text-brand-500 dark:text-brand-400">Edit Header</h3>
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Catatan / No. Surat Jalan</label>
                        <input type="text" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={inputClass} />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Nama Penerima Gudang</label>
                        <input type="text" value={data.received_by_name} onChange={(e) => setData('received_by_name', e.target.value)} placeholder="Wajib sebelum Selesaikan GRN" className={inputClass} />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Perwakilan Supplier</label>
                        <input type="text" value={data.supplier_rep_name} onChange={(e) => setData('supplier_rep_name', e.target.value)} placeholder="Wajib sebelum Selesaikan GRN" className={inputClass} />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Kendala / Catatan Masalah</label>
                        <textarea rows={2} value={data.kendala} onChange={(e) => setData('kendala', e.target.value)} className={areaClass} />
                    </div>
                </div>
                <div className="space-y-2.5 rounded-2xl border border-brand-100 bg-brand-50/20 p-5 text-sm text-brand-700 dark:border-brand-900/20 dark:bg-brand-500/5 dark:text-brand-400">
                    <p className="font-bold uppercase tracking-wider text-xs">Catatan Edit GRN:</p>
                    <ul className="list-inside list-disc space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                        <li>Stok gudang otomatis disesuaikan berdasarkan selisih qty</li>
                        <li>Qty tidak boleh dikurangi melebihi stok yang tersedia</li>
                        <li>Selesaikan GRN membutuhkan Nama Penerima &amp; Perwakilan Supplier</li>
                    </ul>
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                <div className="border-b border-gray-150 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <h3 className="text-sm font-bold text-gray-700 dark:text-gray-300">Edit Item Diterima</h3>
                </div>
                <div className="custom-scrollbar overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                <th className="px-4 py-3.5 text-left">Produk</th>
                                {hasOrdered && <th className="px-4 py-3.5 text-center">Qty PO</th>}
                                <th className="px-4 py-3.5 text-center">Qty Diterima</th>
                                <th className="px-4 py-3.5 text-center">Satuan</th>
                                <th className="px-4 py-3.5 text-center">Kondisi</th>
                                <th className="px-4 py-3.5 text-right">Harga Beli</th>
                                <th className="px-4 py-3.5 text-left">Catatan</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                            {receiving.details.map((d, i) => (
                                <tr key={d.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                    <td className="px-4 py-3 font-semibold text-gray-800 dark:text-white/90">{d.product}</td>
                                    {hasOrdered && <td className="px-4 py-3 text-center text-xs text-gray-400 dark:text-gray-500">{d.quantity_ordered !== null ? formatQty(d.quantity_ordered) : '-'}</td>}
                                    <td className="px-4 py-2">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            required
                                            value={data.items[i].quantity}
                                            onChange={(e) => setItem(i, { quantity: e.target.value })}
                                            className="w-24 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-center text-xs text-gray-800 focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50"
                                        />
                                    </td>
                                    <td className="px-4 py-2 text-center text-xs text-gray-500 dark:text-gray-400">{d.unit}</td>
                                    <td className="px-4 py-2">
                                        <select
                                            value={data.items[i].kondisi}
                                            onChange={(e) => setItem(i, { kondisi: e.target.value })}
                                            className="h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs text-gray-750 focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50"
                                        >
                                            <option value="">-</option>
                                            <option value="baik">Baik</option>
                                            <option value="rusak">Rusak</option>
                                            <option value="kurang">Kurang</option>
                                        </select>
                                    </td>
                                    <td className="px-4 py-2">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            value={data.items[i].hpp_price}
                                            onChange={(e) => setItem(i, { hpp_price: e.target.value })}
                                            className="w-32 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-right text-xs text-gray-800 focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50"
                                        />
                                    </td>
                                    <td className="px-4 py-2">
                                        <input
                                            type="text"
                                            value={data.items[i].notes}
                                            onChange={(e) => setItem(i, { notes: e.target.value })}
                                            placeholder="Catatan..."
                                            className="w-40 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs text-gray-800 focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50"
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="flex gap-3">
                <Button type="submit" disabled={processing}>Simpan Perubahan</Button>
                <Button type="button" variant="outline" onClick={onCancel}>Batal</Button>
            </div>
        </form>
    );
}

function PhotoUpload({ receiving }) {
    const { data, setData, post, processing, errors, reset } = useForm({ photos: [], photo_urls: [], caption: '' });
    const [previews, setPreviews] = useState([]);
    const [isDragging, setIsDragging] = useState(false);

    useEffect(() => {
        const filePreviews = data.photos.map((f) => URL.createObjectURL(f));
        const urlPreviews = data.photo_urls;
        setPreviews([...filePreviews, ...urlPreviews]);
        return () => filePreviews.forEach((url) => URL.revokeObjectURL(url));
    }, [data.photos, data.photo_urls]);

    const onPhotos = (filesList) => {
        const files = Array.from(filesList).filter((f) => f.type.startsWith('image/'));
        setData('photos', [...data.photos, ...files]);
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
        post(route('gudang.receiving.photos.store', receiving.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
            }
        });
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

    const inputClass = 'w-full h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const totalPhotos = data.photos.length + data.photo_urls.length;

    return (
        <form onSubmit={submit} className="border-t border-gray-150 bg-gray-50/50 p-5 dark:border-gray-850 dark:bg-white/[0.01] space-y-4">
            <div 
                className={`rounded-xl border border-dashed p-6 text-center transition-all ${isDragging ? 'border-brand-500 bg-brand-500/5 ring-3 ring-brand-500/10' : 'border-gray-300 dark:border-gray-750 hover:bg-gray-55 dark:hover:bg-gray-900/30'}`}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
            >
                {previews.length > 0 ? (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {previews.map((url, i) => (
                            <div key={i} className="group relative aspect-square overflow-hidden rounded-xl border border-gray-200 dark:border-gray-850 bg-gray-50 dark:bg-gray-900/50">
                                <img src={url} alt="" className="h-full w-full object-cover" />
                                <div className="absolute inset-0 flex items-center justify-center bg-gray-950/40 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button type="button" onClick={() => removePhoto(i)} className="flex h-8 w-8 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg transition-colors hover:bg-rose-700">
                                        <Icon name="close" className="text-[16px]" />
                                    </button>
                                </div>
                            </div>
                        ))}
                        <label className="flex aspect-square cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 text-gray-400 transition hover:border-brand-500 dark:border-gray-750 hover:bg-gray-55 dark:hover:bg-gray-900/50">
                            <Icon name="add_a_photo" className="mb-1 text-[24px]" />
                            <span className="text-[10px] font-bold uppercase tracking-wider">Tambah</span>
                            <input type="file" accept="image/*" multiple className="hidden" onChange={(e) => onPhotos(e.target.files)} />
                        </label>
                    </div>
                ) : (
                    <label className="block cursor-pointer">
                        <Icon name="add_a_photo" className="mb-2 block text-[36px] text-gray-300 dark:text-gray-600 mx-auto" />
                        <p className="text-sm font-bold text-gray-500 dark:text-gray-400">
                            {isDragging ? 'Lepas Foto di Sini' : 'Klik atau Drag & Drop Foto di Sini'}
                        </p>
                        <p className="mt-1 text-xs text-gray-400 dark:text-gray-550">Maks. {10 - receiving.photos.length} foto lagi</p>
                        <input type="file" accept="image/*" multiple className="hidden" onChange={(e) => onPhotos(e.target.files)} required={previews.length === 0} />
                    </label>
                )}
            </div>

            <div className="flex flex-wrap items-end gap-4">
                <div className="flex-1 min-w-[200px]">
                    <label className="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Keterangan (opsional)</label>
                    <input type="text" value={data.caption} onChange={(e) => setData('caption', e.target.value)} placeholder="Contoh: Kondisi kardus..." className={inputClass} />
                </div>
                <Button type="submit" disabled={processing || totalPhotos === 0} size="sm" startIcon={<Icon name="photo_camera" className="text-[16px]" />}>
                    Upload {totalPhotos > 0 ? `${totalPhotos} ` : ''}Foto
                </Button>
            </div>
            {errors.photos && <p className="text-xs text-rose-500 dark:text-rose-400">{errors.photos}</p>}
        </form>
    );
}

export default function ReceivingShow({ receiving: rawReceiving }) {
    const receiving = rawReceiving.data || rawReceiving;
    const [editMode, setEditMode] = useState(false);
    const { errors } = usePage().props;
    const hasOrdered = receiving.details?.some((d) => d.quantity_ordered !== null) ?? false;

    const close = () => {
        if (window.confirm('Tutup GRN ini? Setelah ditutup tidak dapat diedit lagi.')) {
            router.post(route('gudang.receiving.close', receiving.id), {}, { preserveScroll: true });
        }
    };
    const deletePhoto = (photoId) => {
        if (window.confirm('Hapus foto ini?')) {
            router.delete(route('gudang.receiving.photos.destroy', [receiving.id, photoId]), { preserveScroll: true });
        }
    };

    return (
        <GudangLayout title={`Detail GRN ${receiving.grn_number}`} pageTitle={`Penerimaan Barang — ${receiving.grn_number}`}>
            <Head title={receiving.grn_number} />

            <div className="mx-auto max-w-5xl space-y-6">
                {errors?.close && (
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/30 dark:bg-rose-500/5 dark:text-rose-400">
                        {errors.close}
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <Link href={route('gudang.receiving.index')}>
                        <Button variant="outline" size="sm" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                            Kembali
                        </Button>
                    </Link>
                    <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold ${receiving.is_open ? 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-400' : 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-500/10 dark:text-green-400'}`}>
                        {receiving.is_open ? 'TERBUKA' : 'SELESAI'}
                    </span>
                    {receiving.is_open && (
                        <Button variant="outline" size="sm" onClick={() => setEditMode((v) => !v)} startIcon={<Icon name="edit" className="text-[16px]" />}>
                            {editMode ? 'Batal Edit' : 'Edit GRN'}
                        </Button>
                    )}
                    <a href={route('gudang.receiving.print', receiving.id)} target="_blank" rel="noreferrer">
                        <Button variant="outline" size="sm" startIcon={<Icon name="print" className="text-[16px]" />}>
                            Cetak BAST
                        </Button>
                    </a>
                    {receiving.is_open ? (
                        <Button
                            onClick={close}
                            size="sm"
                            className="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-600 dark:hover:bg-emerald-700"
                            startIcon={<Icon name="check" className="text-[16px]" />}
                        >
                            Selesaikan GRN
                        </Button>
                    ) : (
                        <span className="text-xs text-gray-400 dark:text-gray-500 font-semibold ml-2">
                            Ditutup: {receiving.closed_at} oleh {receiving.closed_by}
                        </span>
                    )}
                </div>

                {editMode ? (
                    <EditForm receiving={receiving} onCancel={() => setEditMode(false)} />
                ) : (
                    <>
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                                <h3 className="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Informasi Supplier</h3>
                                <div className="space-y-3.5 text-sm">
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Supplier</p>
                                        <p className="text-sm font-semibold text-gray-850 dark:text-white/90">{receiving.supplier}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Perwakilan Supplier</p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">{receiving.supplier_rep_name || '—'}</p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                                <h3 className="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Informasi Penerimaan</h3>
                                <div className="space-y-3.5 text-sm">
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">No. GRN</p>
                                        <p className="text-sm font-bold text-gray-850 dark:text-white/90">{receiving.grn_number}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Tanggal</p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">{formatDate(receiving.date)}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Referensi PO</p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">
                                            {receiving.po ? (
                                                <Link href={route('gudang.po.show', receiving.po.id)} className="font-semibold text-brand-500 dark:text-brand-400 hover:underline">
                                                    {receiving.po.po_number}
                                                </Link>
                                            ) : (
                                                'Penerimaan Langsung'
                                            )}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Catatan / No. Surat Jalan</p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">{receiving.notes || '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Diterima Oleh (Gudang)</p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">{receiving.received_by_name || '—'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {receiving.kendala && (
                            <div className="rounded-2xl border border-orange-200 bg-orange-50/50 p-4 text-sm dark:border-orange-950/20 dark:bg-orange-500/5">
                                <p className="mb-1.5 text-xs font-bold uppercase tracking-wider text-orange-500">Kendala / Catatan Masalah</p>
                                <p className="text-sm text-orange-850 dark:text-orange-300">{receiving.kendala}</p>
                            </div>
                        )}

                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold text-gray-700 dark:text-gray-300">Item Produk Diterima</h3>
                                <span className="text-base font-bold text-brand-500 dark:text-brand-400">{formatRupiah(receiving.grand_total)}</span>
                            </div>
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                            <th className="px-6 py-3.5">Produk</th>
                                            {hasOrdered && <th className="px-4 py-3.5 text-center">Qty PO</th>}
                                            <th className="px-4 py-3.5 text-center">Qty Terima</th>
                                            <th className="px-4 py-3.5 text-center">Satuan</th>
                                            <th className="px-4 py-3.5 text-center">Kondisi</th>
                                            <th className="px-4 py-3.5 text-right">Harga Beli</th>
                                            <th className="px-4 py-3.5 text-right">Total</th>
                                            <th className="px-6 py-3.5">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {receiving.details.map((item) => (
                                            <tr key={item.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">{item.product}</td>
                                                {hasOrdered && <td className="px-4 py-4 text-center text-xs text-gray-400 dark:text-gray-550">{item.quantity_ordered !== null ? formatQty(item.quantity_ordered) : '-'}</td>}
                                                <td className="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">{formatQty(item.quantity)}</td>
                                                <td className="px-4 py-4 text-center text-gray-550 dark:text-gray-400">{item.unit}</td>
                                                <td className="px-4 py-4 text-center">
                                                    {item.kondisi ? (
                                                        <span className={`inline-flex rounded-lg px-2.5 py-0.5 text-xs font-semibold ${KONDISI[item.kondisi] ?? ''}`}>
                                                            {item.kondisi}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-300 dark:text-gray-600">—</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4 text-right font-medium text-gray-650 dark:text-gray-400">{formatRupiah(item.hpp_price)}</td>
                                                <td className="px-4 py-4 text-right font-bold text-gray-850 dark:text-white/90">{formatRupiah(item.total)}</td>
                                                <td className="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">{item.notes || '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </>
                )}

                {/* Photos Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold text-gray-700 dark:text-gray-300">Foto Bukti Penerimaan</h3>
                        <span className="text-xs text-gray-400 dark:text-gray-550">{receiving.photos.length} / 10 foto</span>
                    </div>
                    {receiving.photos.length > 0 ? (
                        <div className="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 md:grid-cols-4">
                            {receiving.photos.map((photo) => (
                                <div key={photo.id} className="group relative rounded-xl overflow-hidden border border-gray-250 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                                    <img
                                        src={photo.url}
                                        alt={photo.caption ?? ''}
                                        onClick={() => window.open(photo.url, '_blank')}
                                        className="h-28 w-full cursor-pointer object-cover transition duration-300 group-hover:scale-105"
                                    />
                                    {photo.caption && (
                                        <div className="p-2 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900/90">
                                            <p className="truncate text-xs font-semibold text-gray-600 dark:text-gray-400">{photo.caption}</p>
                                        </div>
                                    )}
                                    {receiving.is_open && (
                                        <button
                                            onClick={() => deletePhoto(photo.id)}
                                            className="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white shadow-md opacity-0 transition-opacity duration-200 hover:bg-rose-700 group-hover:opacity-100"
                                        >
                                            ✕
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-10 text-center text-sm font-semibold text-gray-400 dark:text-gray-500">Belum ada foto bukti.</div>
                    )}
                    {receiving.is_open && receiving.photos.length < 10 && <PhotoUpload receiving={receiving} />}
                </div>
            </div>
        </GudangLayout>
    );
}

