import { Head, Link } from '@inertiajs/react';
import IzziLayout from '@/Layouts/IzziLayout';
import { formatDate, formatQty } from '@/lib/format';

const route = window.route;
const action = 'inline-flex rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold dark:border-gray-700';

export default function Show({ transfer, branchId, canReceive, receipt }) {
    const amount = (productId, condition) => receipt?.items
        .filter((item) => item.product_id === productId && item.condition === condition)
        .reduce((total, item) => total + Number(item.actual_qty), 0) || 0;

    return <IzziLayout pageTitle={`Pengiriman ${transfer.transfer_number}`}>
        <Head title={transfer.transfer_number} />
        <div className="space-y-6 text-gray-800 dark:text-gray-200">
            <Link className="text-brand-500" href={route('izzi.warehouse-shipments.index', { branch_id: branchId })}>← Pengiriman Gudang</Link>
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div><h1 className="text-2xl font-bold">{transfer.transfer_number}</h1><p className="mt-2">{transfer.destination}</p></div>
                <div className="flex flex-wrap gap-2">
                    <a className={action} target="_blank" rel="noopener noreferrer" href={route('izzi.warehouse-shipments.print', transfer.id)}>Cetak Surat Jalan</a>
                    {receipt && <a className={action} target="_blank" rel="noopener noreferrer" href={route('izzi.warehouse-shipments.bast', transfer.id)}>Cetak BAST</a>}
                    {canReceive && <Link className={`${action} bg-brand-500 text-white`} href={route('izzi.warehouse-shipments.receive-form', transfer.id)}>Konfirmasi Penerimaan</Link>}
                </div>
            </div>
            <div className="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:grid-cols-2 dark:border-gray-800 dark:bg-gray-900">
                <p><span className="block text-sm text-gray-500">Tanggal kirim</span>{formatDate(transfer.date)}</p>
                <p><span className="block text-sm text-gray-500">Pengirim Gudang</span>{transfer.creator}</p>
                <p><span className="block text-sm text-gray-500">Status</span>{receipt ? 'Penerimaan sudah dikonfirmasi' : 'Menunggu pemeriksaan penerima'}</p>
                <p><span className="block text-sm text-gray-500">Catatan pengiriman</span>{transfer.notes || '-'}</p>
                {receipt && <><p><span className="block text-sm text-gray-500">Diterima oleh</span>{receipt.receiver} · {receipt.received_at}</p><p><span className="block text-sm text-gray-500">Catatan penerimaan</span>{receipt.notes || '-'}</p></>}
            </div>
            <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table className="w-full text-left text-sm">
                    <thead className="bg-gray-50 dark:bg-gray-800"><tr>
                        {['Barang', 'Satuan', 'Dikirim', ...(receipt ? ['Baik', 'Rusak', 'Kurang'] : [])].map((label) => <th className="px-5 py-4" key={label}>{label}</th>)}
                    </tr></thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {transfer.details.map((item) => <tr key={item.id}>
                            <td className="px-5 py-4 font-semibold">{item.product}</td><td className="px-5 py-4">{item.unit}</td><td className="px-5 py-4">{formatQty(item.quantity)}</td>
                            {receipt && <>{['baik', 'rusak', 'kurang'].map((condition) => <td className="px-5 py-4" key={condition}>{formatQty(amount(item.product_id, condition))}</td>)}</>}
                        </tr>)}
                    </tbody>
                </table>
            </div>
            <p className="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">Hanya barang baik menambah stok bahan Izzi. Barang rusak dipisahkan di Stok Rusak / Retur; barang kurang dicatat pada BAST dan tidak otomatis kembali ke stok Gudang.</p>
            {receipt?.photos?.length > 0 && <div className="grid grid-cols-2 gap-3 md:grid-cols-4">{receipt.photos.map((photo, i) => <a href={photo.url} key={photo.url} target="_blank" rel="noopener noreferrer"><img src={photo.url} className="h-40 w-full rounded-lg object-cover" alt={`Bukti penerimaan ${i + 1}`} /></a>)}</div>}
        </div>
    </IzziLayout>;
}
