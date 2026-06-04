import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function BranchRequestsShow({ branchRequest: r }) {
    return (
        <HendhysLayout pageTitle="Detail Request Cabang">
            <Head title={`Request ${r.request_number}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">{r.request_number}</h2><Link href={route('hendhys.branch-requests.index')} className="text-sm font-medium text-amber-600">← Kembali</Link></div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-2 gap-4 text-sm mb-6"><div><span className="text-gray-500">Tanggal:</span> <strong>{r.date}</strong></div><div><span className="text-gray-500">Status:</span> <span className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase ${r.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'}`}>{r.status}</span></div><div><span className="text-gray-500">Cabang:</span> <strong>{r.branch ?? 'N/A'}</strong></div><div><span className="text-gray-500">Dibuat Oleh:</span> <strong>{r.creator}</strong></div>{r.notes && <div className="col-span-2"><span className="text-gray-500">Catatan:</span> {r.notes}</div>}</div>
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Diminta</th><th className="px-4 py-3 text-center font-medium">Disetujui</th><th className="px-4 py-3 text-center font-medium">Satuan</th></tr></thead><tbody className="divide-y">{r.details?.map((d, i) => (<tr key={i}><td className="px-4 py-3 font-medium text-gray-800">{d.product} <span className="text-xs text-gray-400">({d.product_code})</span></td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity_requested)}</td><td className="px-4 py-3 text-center font-bold text-green-600">{d.quantity_approved !== null ? formatQty(d.quantity_approved) : '-'}</td><td className="px-4 py-3 text-center text-gray-500">{d.unit}</td></tr>))}</tbody></table>
                    {r.status === 'pending' && (<div className="mt-6 border-t pt-4"><Link href={route('hendhys.transfer-to-branch.create') + '?request_id=' + r.id} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-green-700"><Icon name="local_shipping" className="text-[20px]" /> Proses Distribusi</Link></div>)}
                </div>
            </div>
        </HendhysLayout>
    );
}
