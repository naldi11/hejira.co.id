import { useEffect, useMemo, useRef, useState } from 'react';
import Icon from '@/Components/Icon';
import { formatQty, formatRupiah } from '@/lib/format';

/** Item-search modal for the POS. Click a row (or Enter) to add it to the cart. */
export default function SearchModal({ products, onAdd, onClose }) {
    const [query, setQuery] = useState('');
    const [active, setActive] = useState(0);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    // Auto-focus search input on mount
    useEffect(() => {
        const t = setTimeout(() => inputRef.current?.focus(), 50);
        return () => clearTimeout(t);
    }, []);

    const filtered = useMemo(() => {
        const q = query.toLowerCase().trim();
        if (!q) return products;
        return products.filter((p) =>
            p.name.toLowerCase().includes(q) ||
            (p.code && p.code.toLowerCase().includes(q)) ||
            (p.barcode && p.barcode.toLowerCase().includes(q))
        );
    }, [query, products]);

    // Reset active index when filtered results change
    useEffect(() => {
        setActive(0);
    }, [filtered]);

    // Auto-scroll active row into view
    useEffect(() => {
        const el = listRef.current?.querySelector(`[data-idx="${active}"]`);
        el?.scrollIntoView({ block: 'nearest' });
    }, [active]);

    const onKeyDown = (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive((i) => Math.min(i + 1, filtered.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const p = filtered[active];
            if (p && p.current_stock > 0) onAdd(p);
        } else if (e.key === 'Escape') {
            onClose();
        }
    };

    return (
        <div
            className="fixed inset-0 z-[60] flex items-start justify-center bg-black/50 pt-[10vh] backdrop-blur-sm"
            onClick={(e) => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <div
                className="flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-950"
                style={{ maxHeight: '80vh' }}
            >
                {/* Header */}
                <div className="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-orange-600 to-orange-500 px-5 py-3.5 dark:border-gray-800">
                    <Icon name="search" className="text-[22px] text-orange-100" />
                    <span className="font-bold text-white uppercase tracking-wider text-sm">Cari Produk</span>
                    <button
                        onClick={onClose}
                        className="ml-auto flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30"
                    >
                        <Icon name="close" className="text-[18px]" />
                    </button>
                </div>

                {/* Search input */}
                <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <div className="relative">
                        <Icon name="search" className="absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" />
                        <input
                            ref={inputRef}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={onKeyDown}
                            placeholder="Ketik nama, kode, atau barcode..."
                            className="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-10 text-sm outline-none transition focus:border-orange-400 focus:bg-white focus:ring-4 focus:ring-orange-400/10 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:focus:border-orange-500 dark:focus:bg-gray-950"
                        />
                        {query && (
                            <button
                                onClick={() => setQuery('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            >
                                <Icon name="close" className="text-[16px]" />
                            </button>
                        )}
                    </div>
                    <div className="mt-2 flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500 font-medium">
                        <span>{filtered.length} produk ditemukan</span>
                        {filtered.length > 0 && (
                            <span className="flex items-center gap-1.5">
                                Gunakan <kbd className="rounded border border-gray-250 bg-gray-50 px-1 py-0.5 font-mono text-[9px] dark:border-gray-700 dark:bg-gray-900">↑↓</kbd> navigasi, 
                                <kbd className="rounded border border-gray-250 bg-gray-50 px-1 py-0.5 font-mono text-[9px] dark:border-gray-700 dark:bg-gray-900">Enter</kbd> pilih
                            </span>
                        )}
                    </div>
                </div>

                {/* Product list */}
                <div ref={listRef} className="custom-scrollbar flex-1 overflow-y-auto">
                    {filtered.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 py-16 text-gray-400 dark:text-gray-600">
                            <Icon name="search_off" className="text-[40px] text-gray-300 dark:text-gray-700" />
                            <p className="text-sm font-semibold">Produk tidak ditemukan</p>
                            <p className="text-xs">Coba kata kunci lain</p>
                        </div>
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead className="sticky top-0 z-10 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:bg-gray-900 dark:text-gray-500">
                                <tr>
                                    <th className="px-4 py-2.5 border-b border-gray-100 dark:border-gray-800/50">Kode</th>
                                    <th className="px-4 py-2.5 border-b border-gray-100 dark:border-gray-800/50">Nama Produk</th>
                                    <th className="px-4 py-2.5 text-center border-b border-gray-100 dark:border-gray-800/50">Stok</th>
                                    <th className="px-4 py-2.5 text-center border-b border-gray-100 dark:border-gray-800/50">Satuan</th>
                                    <th className="px-4 py-2.5 text-right border-b border-gray-100 dark:border-gray-800/50">Harga</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-850">
                                {filtered.map((p, i) => {
                                    const isEmpty = p.current_stock <= 0;
                                    const isActive = i === active;
                                    return (
                                        <tr
                                            key={p.id}
                                            data-idx={i}
                                            onClick={() => !isEmpty && onAdd(p)}
                                            onMouseEnter={() => setActive(i)}
                                            className={[
                                                'transition-colors duration-150',
                                                isEmpty
                                                    ? 'cursor-not-allowed opacity-40'
                                                    : 'cursor-pointer',
                                                isActive && !isEmpty
                                                    ? 'bg-orange-50/70 dark:bg-orange-950/20'
                                                    : 'hover:bg-gray-50/50 dark:hover:bg-white/[0.01]',
                                            ].join(' ')}
                                        >
                                            <td className="px-4 py-3">
                                                <span className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    {p.code}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-semibold text-gray-800 dark:text-white/90">
                                                <div className="flex items-center gap-2">
                                                    <span>{p.name}</span>
                                                    {isActive && !isEmpty && (
                                                        <span className="inline-flex items-center gap-0.5 rounded bg-orange-100 px-1.5 py-0.5 text-[9px] font-bold text-orange-700 dark:bg-orange-950 dark:text-orange-400">
                                                            <Icon name="keyboard_return" className="text-[11px]" /> Enter
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span className={`inline-block min-w-[36px] rounded px-2 py-0.5 text-center text-[11px] font-bold ${
                                                    isEmpty
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400'
                                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                }`}>
                                                    {formatQty(p.current_stock)}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                                                {p.unit}
                                            </td>
                                            <td className="px-4 py-3 text-right font-bold text-gray-850 tabular-nums dark:text-white">
                                                {formatRupiah(p.selling_price)}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Footer */}
                <div className="flex items-center justify-between border-t border-gray-100 bg-gray-50/80 px-5 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
                    <div className="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                        <Icon name="touch_app" className="text-[16px]" />
                        <span>Klik baris atau tekan Enter untuk memilih</span>
                    </div>
                    <button
                        onClick={onClose}
                        className="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Tutup
                        <kbd className="rounded bg-gray-100 px-1 py-0.5 font-mono text-[9px] text-gray-400 dark:bg-gray-800 dark:text-gray-500">Esc</kbd>
                    </button>
                </div>
            </div>
        </div>
    );
}
