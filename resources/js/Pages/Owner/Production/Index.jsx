import { Head, Link, router } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import StockMonitor, { DeadlineAlert } from './StockMonitor';

export default function Index({ records, filters, production_monitor, jihans_stocks }) {
    const changeFilter = (key, value) => {
        const next = { ...filters, [key]: value }; delete next.page;
        Object.keys(next).forEach(key => { if (!next[key]) delete next[key]; });
        router.get(route('owner.production.index'), next, { preserveState: true, preserveScroll: true });
    };
    return <OwnerLayout pageTitle="Produksi & Sisa Stok">
        <Head title="Produksi & Sisa Stok" />
        <div className="mx-auto max-w-6xl space-y-6">
            <div><h1 className="text-2xl font-bold">Pantau produksi dan sisa stok</h1><p className="mt-1 text-sm text-slate-500">Lihat rencana produksi, hasil sebenarnya, dan stok yang masih berada di pusat atau gudang.</p></div>
            <DeadlineAlert monitor={production_monitor} />
            <div className="grid items-start gap-5 xl:grid-cols-2"><StockMonitor entity="hendhys" monitor={production_monitor} /><StockMonitor entity="jihans" jihansStocks={jihans_stocks} /></div>
            <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 className="text-lg font-bold">Riwayat prediksi dan produksi aktual</h2>
                <div className="my-4 grid gap-3 sm:grid-cols-3">
                    <label className="text-sm">Unit usaha<select className="mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2 dark:border-gray-700" value={filters.entity ?? ''} onChange={e => changeFilter('entity', e.target.value)}><option value="">Semua unit</option><option value="hendhys">Hendhys Produksi</option><option value="jihans">Jihan’s</option></select></label>
                    <label className="text-sm">Jenis catatan<select className="mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2 dark:border-gray-700" value={filters.type ?? ''} onChange={e => changeFilter('type', e.target.value)}><option value="">Prediksi dan aktual</option><option value="prediksi">Prediksi — rencana produksi</option><option value="aktual">Aktual — sudah diproduksi</option></select></label>
                    <label className="text-sm">Tanggal produksi<input type="date" className="mt-1 w-full rounded-lg border border-slate-300 bg-transparent p-2 dark:border-gray-700" value={filters.date ?? ''} onChange={e => changeFilter('date', e.target.value)} /></label>
                </div>
                <p className="mb-3 text-xs text-slate-500">Prediksi yang sudah dijadikan aktual dapat dilihat di rincian aktual, bila rencananya tersimpan.</p>
                <div className="space-y-3">{records.data.map(row => <Link key={`${row.entity}-${row.id}`} href={route('owner.production.show', { entity: row.entity, id: row.id })} className="block rounded-xl border border-slate-200 p-4 transition hover:border-indigo-400 dark:border-gray-800">
                    <div className="flex flex-wrap items-center justify-between gap-2"><p className="font-semibold">{row.number}</p><span className={`rounded-full px-3 py-1 text-xs font-semibold ${row.type === 'aktual' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>{row.type === 'aktual' ? 'Aktual · sudah diproduksi' : 'Prediksi · rencana'}</span></div>
                    <p className="mt-2 text-sm text-slate-500">{row.entity === 'hendhys' ? 'Hendhys Produksi' : 'Jihan’s'} · {String(row.date).slice(0, 10)}</p><p className="mt-2 text-sm font-semibold text-indigo-600">Lihat rincian produksi →</p>
                </Link>)}{records.data.length === 0 && <p className="py-6 text-center text-slate-500">Tidak ada catatan produksi untuk pilihan ini.</p>}</div>
                <div className="mt-4 flex flex-wrap gap-2">{records.links.map((link, index) => <Link key={index} href={link.url ?? '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : !link.url ? 'pointer-events-none opacity-40' : 'border-slate-200 dark:border-gray-700'}`} dangerouslySetInnerHTML={{ __html: link.label.replace('Previous', 'Sebelumnya').replace('Next', 'Berikutnya') }} />)}</div>
            </section>
        </div>
    </OwnerLayout>;
}
