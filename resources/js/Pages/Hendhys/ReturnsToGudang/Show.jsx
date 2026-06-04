import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function ReturnsToGudangShow({ return: r }) {
    return (
        <HendhysLayout pageTitle="Detail Return ke Gudang">
            <Head title={`Return ${r.return_number}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">{r.return_number}</h2><Link href={route('hendhys.returns-to-gudang.index')} className="text-sm font-medium text-amber-600">← Kembali</Link></div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-2 gap-4 text-sm mb-6"><div><span className="text-gray-500">Tanggal:</span> <strong>{r.date}</strong></div><div><span className="text-gray-500">Status:</span> <span className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase ${r.status === 'sent' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>{r.status}</span></div><div><span className="text-gray-500">Pengirim:</span> <strong>{r.creator}</strong></div>{r.receiver && <div><span className="text-gray-500">Penerima:</span> <strong>{r.receiver}</strong></div>}{r.notes && <div className="col-span-2"><span className="text-gray-500">Catatan:</span> {r.notes}</div>}</div>
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty</th><th className="px-4 py-3 text-center font-medium">Satuan</th><th className="px-4 py-3 text-center font-medium">Kondisi</th></tr></thead><tbody className="divide-y">{r.details?.map((d, i) => (<tr key={i}><td className="px-4 py-3 font-medium text-gray-800">{d.product}</td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity)}</td><td className="px-4 py-3 text-center text-gray-500">{d.unit}</td><td className="px-4 py-3 text-center">{d.condition}{d.notes && <span className="ml-1 text-xs text-gray-400">({d.notes})</span>}</td></tr>))}</tbody></table>
                </div>
            </div>
        </HendhysLayout>
    );
}
