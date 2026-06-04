import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function ProductionsShow({ production }) {
    const p = production;
    return (
        <HendhysLayout pageTitle="Detail Produksi">
            <Head title={`Produksi ${p.production_number}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">{p.production_number}</h2><Link href={route('hendhys.productions.index')} className="text-sm font-medium text-amber-600 hover:text-amber-800">← Kembali</Link></div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-2 gap-4 text-sm mb-6"><div><span className="text-gray-500">Tanggal:</span> <strong>{p.date}</strong></div><div><span className="text-gray-500">Operator:</span> <strong>{p.creator}</strong></div>{p.notes && <div className="col-span-2"><span className="text-gray-500">Catatan:</span> {p.notes}</div>}</div>
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty Produksi</th><th className="px-4 py-3 text-center font-medium">Satuan</th></tr></thead>
                        <tbody className="divide-y">{p.details?.map((d, i) => (<tr key={i} className="hover:bg-gray-50"><td className="px-4 py-3 font-medium text-gray-800">{d.product} <span className="text-xs text-gray-400">({d.product_code})</span></td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity_produced)}</td><td className="px-4 py-3 text-center text-gray-500">{d.unit}</td></tr>))}</tbody>
                    </table>
                </div>
            </div>
        </HendhysLayout>
    );
}
