import { Head, Link } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import { formatQty } from '@/lib/format';
import { DeadlineAlert } from './StockMonitor';
import DistributionHistory from './DistributionHistory';
import WriteoffHistory from '../ProductionWriteoffs/History';

function Items({ items, label }) {
    return <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><h2 className="mb-4 text-lg font-bold">{label}</h2><div className="space-y-3">{items.map((item, index) => <div key={index} className="flex items-center justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 dark:border-gray-800"><div><p className="font-semibold">{item.product}</p>{item.employee && <p className="text-sm text-slate-500">Karyawan: {item.employee}</p>}</div><p className="shrink-0 font-bold">{formatQty(item.quantity)} {item.unit}</p></div>)}{items.length === 0 && <p className="text-slate-500">Rincian produk tidak tersedia.</p>}</div></section>;
}

export default function Show({ production, production_monitor, jihans_stocks, distributions, distribution_date, batch_stock = [], writeoffs }) {
    const isActual = production.type === 'aktual';
    return <OwnerLayout pageTitle="Rincian Produksi"><Head title={production.number} /><div className="mx-auto max-w-4xl space-y-5">
        <Link href={route('owner.production.index')} className="text-sm font-semibold text-indigo-600">← Kembali ke produksi dan stok</Link>
        <DeadlineAlert monitor={production_monitor} />
        <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p className="text-sm text-slate-500">{production.entity === 'hendhys' ? 'Hendhys Produksi' : 'Jihan’s'} · {production.date}</p><h1 className="mt-1 text-2xl font-bold">{production.number}</h1><p className="mt-3 font-semibold">{isActual ? 'Produksi aktual — sudah menambah stok' : 'Prediksi — rencana, belum menambah stok'}</p><p className="mt-1 text-sm text-slate-500">Dicatat oleh {production.creator ?? 'Pengguna tidak tersedia'}</p>{production.notes && <p className="mt-3 whitespace-pre-wrap text-sm">Catatan: {production.notes}</p>}</section>
        <Items items={production.items} label={isActual ? 'Jumlah yang benar-benar diproduksi' : 'Jumlah yang direncanakan'} />
        {production.prediction && <><Items items={production.prediction.items} label="Rencana awal sebelum dijadikan aktual" /><p className="text-sm text-slate-500">Prediksi: {production.prediction.date} · {production.prediction.creator}{production.prediction.notes && ` · ${production.prediction.notes}`}</p></>}
        <DistributionHistory production={production} distributions={distributions} date={distribution_date} />
        {isActual && <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 className="text-lg font-bold">Sisa stok nomor produksi ini</h2>
            <p className="mt-2 text-sm text-slate-500">Saldo di {production.entity === 'hendhys' ? 'Hendhys Produksi' : 'gudang produksi Jihan’s'}, dipisahkan dari produksi lain. Penghapusan sisa dicatat terpisah. Pengeluaran lain mencakup penjualan, retur, dan penyesuaian stok.</p>
            {batch_stock.length === 0 ? <p className="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">Produksi lama ini belum memiliki pencatatan stok per nomor produksi. Sisa stok dan asal pengiriman tidak dapat dipastikan dari data lama. Pencatatan terpisah berlaku untuk produksi aktual yang dibuat setelah fitur ini aktif.</p> : <div className="mt-4 space-y-3">{batch_stock.map(item => <article key={item.id} className={`rounded-xl border p-4 ${item.overdue ? 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200' : 'border-slate-200 dark:border-gray-800'}`}>
                <h3 className="font-bold">{item.product}</h3>
                <dl className="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">{[['Diproduksi', item.produced], ['Dikirim (setelah retur)', item.distributed], ['Dihapus owner', item.written_off ?? 0], ['Pengeluaran lain (neto)', item.other_out], ['Sisa di produksi', item.remaining]].map(([label, value]) => <div key={label}><dt className="opacity-75">{label}</dt><dd className="mt-1 font-bold">{formatQty(value)} {item.unit}</dd></div>)}</dl>
                {item.unassigned_quantity > 0 && <p className="mt-3 text-xs text-amber-700 dark:text-amber-400">Ada {formatQty(item.unassigned_quantity)} {item.unit} stok lain tanpa nomor produksi (stok lama/retur belum teridentifikasi), yang dihitung terpisah dari saldo produksi ini.</p>}
                {item.returned > 0 && <p className="mt-2 text-xs opacity-75">{formatQty(item.returned)} {item.unit} dari pengiriman sudah kembali ke nomor produksi ini.</p>}
                {item.writeoff_cancelled > 0 && <p className="mt-2 text-xs text-emerald-700 dark:text-emerald-400">{formatQty(item.writeoff_cancelled)} {item.unit} dikembalikan karena pembatalan penghapusan. Jumlah dihapus owner sudah dikurangi pembatalan tersebut.</p>}
                {item.overdue && <p className="mt-3 text-sm font-semibold" role="alert">Stok produksi ini belum habis setelah batas {item.deadline}. Segera distribusikan ke cabang.</p>}
            </article>)}</div>}
            <Link href={route('owner.production-writeoffs.index', { entity: production.entity, search: production.number })} className="mt-4 inline-block text-sm font-semibold text-red-600">Buka menu penghapusan sisa produksi →</Link>
        </section>}
        {isActual && <WriteoffHistory history={writeoffs} />}
    </div></OwnerLayout>;
}
