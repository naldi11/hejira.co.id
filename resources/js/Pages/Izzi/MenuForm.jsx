import { Head, Link, useForm } from '@inertiajs/react';
import IngredientCatalogue, { useIngredients } from './IngredientCatalogue';
import IzziLayout from '@/Layouts/IzziLayout';
import SelectField from '@/Components/SelectField';
import { panel, input, button, Errors, money } from './Common';
const empty = () => ({ product_id: '', quantity: '' });
export default function MenuForm({ menu, ingredients: initial, catalogue: page, selectedIngredients = [], versions }) {
    const form = useForm(menu ? { code: menu.code, name: menu.name, category: menu.category, price: menu.price, is_active: Boolean(menu.is_active), recipe: menu.recipe } : { code: '', name: '', category: '', price: '', is_active: true, recipe: [empty()] });
    const { catalogue, ingredients } = useIngredients(initial, page, form.data.recipe.map(r => r.product_id), selectedIngredients);
    const set = (index, key, value) => form.setData('recipe', form.data.recipe.map((r,i) => i === index ? { ...r, [key]: value } : r));
    return <IzziLayout pageTitle="Atur Menu & Resep"><Head title="Atur Menu & Resep" /><div className="mx-auto max-w-4xl space-y-5 text-gray-800 dark:text-gray-200"><Link className="text-brand-500" href={route('izzi.menus')}>← Daftar menu</Link><h1 className="text-2xl font-bold">{menu ? 'Edit menu & resep' : 'Tambah menu & resep'}</h1>
        <form className={`${panel} space-y-4`} onSubmit={e => { e.preventDefault(); menu ? form.put(route('izzi.menus.update', menu.id)) : form.post(route('izzi.menus.store')); }}><Errors errors={form.errors} />
            <div className="grid gap-4 sm:grid-cols-2">{[['code','Kode menu'],['name','Nama menu'],['category','Kelompok menu']].map(([key,label]) => <label key={key}>{label}<input required className={input} value={form.data[key]} onChange={e => form.setData(key,e.target.value)} /></label>)}<label>Harga jual (Rp)<input required type="number" min="0" step="1" className={input} value={form.data.price} onChange={e => form.setData('price',e.target.value)} /></label></div>
            <h2 className="font-bold">Bahan untuk 1 menu</h2><p className="text-sm text-gray-500">Isi semua bahan dan takaran dalam satuan dasar bahan. Misalnya daging 40 gram dan tortilla 1 PCS. Sistem mengalikan resep ini dengan jumlah terjual.</p>
            <IngredientCatalogue catalogue={catalogue} />
            {ingredients.length === 0 && <p className="text-red-600">Tambahkan bahan melalui menu Bahan & Satuan Kemasan terlebih dahulu.</p>}
            {form.data.recipe.map((r,i) => { const ingredient = ingredients.find(p => String(p.id) === String(r.product_id)); return <div className="space-y-3 rounded-xl border p-4 dark:border-gray-700" key={i}><label className="block">Bahan {i+1}<SelectField required options={ingredients.map(p => ({ value: p.id, label: `${p.name} · ${p.code}`, sublabel: p.unit, isDisabled: p.status !== 'active' || form.data.recipe.some((other,n) => n !== i && String(other.product_id) === String(p.id)) }))} value={r.product_id} onChange={value => set(i,'product_id',value)} /></label><label className="block">Takaran untuk 1 menu ({ingredient?.unit || 'pilih bahan dahulu'})<input required type="number" min="0.001" step="0.001" className={input} value={r.quantity} onChange={e => set(i,'quantity',e.target.value)} /></label>{form.data.recipe.length > 1 && <button type="button" className="text-red-600" onClick={() => form.setData('recipe',form.data.recipe.filter((_,n) => n !== i))}>Hapus bahan</button>}</div>; })}
            <button type="button" disabled={form.data.recipe.length >= 100} className="text-brand-500" onClick={() => form.setData('recipe',[...form.data.recipe,empty()])}>+ Tambah bahan resep</button>
            <label className="flex gap-2"><input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active',e.target.checked)} />Menu aktif di kasir</label>
            <p className="text-sm text-gray-500">Penyimpanan membuat versi resep baru. Gramasi transaksi yang sudah terjadi tetap tersimpan.</p><button className={button} disabled={form.processing}>Simpan menu & resep</button>
        </form>
        {versions.length > 0 && <div className={`${panel} space-y-3`}><h2 className="font-bold">10 perubahan resep terbaru</h2>{versions.map(v => <details key={v.version}><summary>Versi {v.version} · {v.menu_name} · {money(v.price)} · {v.actor_name} · {v.created_at} WIB</summary><div className="mt-2 space-y-2">{v.recipe.map(r => <p key={r.product_id}>{r.product_name || `Bahan #${r.product_id}`} · {r.quantity} {r.unit_name || ''}</p>)}</div></details>)}</div>}
    </div></IzziLayout>;
}
