import { Link } from '@inertiajs/react';

export default function StockOpnameResultCard({ count: c, location, showSession = false, showDetails = true }) {
    const tone = c.difference < 0 ? 'text-red-600' : c.difference > 0 ? 'text-green-600' : 'text-gray-600 dark:text-gray-300';
    return <article className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div className="flex flex-wrap justify-between gap-2"><div>{showDetails ? <Link className="font-bold text-brand-500 underline" href={window.route('stock-opname.result', c.id)}>{c.product_name}</Link> : <strong>{c.product_name}</strong>}<p className="mt-1 text-xs text-gray-500">{c.number} · {c.product_code || (c.current_product_code ? `${c.current_product_code} (kode saat ini)` : `ID produk ${c.product_id}`)}</p></div><span className={`text-sm font-semibold ${tone}`}>{c.difference < 0 ? 'Stok berkurang' : c.difference > 0 ? 'Stok bertambah' : 'Jumlah sesuai'}</span></div>
        {location && <p className="mt-2 text-sm text-gray-500">{location}</p>}
        <div className="mt-4 grid grid-cols-3 gap-3"><div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"><p className="text-xs text-gray-500">Sebelum SO</p><strong className="mt-1 block text-xl">{c.quantity_before}</strong><span className="text-xs text-gray-500">{c.unit_name}</span></div><div className="rounded-lg bg-brand-50 p-3 dark:bg-brand-500/10"><p className="text-xs text-gray-500">Sesudah SO</p><strong className="mt-1 block text-xl">{c.quantity_after}</strong><span className="text-xs text-gray-500">{c.unit_name}</span></div><div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"><p className="text-xs text-gray-500">Selisih</p><strong className={`mt-1 block text-xl ${tone}`}>{c.difference > 0 ? '+' : ''}{c.difference}</strong><span className="text-xs text-gray-500">{c.unit_name}</span></div></div>
        <p className="mt-3 text-sm">Petugas: <strong>{c.counted_by_name}</strong>{c.role_label && ` · ${c.role_label}`}</p><p className="mt-1 text-sm text-gray-500">Disimpan {c.created_at}</p>
        <p className="mt-3 text-sm"><strong>Catatan / alasan:</strong> {c.notes}</p>
        {(showDetails || showSession) && <div className="mt-3 flex flex-wrap gap-4 text-sm">{showDetails && <Link className="font-semibold text-brand-500 underline" href={window.route('stock-opname.result', c.id)}>Detail dan jejak stok →</Link>}{showSession && <Link className="text-brand-500 underline" href={window.route('stock-opname.show', c.session_id)}>Sesi izin Owner #{c.session_id}</Link>}</div>}
    </article>;
}
