import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useRef } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const VISIBILITY = [
    { key: 'visible_gudang', label: 'Gudang', icon: 'warehouse' },
    { key: 'visible_jihans', label: "Jihan's", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys', icon: 'cake' },
];

export default function ProductForm({ categories, units, brands, product = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const p = product?.data ?? product;
    const isEdit = !!(p && p.id);
    const [preview, setPreview] = useState(p?.image_url ?? null);
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef(null);

    const triggerFileSelect = () => fileInputRef.current?.click();

    const clearPhoto = () => {
        setData({
            ...data,
            image: null,
            clear_image: true,
            image_url: ''
        });
        setPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
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
        const file = e.dataTransfer.files?.[0];
        if (file && file.type.startsWith('image/')) {
            setData({
                ...data,
                image: file,
                image_url: '',
                clear_image: false
            });
            setPreview(URL.createObjectURL(file));
        } else {
            // Dragged from another page / URL
            let imageUrl = '';
            
            // 1. Try to get image src from HTML first (this is the most reliable way to get the actual dragged image element's URL)
            const html = e.dataTransfer.getData('text/html');
            if (html) {
                // Look for src="..." in the HTML
                const match = html.match(/src="([^"]+)"/i);
                if (match && match[1]) {
                    imageUrl = match[1];
                }
            }
            
            // 2. Fallback to uri-list or plain text
            if (!imageUrl) {
                imageUrl = e.dataTransfer.getData('text/uri-list') || e.dataTransfer.getData('text/plain');
            }
            
            // 3. Try to extract imgurl parameter if it is a Google Images redirect URL
            if (imageUrl) {
                try {
                    const urlObj = new URL(imageUrl);
                    const imgUrlParam = urlObj.searchParams.get('imgurl');
                    if (imgUrlParam) {
                        imageUrl = decodeURIComponent(imgUrlParam);
                    }
                } catch (err) {
                    // Not a valid absolute URL, keep as is
                }
            }
            if (imageUrl && (imageUrl.startsWith('http://') || imageUrl.startsWith('https://') || imageUrl.startsWith('data:image/'))) {
                setData({
                    ...data,
                    image: null,
                    image_url: imageUrl,
                    clear_image: false
                });
                setPreview(imageUrl);
            }
        }
    };

    const { data, setData, post, processing, errors } = useForm({
        name: p?.name ?? '',
        barcode: p?.barcode ?? '',
        category_id: p?.category ?? '',
        unit_id: p?.unit_name ?? '',
        brand_id: p?.brand ?? '',
        rack: p?.rack ?? '',
        hpp: p?.hpp ?? 0,
        selling_price: p?.selling_price ?? 0,
        stock_min: p?.stock_min ?? 0,
        ppn_type: p?.ppn_type ?? 'none',
        ppn_rate: p?.ppn_rate ?? 11,
        product_type: p?.product_type ?? 'INV',
        source_type: p?.source_type ?? 'purchased',
        status: p?.status ?? 'active',
        notes: p?.notes ?? '',
        image: null,
        clear_image: false,
        image_url: '',
        visible_gudang: p ? p.visible_gudang : true,
        visible_jihans: p ? p.visible_jihans : false,
        visible_hendhys: p ? p.visible_hendhys : false,
        tiered_prices: p?.tiered_prices ?? [],
        _method: isEdit ? 'put' : 'post',
    });

    const onImage = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData({
                ...data,
                image: file,
                image_url: '',
                clear_image: false
            });
            setPreview(URL.createObjectURL(file));
        }
    };

    const addTier = () => setData('tiered_prices', [...data.tiered_prices, { min_qty: '', price: '' }]);
    const setTier = (i, patch) => setData('tiered_prices', data.tiered_prices.map((t, idx) => (idx === i ? { ...t, ...patch } : t)));
    const removeTier = (i) => setData('tiered_prices', data.tiered_prices.filter((_, idx) => idx !== i));

    const submit = (e) => {
        e.preventDefault();
        // Inertia turns _method=put + files into a spoofed multipart PUT.
        post(isEdit ? route(routePrefix + 'products.update', p.id) : route(routePrefix + 'products.store'), { forceFormData: true });
    };

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-855 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';
    const labelClass = 'mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400';
    const err = (k) => errors[k] && <p className="mt-1 text-xs font-semibold text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Produk' : 'Tambah Produk'} pageTitle="Master Data — Produk">
            <Head title={isEdit ? 'Edit Produk' : 'Tambah Produk'} />

            <datalist id="categories-list">{categories.map((c) => <option key={c.id ?? (c.name ?? c)} value={c.name ?? c} />)}</datalist>
            <datalist id="units-list">{units.map((u) => <option key={u.id ?? (u.name ?? u)} value={u.name ?? u} />)}</datalist>
            <datalist id="brands-list">{brands.map((b) => <option key={b.id ?? (b.name ?? b)} value={b.name ?? b} />)}</datalist>

            <form onSubmit={submit} className="w-full space-y-6 pb-20">

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Main */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Detail Info */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Informasi Produk</h3>
                            </div>
                            <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <label className={labelClass}>Nama Produk <span className="text-rose-500">*</span></label>
                                    <input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="cth: Gula Pasir Premium" className={inputClass} />
                                    {err('name')}
                                </div>
                                <div>
                                    <label className={labelClass}>Barcode</label>
                                    <input type="text" value={data.barcode} onChange={(e) => setData('barcode', e.target.value)} placeholder="Nomor barcode..." className={inputClass} />
                                    {err('barcode')}
                                </div>
                                <div>
                                    <label className={labelClass}>Rak / Posisi</label>
                                    <input type="text" value={data.rack} onChange={(e) => setData('rack', e.target.value)} placeholder="A-1-2..." className={inputClass} />
                                    {err('rack')}
                                </div>
                                <div>
                                    <label className={labelClass}>Kategori <span className="text-rose-500">*</span></label>
                                    <input list="categories-list" required value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} placeholder="Pilih atau ketik baru..." className={inputClass} />
                                    {err('category_id')}
                                </div>
                                <div>
                                    <label className={labelClass}>Satuan <span className="text-rose-500">*</span></label>
                                    <input list="units-list" required value={data.unit_id} onChange={(e) => setData('unit_id', e.target.value)} placeholder="Pilih atau ketik baru..." className={inputClass} />
                                    {err('unit_id')}
                                </div>
                                <div>
                                    <label className={labelClass}>Brand / Merek</label>
                                    <input list="brands-list" value={data.brand_id} onChange={(e) => setData('brand_id', e.target.value)} placeholder="Opsional..." className={inputClass} />
                                    {err('brand_id')}
                                </div>
                                <div>
                                    <label className={labelClass}>Stok Minimum <span className="text-rose-500">*</span></label>
                                    <input type="number" min="0" required value={data.stock_min} onChange={(e) => setData('stock_min', e.target.value)} className={inputClass} />
                                    {err('stock_min')}
                                </div>
                            </div>
                        </div>

                        {/* Pricing Info */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Harga & Pajak</h3>
                            </div>
                            <div className="space-y-6 p-6">
                                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label className={labelClass}>HPP (Modal) <span className="text-rose-500">*</span></label>
                                        <input type="number" min="0" required value={data.hpp} onChange={(e) => setData('hpp', e.target.value)} className={inputClass} />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Harga Jual <span className="text-rose-500">*</span></label>
                                        <input type="number" min="0" required value={data.selling_price} onChange={(e) => setData('selling_price', e.target.value)} className={inputClass} />
                                    </div>
                                    <div>
                                        <label className={labelClass}>Tipe PPN</label>
                                        <select value={data.ppn_type} onChange={(e) => setData('ppn_type', e.target.value)} className={inputClass}>
                                            <option value="none">Tanpa PPN</option>
                                            <option value="include">Include</option>
                                            <option value="exclude">Exclude</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className={labelClass}>Rate PPN (%)</label>
                                        <input type="number" min="0" max="100" step="0.01" value={data.ppn_rate} onChange={(e) => setData('ppn_rate', e.target.value)} className={inputClass} />
                                    </div>
                                </div>

                                <div className="border-t border-gray-150 pt-5 dark:border-gray-800">
                                    <div className="mb-4 flex items-center justify-between">
                                        <label className="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Harga Bertingkat (Grosir)</label>
                                        <Button type="button" onClick={addTier} variant="outline" size="sm" startIcon={<Icon name="add" className="text-[16px]" />}>
                                            Tambah Tier
                                        </Button>
                                    </div>
                                    <div className="space-y-3">
                                        {data.tiered_prices.length === 0 && (
                                            <p className="text-xs italic text-gray-400 dark:text-gray-500">Belum ada harga bertingkat.</p>
                                        )}
                                        {data.tiered_prices.map((t, i) => (
                                            <div key={i} className="flex items-center gap-3">
                                                <input type="number" min="1" placeholder="Min Qty" value={t.min_qty} onChange={(e) => setTier(i, { min_qty: e.target.value })} className={inputClass} />
                                                <input type="number" min="0" placeholder="Harga" value={t.price} onChange={(e) => setTier(i, { price: e.target.value })} className={inputClass} />
                                                <button type="button" onClick={() => removeTier(i)} className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-450 transition hover:bg-rose-50 hover:text-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-rose-950/30">
                                                    <Icon name="delete" className="text-[18px]" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Foto Produk */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Foto Produk</h3>
                            </div>
                            <div className="p-6">
                                {preview ? (
                                    <div className="relative aspect-square rounded-2xl overflow-hidden group border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/30 shadow-theme-xs">
                                        <img src={preview} alt="Preview" className="h-full w-full object-cover" />
                                        <div className="absolute inset-0 bg-black/60 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            <button 
                                                type="button" 
                                                onClick={triggerFileSelect}
                                                className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-gray-800 transition hover:bg-gray-100 hover:scale-110 shadow-md"
                                                title="Ubah Foto"
                                            >
                                                <Icon name="edit" className="text-[20px]" />
                                            </button>
                                            <button 
                                                type="button" 
                                                onClick={clearPhoto}
                                                className="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-600 text-white transition hover:bg-rose-700 hover:scale-110 shadow-md"
                                                title="Hapus Foto"
                                            >
                                                <Icon name="delete" className="text-[20px]" />
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <label 
                                        onDragOver={handleDragOver}
                                        onDragLeave={handleDragLeave}
                                        onDrop={handleDrop}
                                        onClick={triggerFileSelect}
                                        className={`flex aspect-square cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed transition-all duration-200 ${
                                            isDragging 
                                                ? 'border-brand-500 bg-brand-500/5 dark:border-brand-400 dark:bg-brand-500/10 scale-[1.02]' 
                                                : 'border-gray-200 bg-gray-50/50 hover:border-brand-500/50 dark:border-gray-700 dark:bg-gray-900/30 dark:hover:border-brand-400/50'
                                        }`}
                                    >
                                        <div className="text-center text-gray-400 dark:text-gray-500 p-4 pointer-events-none">
                                            <Icon name="add_a_photo" className="text-[32px] mb-2" />
                                            <p className="text-xs font-semibold leading-relaxed">
                                                {isDragging ? 'Lepas Foto di Sini' : 'Upload atau Drag & Drop Foto'}
                                            </p>
                                        </div>
                                    </label>
                                )}
                                <input 
                                    type="file" 
                                    ref={fileInputRef}
                                    accept="image/*" 
                                    className="hidden" 
                                    onChange={onImage} 
                                />
                                {err('image')}
                            </div>
                        </div>

                        {/* Status & Options */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Status & Visibilitas</h3>
                            </div>
                            <div className="space-y-4 p-6">
                                <div>
                                    <label className={labelClass}>Tipe Produk</label>
                                    <select value={data.product_type} onChange={(e) => setData('product_type', e.target.value)} className={inputClass}>
                                        <option value="INV">Inventori (Stok)</option>
                                        <option value="NON">Non-Inventori (Jasa)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className={labelClass}>Sumber Produk</label>
                                    <select value={data.source_type} onChange={(e) => setData('source_type', e.target.value)} className={inputClass}>
                                        <option value="purchased">Beli (Supplier)</option>
                                        <option value="produced">Produksi Sendiri</option>
                                    </select>
                                </div>
                                <div>
                                    <label className={labelClass}>Status</label>
                                    <select value={data.status} onChange={(e) => setData('status', e.target.value)} className={inputClass}>
                                        <option value="active">Aktif</option>
                                        <option value="discontinued">Discontinued</option>
                                    </select>
                                </div>
                                <div>
                                    <label className={labelClass}>Visibilitas Entitas</label>
                                    <div className="mt-2 grid grid-cols-3 gap-2">
                                        {VISIBILITY.map((v) => {
                                            const on = data[v.key];
                                            return (
                                                <button
                                                    type="button"
                                                    key={v.key}
                                                    onClick={() => setData(v.key, !on)}
                                                    className={`flex flex-col items-center gap-1 rounded-xl border p-2.5 transition-all text-center ${on ? 'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-850 dark:bg-brand-500/10 dark:text-brand-400' : 'border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-500'}`}
                                                >
                                                    <Icon name={v.icon} className="text-[18px]" />
                                                    <span className="text-[9px] font-bold uppercase tracking-wider">{v.label}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                                <div>
                                    <label className={labelClass}>Catatan</label>
                                    <textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Catatan produk..." className={areaClass} />
                                    {err('notes')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <Link href={route(routePrefix + 'products.index')}>
                        <Button type="button" variant="outline" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                            Kembali ke Daftar
                        </Button>
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Produk'}
                    </Button>
                </div>
            </form>
        </Layout>
    );
}

