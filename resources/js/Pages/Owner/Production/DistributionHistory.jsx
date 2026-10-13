import { Link, router } from '@inertiajs/react';
import { formatQty } from '@/lib/format';

export default function DistributionHistory({ production, distributions, date }) {
    const actual = production.type === 'aktual';
    const selectDate = (value) => router.get(route('owner.production.show', { entity: production.entity, id: production.id }), value ? { distribution_date: value } : {}, { preserveState: true, preserveScroll: true });

    return <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <h2 className="text-lg font-bold">Rincian distribusi ke cabang</h2>
        {!actual ? <p className="mt-2 text-sm text-slate-500">Produksi ini masih berupa prediksi. Riwayat distribusi tersedia pada rincian produksi aktual.</p> : <>
            <p className="mt-2 text-sm text-slate-500 dark:text-gray-400">Hanya jumlah yang berasal dari nomor produksi ini yang ditampilkan. Satu surat jalan dapat membawa beberapa produksi; jumlahnya dipisahkan untuk setiap nomor produksi.</p>
            <div className="my-4 flex flex-wrap items-end justify-between gap-3">
                <form onSubmit={event => { event.preventDefault(); selectDate(new FormData(event.currentTarget).get('distribution_date')); }} className="flex flex-wrap items-end gap-3">
                    <label htmlFor="distribution-date" className="text-sm">Tanggal pengiriman<input id="distribution-date" name="distribution_date" type="date" key={date} defaultValue={date ?? ''} className="mt-1 block rounded-lg border border-slate-300 bg-transparent p-2 dark:border-gray-700" /></label>
                    <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tampilkan</button>
                </form>
                {date && <button type="button" className="text-sm font-semibold text-indigo-600" onClick={() => selectDate('')}>Semua tanggal</button>}
                <p className="text-sm text-slate-500">{distributions?.total ?? 0} pengiriman tercatat</p>
            </div>
            <p className="mb-4 text-xs text-slate-500">Semua tanggal ditampilkan secara awal. Waktu menggunakan WIB saat pengiriman dicatat.</p>
            <div className="space-y-4">{distributions?.data?.map(shipment => <article key={shipment.id} className="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                <div className="flex flex-wrap items-start justify-between gap-2"><div><h3 className="font-bold">{shipment.destination}</h3><p className="mt-1 break-all text-xs text-slate-500">{shipment.number}{shipment.destination_code && ` · ${shipment.destination_code}`}</p></div><span className={`rounded-full px-3 py-1 text-xs font-semibold ${shipment.status === 'received' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>{shipment.status === 'received' ? 'Sudah diterima cabang' : 'Dikirim · belum diterima'}</span></div>
                <dl className="my-3 grid gap-3 text-sm sm:grid-cols-2"><div><dt className="text-slate-500">Waktu pengiriman tercatat</dt><dd className="mt-1 font-semibold">{shipment.sent_at}</dd></div><div><dt className="text-slate-500">User yang mengirim</dt><dd className="mt-1 font-semibold">{shipment.sender}</dd></div></dl>
                <div className="space-y-2">{shipment.items.map((item, index) => <div key={index} className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-3 text-sm dark:bg-gray-800"><p className="font-semibold">{item.product}</p><div className="text-right"><p>Dari produksi ini: <strong>{formatQty(item.quantity)} {item.unit}</strong></p>{item.returned_quantity > 0 && <p className="mt-1 text-xs text-amber-700">Kembali ke produksi: {formatQty(item.returned_quantity)} {item.unit}</p>}{item.document_quantity !== item.quantity && <p className="mt-1 text-xs text-slate-500">Total produk di surat jalan: {formatQty(item.document_quantity)} {item.unit} (beberapa sumber stok)</p>}{shipment.status === 'received' && <p className="mt-1 text-xs text-slate-500">Diterima: {item.received_quantity == null ? (item.document_quantity !== item.quantity ? 'Penerimaan dicatat gabungan per produk, belum per nomor produksi' : 'Jumlah belum dicatat') : `${formatQty(item.received_quantity)} ${item.unit}`}</p>}</div></div>)}</div>
                {shipment.notes && <p className="mt-3 whitespace-pre-wrap text-sm text-slate-500">Catatan: {shipment.notes}</p>}
            </article>)}{!distributions?.data?.length && <p className="rounded-xl bg-slate-50 p-5 text-sm text-slate-500 dark:bg-gray-800">Belum ada pengiriman yang terhubung ke nomor produksi ini{date && ` pada tanggal ${date}`}.</p>}</div>
            {distributions?.last_page > 1 && <nav aria-label="Halaman distribusi" className="mt-4 flex flex-wrap gap-2">{distributions.links.map((link, index) => <Link key={index} href={link.url ?? '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-indigo-600 bg-indigo-600 text-white' : !link.url ? 'pointer-events-none opacity-40' : 'border-slate-200 dark:border-gray-700'}`} dangerouslySetInnerHTML={{ __html: link.label.replace('Previous', 'Sebelumnya').replace('Next', 'Berikutnya') }} />)}</nav>}
        </>}
    </section>;
}
