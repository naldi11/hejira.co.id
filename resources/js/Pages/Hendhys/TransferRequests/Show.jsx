import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function TransferRequestsShow({ request: r }) {
    return (
        <HendhysLayout pageTitle="Detail Request">
            <Head title={`Request ${r.request_number}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">{r.request_number}</h2><Link href={route('hendhys.transfer-requests.index')} className="text-sm font-medium text-amber-600">← Kembali</Link></div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-2 gap-4 text-sm mb-6">
                        <div><span className="text-gray-500">Tanggal:</span> <strong>{r.date}</strong></div>
                        <div><span className="text-gray-500">Status:</span> <span className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase ${r.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : r.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}`}>{r.status}</span></div>
                        <div><span className="text-gray-500">Dibuat Oleh:</span> <strong>{r.creator}</strong></div>
                        {r.notes && <div className="col-span-2"><span className="text-gray-500">Catatan:</span> {r.notes}</div>}
                    </div>
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty Diminta</th><th className="px-4 py-3 text-center font-medium">Qty Disetujui</th><th className="px-4 py-3 text-center font-medium">Satuan</th></tr></thead>
                        <tbody className="divide-y">{r.details?.map((d, i) => (<tr key={i} className="hover:bg-gray-50"><td className="px-4 py-3 font-medium text-gray-800">{d.product} <span className="text-xs text-gray-400">({d.product_code})</span></td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity_requested)}</td><td className="px-4 py-3 text-center font-bold text-green-600">{d.quantity_approved !== null ? formatQty(d.quantity_approved) : '-'}</td><td className="px-4 py-3 text-center text-gray-500">{d.unit}</td></tr>))}</tbody>
                    </table>
                    {r.transfer_outs?.length > 0 && <div className="mt-6 border-t pt-4"><h4 className="font-semibold text-gray-800 mb-2">Pengiriman</h4>{r.transfer_outs.map((t) => (<div key={t.id} className="flex items-center justify-between rounded-lg bg-gray-50 p-3 text-sm mb-1"><span className="font-medium">{t.transfer_number}</span><span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${t.status === 'sent' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>{t.status}</span></div>))}</div>}
                </div>
            </div>
        </HendhysLayout>
    );
}
