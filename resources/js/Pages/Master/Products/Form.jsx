import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import JihansLayout from '@/Layouts/JihansLayout';
import HendhysLayout from '@/Layouts/HendhysLayout';

const Layouts = { GudangLayout, JihansLayout, HendhysLayout };
import Icon from '@/Components/Icon';

const route = window.route;

const VISIBILITY = [
    { key: 'visible_gudang', label: 'Gudang', icon: 'warehouse' },
    { key: 'visible_jihans', label: "Jihan's", icon: 'storefront' },
    { key: 'visible_hendhys', label: 'Hendhys', icon: 'cake' },
];

export default function ProductForm({ categories, units, brands, product = null , layout = 'GudangLayout', routePrefix = 'master.'}) {
    const Layout = Layouts[layout] || (({ children }) => <div>{children}</div>);
    const isEdit = !!product;
    const [preview, setPreview] = useState(product?.image_url ?? null);

    const { data, setData, post, processing, errors } = useForm({
        name: product?.name ?? '',
        barcode: product?.barcode ?? '',
        category_id: product?.category ?? '',
        unit_id: product?.unit_name ?? '',
        brand_id: product?.brand ?? '',
        rack: product?.rack ?? '',
        hpp: product?.hpp ?? 0,
        selling_price: product?.selling_price ?? 0,
        stock_min: product?.stock_min ?? 0,
        ppn_type: product?.ppn_type ?? 'none',
        ppn_rate: product?.ppn_rate ?? 11,
        product_type: product?.product_type ?? 'INV',
        source_type: product?.source_type ?? 'purchased',
        status: product?.status ?? 'active',
        notes: product?.notes ?? '',
        image: null,
        visible_gudang: product ? product.visible_gudang : true,
        visible_jihans: product ? product.visible_jihans : false,
        visible_hendhys: product ? product.visible_hendhys : false,
        tiered_prices: product?.tiered_prices ?? [],
        _method: isEdit ? 'put' : 'post',
    });

    const onImage = (e) => {
        const file = e.target.files[0];
        setData('image', file ?? null);
        if (file) setPreview(URL.createObjectURL(file));
    };

    const addTier = () => setData('tiered_prices', [...data.tiered_prices, { min_qty: '', price: '' }]);
    const setTier = (i, patch) => setData('tiered_prices', data.tiered_prices.map((t, idx) => (idx === i ? { ...t, ...patch } : t)));
    const removeTier = (i) => setData('tiered_prices', data.tiered_prices.filter((_, idx) => idx !== i));

    const submit = (e) => {
        e.preventDefault();
        // Inertia turns _method=put + files into a spoofed multipart PUT.
        post(isEdit ? route(routePrefix + 'products.update', product.id) : route(routePrefix + 'products.store'), { forceFormData: true });
    };

    const field = 'w-full rounded-xl border-2 border-slate-100 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-700 outline-none transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10';
    const label = 'mb-1.5 ml-1 block text-xs font-black uppercase tracking-widest text-slate-500';
    const err = (k) => errors[k] && <p className="ml-1 mt-1 text-[10px] font-bold uppercase text-rose-500">{errors[k]}</p>;

    return (
        <Layout title={isEdit ? 'Edit Produk' : 'Tambah Produk'} pageTitle="Master Data — Produk">
            <Head title={isEdit ? 'Edit Produk' : 'Tambah Produk'} />

            <datalist id="categories-list">{categories.map((c) => <option key={c} value={c} />)}</datalist>
            <datalist id="units-list">{units.map((u) => <option key={u.name} value={u.name} />)}</datalist>
            <datalist id="brands-list">{brands.map((b) => <option key={b} value={b} />)}</datalist>

            <form onSubmit={submit} className="mx-auto max-w-5xl space-y-6 pb-12">
                <div className="flex items-center justify-between">
                    <Link href={route(routePrefix + 'products.index')} className="group inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition-colors hover:text-slate-900">
                        <Icon name="arrow_back" className="text-[18px] transition-transform group-hover:-translate-x-1" /> Kembali
                    </Link>
                    <h2 className="font-headline text-xl font-black tracking-tight text-slate-800">{isEdit ? `Edit ${product.code}` : 'Produk Baru'}</h2>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Main */}
                    <div className="space-y-6 lg:col-span-2">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 className="mb-5 text-sm font-bold uppercase tracking-wider text-slate-700">Informasi Produk</h3>
                            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div className="md:col-span-2"><label className={label}>Nama Produk <span className="text-rose-500">*</span></label><input type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} className={field} />{err('name')}</div>
                                <div><label className={label}>Barcode</label><input type="text" value={data.barcode} onChange={(e) => setData('barcode', e.target.value)} className={field} />{err('barcode')}</div>
                                <div><label className={label}>Rak</label><input type="text" value={data.rack} onChange={(e) => setData('rack', e.target.value)} className={field} /></div>
                                <div><label className={label}>Kategori <span className="text-rose-500">*</span></label><input list="categories-list" required value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} placeholder="Pilih atau ketik baru..." className={field} />{err('category_id')}</div>
                                <div><label className={label}>Satuan <span className="text-rose-500">*</span></label><input list="units-list" required value={data.unit_id} onChange={(e) => setData('unit_id', e.target.value)} placeholder="Pilih atau ketik baru..." className={field} />{err('unit_id')}</div>
                                <div><label className={label}>Brand</label><input list="brands-list" value={data.brand_id} onChange={(e) => setData('brand_id', e.target.value)} placeholder="Opsional..." className={field} /></div>
                                <div><label className={label}>Stok Minimum <span className="text-rose-500">*</span></label><input type="number" min="0" required value={data.stock_min} onChange={(e) => setData('stock_min', e.target.value)} className={field} /></div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 className="mb-5 text-sm font-bold uppercase tracking-wider text-slate-700">Harga & Pajak</h3>
                            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div><label className={label}>HPP (Modal) <span className="text-rose-500">*</span></label><input type="number" min="0" required value={data.hpp} onChange={(e) => setData('hpp', e.target.value)} className={field} /></div>
                                <div><label className={label}>Harga Jual <span className="text-rose-500">*</span></label><input type="number" min="0" required value={data.selling_price} onChange={(e) => setData('selling_price', e.target.value)} className={field} /></div>
                                <div><label className={label}>Tipe PPN</label><select value={data.ppn_type} onChange={(e) => setData('ppn_type', e.target.value)} className={field}><option value="none">Tanpa PPN</option><option value="include">Include</option><option value="exclude">Exclude</option></select></div>
                                <div><label className={label}>Rate PPN (%)</label><input type="number" min="0" max="100" step="0.01" value={data.ppn_rate} onChange={(e) => setData('ppn_rate', e.target.value)} className={field} /></div>
                            </div>

                            <div className="mt-6 border-t border-slate-100 pt-5">
                                <div className="mb-3 flex items-center justify-between">
                                    <label className={`${label} mb-0`}>Harga Bertingkat (Grosir)</label>
                                    <button type="button" onClick={addTier} className="flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800"><Icon name="add" className="text-[16px]" /> Tambah Tier</button>
                                </div>
                                <div className="space-y-2">
                                    {data.tiered_prices.length === 0 && <p className="text-xs italic text-slate-400">Belum ada harga bertingkat.</p>}
                                    {data.tiered_prices.map((t, i) => (
                                        <div key={i} className="flex items-center gap-2">
                                            <input type="number" min="1" placeholder="Min Qty" value={t.min_qty} onChange={(e) => setTier(i, { min_qty: e.target.value })} className={`${field} flex-1`} />
                                            <input type="number" min="0" placeholder="Harga" value={t.price} onChange={(e) => setTier(i, { price: e.target.value })} className={`${field} flex-1`} />
                                            <button type="button" onClick={() => removeTier(i)} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-300 hover:bg-rose-50 hover:text-rose-500"><Icon name="delete" className="text-[18px]" /></button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <label className={label}>Foto Produk</label>
                            <label className="mt-2 flex aspect-square cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 transition-all hover:border-indigo-300">
                                {preview ? <img src={preview} alt="" className="h-full w-full object-cover" /> : <div className="text-center text-slate-400"><Icon name="add_a_photo" className="text-[36px]" /><p className="mt-2 text-xs font-bold">Upload Foto</p></div>}
                                <input type="file" accept="image/*" className="hidden" onChange={onImage} />
                            </label>
                            {err('image')}
                        </div>

                        <div className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div><label className={label}>Tipe Produk</label><select value={data.product_type} onChange={(e) => setData('product_type', e.target.value)} className={field}><option value="INV">Inventori (Stok)</option><option value="NON">Non-Inventori (Jasa)</option></select></div>
                            <div><label className={label}>Sumber</label><select value={data.source_type} onChange={(e) => setData('source_type', e.target.value)} className={field}><option value="purchased">Beli (Supplier)</option><option value="produced">Produksi Sendiri</option></select></div>
                            <div><label className={label}>Status</label><select value={data.status} onChange={(e) => setData('status', e.target.value)} className={field}><option value="active">Aktif</option><option value="discontinued">Discontinued</option></select></div>
                            <div>
                                <label className={label}>Visibilitas Entitas</label>
                                <div className="mt-2 grid grid-cols-3 gap-2">
                                    {VISIBILITY.map((v) => {
                                        const on = data[v.key];
                                        return (
                                            <button type="button" key={v.key} onClick={() => setData(v.key, !on)} className={`flex flex-col items-center gap-1 rounded-xl border-2 p-3 transition-all ${on ? 'border-indigo-600 bg-indigo-50 text-indigo-600' : 'border-slate-100 bg-slate-50 text-slate-400'}`}>
                                                <Icon name={v.icon} filled={on} className="text-[20px]" />
                                                <span className="text-[9px] font-black uppercase">{v.label}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                            <div><label className={label}>Catatan</label><textarea rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={`${field} resize-none`} /></div>
                        </div>
                    </div>
                </div>

                <button type="submit" disabled={processing} className="w-full rounded-2xl bg-slate-900 px-8 py-4 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/10 transition-all hover:bg-indigo-600 disabled:opacity-50">
                    {processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Produk'}
                </button>
            </form>
        </Layout>
    );
}
