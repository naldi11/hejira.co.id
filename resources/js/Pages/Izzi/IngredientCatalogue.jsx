import { useRef } from 'react';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import CataloguePages from '@/Components/CataloguePages';
import { input } from './Common';

const route = window.route;

export function useIngredients(initial, page, selectedIds, selected = []) {
    const catalogue = usePosCatalogue(route('izzi.ingredients'), initial, page);
    const known = useRef(new Map([...initial, ...selected].map((p) => [String(p.id), p])));
    for (const p of catalogue.products) known.current.set(String(p.id), p);
    const choices = new Map(catalogue.products.map((p) => [String(p.id), p]));
    for (const id of selectedIds) if (known.current.has(String(id))) choices.set(String(id), known.current.get(String(id)));
    return { catalogue, ingredients: [...choices.values()] };
}

export default function IngredientCatalogue({ catalogue }) {
    return (
        <div className="space-y-2 rounded-xl border border-gray-200 bg-gray-50/70 p-3.5 dark:border-gray-700 dark:bg-gray-800/40">
            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300">
                Cari Bahan dari Master Produk Bersama
                <input
                    type="search"
                    className={`${input} mt-1.5 bg-white dark:bg-gray-900`}
                    placeholder="Ketik nama atau kode produk master untuk memfilter pilihan bahan di bawah…"
                    value={catalogue.query}
                    onChange={(e) => catalogue.setQuery(e.target.value)}
                />
            </label>
            <CataloguePages catalogue={catalogue} />
        </div>
    );
}
