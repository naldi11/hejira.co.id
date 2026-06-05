import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';

const route = window.route;

export default function ProductionsCreate({ products, units }) {
    const { data, setData, post, processing, errors } = useForm({ 
        date: new Date().toISOString().slice(0, 10), 
        notes: '', 
        items: [{ product_id: '', quantity_produced: 1, unit_id: '', search_text: '' }] 
    });

    const addItem = () => setData('items', [...data.items, { product_id: '', quantity_produced: 1, unit_id: '', search_text: '' }]);
    const removeItem = (i) => setData('items', data.items.filter((_, idx) => idx !== i));
    const updateItem = (i, field, value) => { 
        const items = [...data.items]; 
        items[i][field] = value; 
        setData('items', items); 
    };

    const onProductInputChange = (i, val) => {
        const p = products.find(pr => `${pr.name} (${pr.code})` === val);
        if (p) {
            setData('items', data.items.map((it, idx) => (idx === i ? {
                ...it,
                search_text: val,
                product_id: p.id,
                unit_id: p.unit_id
            } : it)));
        } else {
            setData('items', data.items.map((it, idx) => (idx === i ? {
                ...it,
                search_text: val,
                product_id: '',
                unit_id: ''
            } : it)));
        }
    };
    
    const submit = (e) => { 
        e.preventDefault(); 
        post(route('hendhys.productions.store')); 
    };

    const fieldClass = "mt-1.5 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3.5 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition-all shadow-theme-xs";

    return (
        <HendhysLayout pageTitle="Catat Produksi">
            <Head title="Catat Produksi" />

            <datalist id="products-list">
                {products.map((p) => (
                    <option key={p.id} value={`${p.name} (${p.code})`} />
                ))}
            </datalist>

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6">
                <h2 className="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">Catat Produksi Baru</h2>
                
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs space-y-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                            <input 
                                type="date" 
                                value={data.date} 
                                onChange={(e) => setData('date', e.target.value)} 
                                className={fieldClass} 
                            />
                            {errors.date && <p className="mt-1.5 text-xs text-red-500 dark:text-red-400">{errors.date}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                            <input 
                                type="text" 
                                value={data.notes} 
                                onChange={(e) => setData('notes', e.target.value)} 
                                className={fieldClass} 
                                placeholder="Opsional" 
                            />
                        </div>
                    </div>

                    <div className="pt-5 border-t border-gray-150 dark:border-gray-800">
                        <h3 className="font-bold text-gray-800 dark:text-white/90 mb-4 text-base">Item Produksi</h3>
                        
                        <div className="space-y-3">
                            {data.items.map((item, i) => (
                                <div key={i} className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                                    <div className="min-w-[200px] flex-1">
                                        <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Produk</label>
                                        <input 
                                            type="text" 
                                            list="products-list"
                                            value={item.search_text ?? ''} 
                                            onChange={(e) => onProductInputChange(i, e.target.value)} 
                                            placeholder="Cari & pilih produk..."
                                            required 
                                            className={fieldClass} 
                                        />
                                    </div>
                                    <div className="w-32">
                                        <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Qty</label>
                                        <input 
                                            type="number" 
                                            min="1" 
                                            value={item.quantity_produced} 
                                            onChange={(e) => updateItem(i, 'quantity_produced', e.target.value)} 
                                            className={fieldClass} 
                                        />
                                    </div>
                                    <div className="w-32">
                                        <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Satuan</label>
                                        <select 
                                            value={item.unit_id} 
                                            onChange={(e) => updateItem(i, 'unit_id', e.target.value)} 
                                            className={fieldClass}
                                        >
                                            <option value="" className="dark:bg-gray-800">—</option>
                                            {units.map((u) => (
                                                <option key={u.id} value={u.id} className="dark:bg-gray-800">
                                                    {u.abbreviation}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    {data.items.length > 1 && (
                                        <button 
                                            type="button" 
                                            onClick={() => removeItem(i)} 
                                            className="rounded-xl bg-red-50 p-2.5 text-red-500 hover:bg-red-100 transition-colors dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20 h-[42px] w-[42px] flex items-center justify-center border border-transparent dark:border-red-500/10 shadow-sm"
                                        >
                                            <Icon name="delete" className="text-[20px]" />
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>

                        <div className="mt-4">
                            <button 
                                type="button" 
                                onClick={addItem} 
                                className="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/[0.02] hover:border-amber-500 dark:hover:border-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-all cursor-pointer shadow-theme-xs"
                            >
                                <Icon name="add" className="text-[18px]" /> Tambah Item
                            </button>
                        </div>
                    </div>
                    {errors.items && <p className="text-sm text-red-500 dark:text-red-400 mt-2">{errors.items}</p>}
                </div>

                <div className="flex justify-end gap-3">
                    <button 
                        type="button" 
                        onClick={() => window.history.back()} 
                        className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-2.5 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.01] transition-colors shadow-theme-xs"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        disabled={processing} 
                        className="rounded-xl bg-amber-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none"
                    >
                        {processing ? 'Menyimpan...' : 'Simpan Produksi'}
                    </button>
                </div>
            </form>
        </HendhysLayout>
    );
}
