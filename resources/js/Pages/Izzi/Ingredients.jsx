import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import { panel, input, button, Errors, Search, Paging } from './Common';

function IngredientForm({ ingredient, onClose }) {
    const form = useForm({ product_id: ingredient.id, purchase_unit: ingredient.purchase_unit, purchase_factor: ingredient.purchase_factor });
    return <form className={`${panel} space-y-4`} onSubmit={e => {
        e.preventDefault();
        form.put(route('izzi.ingredients.update', ingredient.id), { onSuccess: onClose });
    }}>
        <h2 className="font-bold">Sesuaikan kemasan untuk Izzi</h2><Errors errors={form.errors} />
        <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
            <strong>{ingredient.name}</strong><p>{ingredient.code} · Satuan master: <strong>{ingredient.unit}</strong></p>
            <p className="mt-2 text-sm text-gray-500">Produk ini memakai master yang sama dengan Gudang, Jihans, dan Hendhys. Kode, nama, serta satuannya mengikuti master produk.</p>
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
            <label>Nama kemasan saat diterima<input required maxLength={40} placeholder="Contoh: dus, botol, atau kemasan" className={input} value={form.data.purchase_unit} onChange={e => form.setData('purchase_unit', e.target.value)} /></label>
            <label>Isi 1 kemasan dalam {ingredient.unit}<input required type="number" min="0.001" step="0.001" className={input} value={form.data.purchase_factor} onChange={e => form.setData('purchase_factor', e.target.value)} /></label>
        </div>
        <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">1 {form.data.purchase_unit || 'kemasan'} = {form.data.purchase_factor || '?'} {ingredient.unit}. Isi sesuai kemasan yang benar-benar digunakan. Stok dan takaran resep tetap memakai {ingredient.unit}.</p>
        <p className="text-sm text-gray-500">Penyesuaian ini digunakan saat penerimaan langsung dari pemasok. Mutasi Gudang ke Izzi mengikuti jumlah dalam satuan master.</p>
        <div className="flex gap-4"><button className={button} disabled={form.processing}>Simpan penyesuaian</button><button type="button" onClick={onClose}>Tutup</button></div>
    </form>;
}

export default function Ingredients({ ingredients, filters }) {
    const [editing, setEditing] = useState(null);
    return <IzziLayout pageTitle="Penyesuaian Bahan Izzi"><Head title="Penyesuaian Bahan & Kemasan" />
        <div className="space-y-5 text-gray-800 dark:text-gray-200">
            <h1 className="text-2xl font-bold">Penyesuaian Bahan & Kemasan</h1>
            <p>Pilih produk yang sudah ada di master bersama. Pengguna cukup menentukan kemasan penerimaan dan takaran pada resep. Untuk menerima stok dari Gudang, gunakan Mutasi Stok Cabang & Gudang.</p>
            {editing && <IngredientForm key={editing.id} ingredient={editing} onClose={() => setEditing(null)} />}
            <div className={`${panel} space-y-4`}>
                <Search path="izzi.ingredients" filters={filters} />
                {ingredients.data.map(p => <div key={p.id} className="flex flex-wrap justify-between gap-3 border-b py-3 dark:border-gray-700">
                    <div><strong>{p.name}</strong><p>{p.code} · Satuan master: {p.unit} · {p.status === 'active' ? 'Aktif' : 'Nonaktif'}</p>
                        <p>{p.configured ? `1 ${p.purchase_unit} = ${p.purchase_factor} ${p.unit}` : `Mengikuti satuan master ${p.unit}; kemasan tambahan dapat disesuaikan.`}</p>
                    </div>
                    <button type="button" className="text-brand-500 disabled:text-gray-400" disabled={p.status !== 'active'} onClick={() => setEditing(p)}>Sesuaikan kemasan</button>
                </div>)}
                <Paging rows={ingredients} />
            </div>
        </div>
    </IzziLayout>;
}
