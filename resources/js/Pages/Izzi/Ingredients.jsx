import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import { panel, input, button, Errors, Search, Paging } from './Common';
const blank = { code: '', name: '', unit: 'gram', purchase_unit: 'gram', purchase_factor: '1', is_active: true };
function IngredientForm({ ingredient, units, onClose }) {
    const form = useForm(ingredient ? { code: ingredient.code, name: ingredient.name, unit: ingredient.unit, purchase_unit: ingredient.purchase_unit, purchase_factor: ingredient.purchase_factor, is_active: ingredient.status === 'active' } : blank);
    return <form className={`${panel} space-y-4`} onSubmit={e => { e.preventDefault(); ingredient ? form.put(route('izzi.ingredients.update', ingredient.id), { onSuccess: onClose }) : form.post(route('izzi.ingredients.store'), { onSuccess: onClose }); }}>
        <h2 className="font-bold">{ingredient ? 'Edit bahan' : 'Tambah bahan'}</h2><Errors errors={form.errors} />
        <div className="grid gap-4 sm:grid-cols-2"><label>Kode bahan<input required maxLength={20} className={input} value={form.data.code} onChange={e => form.setData('code', e.target.value)} /></label><label>Nama bahan<input required maxLength={200} className={input} value={form.data.name} onChange={e => form.setData('name', e.target.value)} /></label></div>
        <label className="block">Satuan dasar resep dan stok<select className={input} value={form.data.unit} onChange={e => form.setData('unit', e.target.value)}>{units.map(u => <option key={u}>{u}</option>)}</select></label>
        <p className="text-sm text-gray-500">Satuan dasar ditetapkan saat bahan dibuat. Gunakan gram untuk berat, ml untuk cairan yang ditakar dalam ml, atau PCS untuk bahan utuh. SDM/porsi/potong boleh dipakai sebagai satuan dasar bila memang dihitung dalam satuan tersebut.</p>
        <div className="grid gap-4 sm:grid-cols-2"><label>Satuan saat diterima<input required maxLength={40} placeholder="Contoh: kemasan 2 kg" className={input} value={form.data.purchase_unit} onChange={e => form.setData('purchase_unit', e.target.value)} /></label><label>Isi 1 satuan penerimaan ({form.data.unit})<input required type="number" min="0.001" step="0.001" className={input} value={form.data.purchase_factor} onChange={e => form.setData('purchase_factor', e.target.value)} /></label></div>
        <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">1 {form.data.purchase_unit || 'kemasan'} = {form.data.purchase_factor || '?'} {form.data.unit}. Contoh: kemasan 2 kg berisi 2.000 gram. Masukkan angka 2000.</p>
        <label className="flex gap-2"><input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />Bahan aktif</label>
        <div className="flex gap-4"><button className={button} disabled={form.processing}>Simpan bahan</button><button type="button" onClick={onClose}>Tutup</button></div>
    </form>;
}
export default function Ingredients({ ingredients, filters, units }) {
    const [editing, setEditing] = useState(undefined);
    return <IzziLayout pageTitle="Bahan Izzi"><Head title="Bahan & Satuan Kemasan" /><div className="space-y-5 text-gray-800 dark:text-gray-200"><div className="flex justify-between"><h1 className="text-2xl font-bold">Bahan & Satuan Kemasan</h1><button className={button} onClick={() => setEditing(null)}>Tambah bahan</button></div>
        <p>Daftarkan seluruh bahan yang akan dipotong oleh resep. Takaran dan isi kemasan Anda isi sendiri.</p>
        {editing !== undefined && <IngredientForm key={editing?.id || 'new'} ingredient={editing} units={units} onClose={() => setEditing(undefined)} />}
        <div className={`${panel} space-y-4`}><Search path="izzi.ingredients" filters={filters} />{ingredients.data.map(p => <div key={p.id} className="flex flex-wrap justify-between gap-3 border-b py-3 dark:border-gray-700"><div><strong>{p.name}</strong><p>{p.code} · {p.status === 'active' ? 'Aktif' : 'Nonaktif'}</p><p>1 {p.purchase_unit} = {p.purchase_factor} {p.unit}</p></div><button className="text-brand-500" onClick={() => setEditing(p)}>Edit</button></div>)}<Paging rows={ingredients} /></div>
    </div></IzziLayout>;
}
