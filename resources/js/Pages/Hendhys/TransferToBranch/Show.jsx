import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';
const route = window.route;
export default function TransferToBranchShow({ transfer: t }) {
    return (
        <HendhysLayout pageTitle="Detail Distribusi">
            <Head title={`Transfer ${t.transfer_number}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center justify-between"><h2 className="text-2xl font-bold text-gray-800">{t.transfer_number}</h2><Link href={route('hendhys.transfer-to-branch.index')} className="text-sm font-medium text-amber-600">← Kembali</Link></div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="grid grid-cols-2 gap-4 text-sm mb-6"><div><span className="text-gray-500">Tanggal:</span> <strong>{t.date}</strong></div><div><span className="text-gray-500">Status:</span> <span className={`rounded-full px-2 py-0.5 text-xs font-bold uppercase ${t.status === 'sent' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'}`}>{t.status}</span></div><div><span className="text-gray-500">Cabang:</span> <strong>{t.branch}</strong></div><div><span className="text-gray-500">Pengirim:</span> <strong>{t.creator}</strong></div>{t.receiver && <div><span className="text-gray-500">Penerima:</span> <strong>{t.receiver}</strong></div>}{t.notes && <div className="col-span-2"><span className="text-gray-500">Catatan:</span> {t.notes}</div>}{t.receive_kendala && <div className="col-span-2 text-red-600"><span className="text-gray-500">Kendala:</span> {t.receive_kendala}</div>}</div>
                    <table className="w-full text-left text-sm"><thead className="border-b bg-gray-50 text-gray-500"><tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty Kirim</th><th className="px-4 py-3 text-center font-medium">Qty Diterima</th><th className="px-4 py-3 text-center font-medium">Kondisi</th></tr></thead><tbody className="divide-y">{t.details?.map((d, i) => (<tr key={i}><td className="px-4 py-3 font-medium text-gray-800">{d.product}</td><td className="px-4 py-3 text-center font-bold">{formatQty(d.quantity)}</td><td className="px-4 py-3 text-center font-bold text-green-600">{d.received_quantity !== null ? formatQty(d.received_quantity) : '-'}</td><td className="px-4 py-3 text-center"><span className="capitalize">{d.kondisi ?? '-'}</span></td></tr>))}</tbody></table>
                    <div className="mt-6 flex gap-3 border-t pt-4">
                        {t.status === 'sent' && <Link href={route('hendhys.transfer-to-branch.receive-form', t.id)} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-green-700"><Icon name="check_circle" className="text-[20px]" /> Konfirmasi Penerimaan</Link>}
                        <a href={route('hendhys.transfer-to-branch.print-bast', t.id)} target="_blank" className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"><Icon name="print" className="text-[20px]" /> Cetak BAST</a>
                    </div>
                </div>
            </div>
        </HendhysLayout>
    );
}
