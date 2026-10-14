import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import { panel, button, Search, Paging, money } from './Common';
export default function Menus({ menus, filters }) {
    return <IzziLayout pageTitle="Menu & Resep Izzi"><Head title="Menu, Harga & Gramasi" /><div className="space-y-5 text-gray-800 dark:text-gray-200"><div className="flex justify-between"><h1 className="text-2xl font-bold">Menu, Harga & Gramasi</h1><Link className={button} href={route('izzi.menus.create')}>Tambah menu</Link></div><p>Setiap menu memiliki resep sendiri. Perubahan resep berlaku untuk penjualan berikutnya; transaksi lama tetap memakai catatan resep asal.</p>
        <div className={`${panel} space-y-4`}><Search path="izzi.menus" filters={filters} />{menus.data.map(m => <div className="flex flex-wrap justify-between gap-3 border-b py-3 dark:border-gray-700" key={m.id}><div><strong>{m.name}</strong><p>{m.code} · {m.category} · {m.is_active ? 'Aktif' : 'Nonaktif'}</p><p>{money(m.price)} · {m.ingredient_count} bahan · Resep versi {m.recipe_version}</p></div><Link className="text-brand-500" href={route('izzi.menus.edit', m.id)}>Edit menu & resep</Link></div>)}<Paging rows={menus} /></div>
    </div></IzziLayout>;
}
