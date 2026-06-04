import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from '@/Components/Icon';
import { formatQty, formatRupiah } from '@/lib/format';

/** Item-search modal for the POS. Click a row (or Enter) to add it to the cart. */
export default function SearchModal({ products, onAdd, onClose }) {
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const inputRef = useRef(null);

    useEffect(() => { inputRef.current?.focus(); }, []);

    const filtered = useMemo(() => {
        const q = query.toLowerCase().trim();
        if (!q) return products;
        return products.filter((p) =>
            p.name.toLowerCase().includes(q) ||
            (p.code && p.code.toLowerCase().includes(q)) ||
            (p.barcode && p.barcode.toLowerCase().includes(q)));
    }, [query, products]);

    const onKeyDown = (e) => {
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive((i) => Math.min(i + 1, filtered.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive((i) => Math.max(i - 1, 0)); }
        else if (e.key === 'Enter') { e.preventDefault(); const p = filtered[active]; if (p) onAdd(p); }
        else if (e.key === 'Escape') { onClose(); }
    };

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div className="flex w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-gray-500 bg-white shadow-2xl">
                <div className="flex items-center justify-between bg-orange-700 px-4 py-2 text-white">
                    <span className="text-sm font-bold">Daftar Item</span>
                    <button onClick={onClose} className="font-bold hover:text-orange-200">✕</button>
                </div>
                <div className="border-b border-gray-200 bg-gray-50 p-3">
                    <div className="relative">
                        <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" />
                        <input ref={inputRef} value={query} onChange={(e) => { setQuery(e.target.value); setActive(0); }} onKeyDown={onKeyDown}
                            placeholder="Ketik nama / kode / barcode, lalu Enter atau ↓"
                            className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-orange-500 focus:ring-orange-500" />
                    </div>
                </div>
                <div className="custom-scrollbar h-[400px] overflow-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="sticky top-0 bg-gray-100 text-xs text-gray-500">
                            <tr><th className="px-3 py-2">Kode</th><th className="px-3 py-2">Nama Item</th><th className="px-3 py-2 text-center">Stok</th><th className="px-3 py-2 text-center">Satuan</th><th className="px-3 py-2 text-right">Harga</th></tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {filtered.length === 0 ? (
                                <tr><td colSpan={5} className="py-8 text-center text-gray-400">Data tidak ditemukan.</td></tr>
                            ) : filtered.map((p, i) => {
                                const empty = p.current_stock <= 0;
                                return (
                                    <tr key={p.id} onClick={() => onAdd(p)} onMouseEnter={() => setActive(i)}
                                        className={`cursor-pointer ${i === active ? 'bg-orange-100' : 'hover:bg-gray-50'} ${empty ? 'opacity-50' : ''}`}>
                                        <td className="px-3 py-2 font-mono text-xs">{p.code}</td>
                                        <td className="px-3 py-2 font-medium text-gray-800">{p.name}</td>
                                        <td className={`px-3 py-2 text-center font-bold ${empty ? 'text-red-600' : 'text-gray-700'}`}>{formatQty(p.current_stock)}</td>
                                        <td className="px-3 py-2 text-center text-gray-500">{p.unit}</td>
                                        <td className="px-3 py-2 text-right">{formatRupiah(p.selling_price)}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                <div className="flex items-center justify-between border-t border-gray-300 bg-gray-100 p-2 text-xs text-gray-600">
                    <span>Klik baris untuk menambah ke keranjang</span>
                    <button onClick={onClose} className="rounded border border-gray-400 bg-white px-4 py-1.5 font-bold text-gray-700 hover:bg-gray-50">Tutup [Esc]</button>
                </div>
            </div>
        </div>
    );
}
