import { useRef } from 'react';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import CataloguePages from '@/Components/CataloguePages';
import { input } from './Common';
export function useIngredients(initial, page, selectedIds, selected = []) {
    const catalogue = usePosCatalogue(route('izzi.ingredients'), initial, page);
    const known = useRef(new Map([...initial, ...selected].map(p => [String(p.id), p])));
    for (const p of catalogue.products) known.current.set(String(p.id), p);
    const choices = new Map(catalogue.products.map(p => [String(p.id), p]));
    for (const id of selectedIds) if (known.current.has(String(id))) choices.set(String(id), known.current.get(String(id)));
    return { catalogue, ingredients: [...choices.values()] };
}
export default function IngredientCatalogue({ catalogue }) {
    return <div className="rounded-xl border p-3 dark:border-gray-700"><label className="block">Cari bahan di seluruh data<input type="search" className={input} placeholder="Nama atau kode bahan…" value={catalogue.query} onChange={e => catalogue.setQuery(e.target.value)} /></label><CataloguePages catalogue={catalogue} /></div>;
}
