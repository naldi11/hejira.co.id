import { Link } from '@inertiajs/react';
import { formatQty } from '@/lib/format';

export function DeadlineAlert({ monitor }) {
    if (!monitor?.alert) return null;
    return <div role="alert" className="rounded-xl border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
        <p className="font-bold">{monitor.alert.title}</p>
        <p className="mt-1 text-sm">{monitor.alert.message}</p>
        <Link className="mt-2 inline-block text-sm font-semibold underline" href={route('owner.production.index')}>Lihat produk yang masih tersisa</Link>
    </div>;
}

export default function StockMonitor({ monitor, jihansStocks, entity }) {
    const rows = entity === 'hendhys' ? monitor?.stocks ?? [] : jihansStocks ?? [];
    return <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <h2 className="text-lg font-bold">{entity === 'hendhys' ? 'Sisa stok di Hendhys Produksi' : 'Sisa stok hasil produksi Jihan’s'}</h2>
        <p className="mt-1 text-sm text-slate-500 dark:text-gray-400">{entity === 'hendhys'
            ? 'Produksi pagi diasumsikan mulai 08.00 WIB. Stok baik hasil produksi harus keluar dari pusat paling lambat 17.00 WIB. Stok retur dan stok di cabang tidak dihitung.'
            : 'Hasil produksi aktual dicatat ke gudang. Stok yang sudah dipindahkan ke outlet ditampilkan terpisah.'}</p>
        <p className="mt-2 text-xs text-slate-500">Stok saat ini mencakup seluruh produksi, bukan sisa dari satu nomor produksi. Prediksi belum menambah stok.</p>
        {entity === 'hendhys' && <p className="mt-2 text-xs text-slate-500">Diperiksa: {monitor?.checked_at}. Pembaruan otomatis setiap 60 detik.</p>}
        <div className="mt-4 space-y-2">
            {rows.length === 0 && <p className="py-4 text-sm text-slate-500">Belum ada stok hasil produksi yang tercatat.</p>}
            {rows.map(row => <div key={row.product_id} className={`flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3 ${row.overdue ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200' : 'border-slate-200 dark:border-gray-800'}`}>
                <div><p className="font-semibold">{row.product}</p><p className="text-xs opacity-70">{row.code}</p>{row.overdue && <p className="mt-1 text-xs font-semibold">Belum habis sejak {row.deadline_date} pukul 17.00 WIB</p>}{row.overdue && (monitor?.overdue_lots ?? []).filter(lot => lot.product_id === row.product_id).map(lot => <Link key={lot.production_id} href={route('owner.production.show', { entity: 'hendhys', id: lot.production_id })} className="mt-1 block text-xs underline">{lot.number}: sisa {formatQty(lot.quantity)} {row.unit}</Link>)}</div>
                <div className="text-right"><p className="text-lg font-bold">{formatQty(row.quantity)} <span className="text-xs font-normal">{row.unit}</span></p>{entity === 'jihans' && <p className="text-xs">Gudang · Outlet: {formatQty(row.retail_quantity)} {row.unit}</p>}</div>
            </div>)}
        </div>
    </section>;
}
