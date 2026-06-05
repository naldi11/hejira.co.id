import { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatDate } from '@/lib/format';

const route = window.route;

export default function Receive({ transferOut, currentScope, info }) {
    const Layout = currentScope === 'jihans' ? JihansLayout : HendhysLayout;

    // Theme values dynamic resolution
    const isJihans = currentScope === 'jihans';
    const textColor = isJihans ? 'text-orange-500 dark:text-orange-400' : 'text-amber-500 dark:text-amber-400';
    const bgColor = isJihans ? 'bg-orange-500 hover:bg-orange-600' : 'bg-amber-500 hover:bg-amber-600';
    const lightBg = isJihans ? 'bg-orange-50 dark:bg-orange-950/20' : 'bg-amber-50 dark:bg-amber-950/20';
    const borderTheme = isJihans ? 'border-orange-100 dark:border-orange-900/40' : 'border-amber-100 dark:border-amber-900/40';

    // Build initial state for each item detail
    const initializeState = () => {
        const bagus = {};
        const rusak = {};
        const batch = {};
        const exp = {};
        transferOut.details?.forEach(detail => {
            const qty = Number(detail.quantity) || 0;
            bagus[detail.id] = qty;
            rusak[detail.id] = 0;
            batch[detail.id] = '';
            exp[detail.id] = '';
        });
        return { bagus, rusak, batch, exp };
    };

    const initialData = initializeState();

    const { data, setData, post, processing, errors } = useForm({
        quantity_bagus: initialData.bagus,
        quantity_rusak: initialData.rusak,
        batch_number: initialData.batch,
        expired_date: initialData.exp,
        receive_notes: '',
        photos: [],
    });

    const [previews, setPreviews] = useState([]);
    const [isDragging, setIsDragging] = useState(false);

    // Update previews dynamically when files change
    useEffect(() => {
        const filePreviews = data.photos.map((f) => URL.createObjectURL(f));
        setPreviews(filePreviews);
        return () => filePreviews.forEach((url) => URL.revokeObjectURL(url));
    }, [data.photos]);

    const handleQtyBagusChange = (detailId, qtySent, value) => {
        let numVal = parseFloat(value);
        if (isNaN(numVal)) {
            setData('quantity_bagus', { ...data.quantity_bagus, [detailId]: value });
            return;
        }
        if (numVal < 0) numVal = 0;
        if (numVal > qtySent) numVal = qtySent;

        const remaining = parseFloat((qtySent - numVal).toFixed(3));
        setData({
            ...data,
            quantity_bagus: { ...data.quantity_bagus, [detailId]: numVal },
            quantity_rusak: { ...data.quantity_rusak, [detailId]: remaining }
        });
    };

    const handleQtyRusakChange = (detailId, qtySent, value) => {
        let numVal = parseFloat(value);
        if (isNaN(numVal)) {
            setData('quantity_rusak', { ...data.quantity_rusak, [detailId]: value });
            return;
        }
        if (numVal < 0) numVal = 0;
        if (numVal > qtySent) numVal = qtySent;

        const remaining = parseFloat((qtySent - numVal).toFixed(3));
        setData({
            ...data,
            quantity_rusak: { ...data.quantity_rusak, [detailId]: numVal },
            quantity_bagus: { ...data.quantity_bagus, [detailId]: remaining }
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
        }
    };

    const onPhotos = (e) => {
        const files = Array.from(e.target.files);
        setData('photos', [...data.photos, ...files]);
        e.target.value = '';
    };

    const removePhoto = (i) => {
        setData('photos', data.photos.filter((_, idx) => idx !== i));
    };

    const submit = (e) => {
        e.preventDefault();
        post(route(info.receiveRoute, transferOut.id), { forceFormData: true });
    };

    return (
        <Layout pageTitle="Konfirmasi Penerimaan Barang">
            <Head title="Konfirmasi Penerimaan" />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6 pb-20">
                
                {/* Title Section */}
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">Konfirmasi Penerimaan Barang</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Verifikasi fisik barang yang diterima dari Gudang Utama</p>
                </div>

                {/* Validation Errors */}
                {Object.keys(errors).length > 0 && (
                    <div className="rounded-2xl border border-rose-100 bg-rose-50 p-5 dark:border-rose-950/40 dark:bg-rose-950/20 text-rose-700 dark:text-rose-350 shadow-theme-xs">
                        <div className="flex items-center gap-3 mb-2 text-rose-600 dark:text-rose-400">
                            <Icon name="warning" className="text-[20px]" />
                            <span className="text-sm font-bold uppercase tracking-wider">Kesalahan Input</span>
                        </div>
                        <ul className="list-disc list-inside space-y-1 text-xs font-semibold opacity-90">
                            {Object.entries(errors).map(([key, val]) => (
                                <li key={key}>{val}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Info Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] space-y-6">
                    <div className="flex items-center gap-4">
                        <div className={`w-12 h-12 rounded-2xl ${lightBg} ${textColor} flex items-center justify-center border ${borderTheme} shadow-inner`}>
                            <Icon name="local_shipping" className="text-[28px]" />
                        </div>
                        <div>
                            <p className="text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider">Informasi Pengiriman</p>
                            <h3 className="text-base font-bold text-gray-850 dark:text-white/90">Dari Gudang Utama</h3>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div className="p-4 bg-gray-50 dark:bg-white/[0.02] rounded-2xl border border-gray-150 dark:border-gray-800">
                            <p className="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">No. Transfer</p>
                            <p className="text-sm font-mono font-bold text-gray-800 dark:text-white/90 mt-1">{transferOut.transfer_number}</p>
                        </div>
                        <div className="p-4 bg-gray-50 dark:bg-white/[0.02] rounded-2xl border border-gray-150 dark:border-gray-800">
                            <p className="text-[9px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest">Tanggal Kirim</p>
                            <p className="text-sm font-bold text-gray-800 dark:text-white/90 mt-1">{formatDate(transferOut.date)}</p>
                        </div>
                        <div className="p-4 bg-gray-50 dark:bg-white/[0.02] rounded-2xl border border-gray-150 dark:border-gray-800">
                            <p className="text-[9px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest">Dikirim Oleh</p>
                            <p className="text-sm font-bold text-gray-800 dark:text-white/90 mt-1 truncate">{transferOut.creator?.name || '-'}</p>
                        </div>
                    </div>
                </div>

                {/* Items Table Card */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.01]">
                        <h3 className="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Daftar Barang — Verifikasi Fisik</h3>
                    </div>
                    <div className="overflow-x-auto custom-scrollbar">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-gray-250 bg-gray-50 text-[10px] font-bold text-gray-450 dark:text-gray-500 uppercase tracking-wider dark:border-gray-800 dark:bg-white/[0.02]">
                                    <th className="px-6 py-4">Produk</th>
                                    <th className="px-4 py-4 text-center">Qty Kirim</th>
                                    <th className="px-4 py-4 text-center w-32">Qty Bagus</th>
                                    <th className="px-4 py-4 text-center w-32">Qty Rusak</th>
                                    <th className="px-4 py-4 text-center">Satuan</th>
                                    <th className="px-6 py-4 text-center w-48">Batch / Expired</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {transferOut.details?.map((detail) => {
                                    const qtySent = Number(detail.quantity) || 0;
                                    return (
                                        <tr key={detail.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                            <td className="px-6 py-4">
                                                <span className="font-bold text-gray-850 dark:text-white/90">{detail.product?.name}</span>
                                            </td>
                                            <td className="px-4 py-4 text-center">
                                                <span className="inline-block text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    {qtySent}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <input 
                                                    type="number" 
                                                    value={data.quantity_bagus[detail.id] ?? ''} 
                                                    onChange={(e) => handleQtyBagusChange(detail.id, qtySent, e.target.value)} 
                                                    min="0" 
                                                    max={qtySent} 
                                                    step="any" 
                                                    required
                                                    className="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-center text-gray-850 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:border-orange-400 dark:focus:border-orange-500 outline-none transition-all shadow-inner"
                                                />
                                            </td>
                                            <td className="px-4 py-4">
                                                <input 
                                                    type="number" 
                                                    value={data.quantity_rusak[detail.id] ?? ''} 
                                                    onChange={(e) => handleQtyRusakChange(detail.id, qtySent, e.target.value)} 
                                                    min="0" 
                                                    max={qtySent} 
                                                    step="any" 
                                                    required
                                                    className="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-center text-gray-855 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:border-orange-400 dark:focus:border-orange-500 outline-none transition-all shadow-inner"
                                                />
                                            </td>
                                            <td className="px-4 py-4 text-center">
                                                <span className="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase">
                                                    {detail.unit?.abbreviation || 'PCS'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 space-y-2">
                                                <input 
                                                    type="text" 
                                                    value={data.batch_number[detail.id] || ''} 
                                                    onChange={(e) => setData('batch_number', { ...data.batch_number, [detail.id]: e.target.value })} 
                                                    placeholder="No Batch"
                                                    className="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-250 dark:border-gray-700 rounded-lg text-[10px] font-semibold text-gray-700 dark:text-gray-300 focus:bg-white dark:focus:bg-gray-900 focus:border-orange-400 dark:focus:border-orange-500 outline-none uppercase"
                                                />
                                                <input 
                                                    type="date" 
                                                    value={data.expired_date[detail.id] || ''} 
                                                    onChange={(e) => setData('expired_date', { ...data.expired_date, [detail.id]: e.target.value })} 
                                                    className="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-250 dark:border-gray-700 rounded-lg text-[10px] font-semibold text-gray-705 dark:text-gray-300 focus:bg-white dark:focus:bg-gray-900 focus:border-orange-400 dark:focus:border-orange-500 outline-none"
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* File Uploads + Notes Card */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Notes Section */}
                        <div className="space-y-4">
                            <div>
                                <label className="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan Penerimaan</label>
                                <textarea 
                                    value={data.receive_notes} 
                                    onChange={(e) => setData('receive_notes', e.target.value)} 
                                    rows={3} 
                                    placeholder="Tulis catatan jika ada barang rusak atau kurang..."
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-orange-500 dark:text-white/90 dark:bg-gray-900/50 resize-none placeholder-gray-400 dark:placeholder-gray-550"
                                />
                            </div>
                        </div>

                        {/* File Upload Section */}
                        <div className="space-y-3">
                            <label className="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unggah Foto Bukti / Surat Jalan</label>
                            
                            <div 
                                className={`border-2 border-dashed rounded-xl p-6 transition-all duration-200 flex flex-col items-center justify-center cursor-pointer text-center relative ${
                                    isDragging 
                                        ? 'border-orange-500 bg-orange-500/5 dark:border-orange-500 dark:bg-orange-500/10' 
                                        : 'border-gray-300 hover:border-orange-500 dark:border-gray-700 dark:hover:border-orange-550 bg-gray-50/50 dark:bg-gray-900/30'
                                }`}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDrop}
                                onClick={() => document.getElementById('photo-upload-input').click()}
                            >
                                <input 
                                    type="file" 
                                    id="photo-upload-input"
                                    multiple 
                                    accept="image/*" 
                                    className="hidden" 
                                    onChange={onPhotos} 
                                />
                                <Icon name="add_a_photo" className="mb-2 text-[32px] text-gray-450 dark:text-gray-600" />
                                <p className="text-xs text-gray-700 dark:text-gray-300 font-bold">Klik atau seret foto di sini</p>
                                <p className="text-[10px] text-gray-450 mt-1">Maksimal 10 file gambar (maks 5MB/foto)</p>
                            </div>

                            {/* Previews Grid */}
                            {previews.length > 0 && (
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                                    {previews.map((url, idx) => (
                                        <div key={idx} className="relative group aspect-square rounded-xl overflow-hidden border border-gray-250 dark:border-gray-800 bg-gray-100 dark:bg-white/[0.02]">
                                            <img src={url} className="w-full h-full object-cover" alt="" />
                                            <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <button 
                                                    type="button" 
                                                    onClick={(e) => { e.stopPropagation(); removePhoto(idx); }} 
                                                    className="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-500 transition-colors shadow-lg"
                                                >
                                                    <Icon name="close" className="text-[16px]" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Form Buttons row at the bottom */}
                <div className="flex justify-end gap-3 mt-6">
                    <Link 
                        href={route(`${info.transferRoute}index`)} 
                        className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors inline-flex items-center gap-1.5"
                    >
                        <Icon name="arrow_back" className="text-[18px]" /> Kembali
                    </Link>
                    <button 
                        type="submit" 
                        disabled={processing}
                        className={`rounded-xl px-8 py-2.5 text-sm font-bold text-white shadow-sm transition-all ${bgColor} disabled:opacity-50`}
                    >
                        {processing ? 'Menyimpan...' : 'Konfirmasi & Simpan Stok'}
                    </button>
                </div>

            </form>
        </Layout>
    );
}
