import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty } from '@/lib/format';

const route = window.route;

export default function JihansReturnShow({ return: ret }) {
    return (
        <JihansLayout pageTitle={`Detail Retur ${ret.return_number}`}>
            <Head title={ret.return_number} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Link href={route('jihans.returns-to-gudang.index')} className="inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali
                </Link>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-gray-50 p-6">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800">{ret.return_number}</h2>
                            <p className="mt-1 text-sm text-gray-500">Dikirim {formatDate(ret.date)} · Oleh {ret.creator ?? '-'}</p>
                        </div>
                        <StatusBadge status={ret.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-100 p-6 md:grid-cols-2 text-sm">
                        <div><p className="text-xs text-gray-400">Tujuan</p><p className="font-medium text-gray-800">Gudang Utama</p></div>
                        <div><p className="text-xs text-gray-400">Catatan</p><p className="text-gray-800">{ret.notes || '-'}</p></div>
                        {ret.status === 'received' && (
                            <div><p className="text-xs text-gray-400">Diterima Oleh</p><p className="text-gray-800">{ret.receiver ?? '-'} {ret.received_at ? `(${ret.received_at})` : ''}</p></div>
                        )}
                    </div>

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-800">Rincian Barang</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                    <tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-right font-medium">Qty Dikirim</th><th className="px-4 py-3 text-right font-medium">Qty Diterima</th><th className="px-4 py-3 font-medium">Kondisi</th><th className="px-4 py-3 font-medium">Satuan</th></tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {ret.details.map((d) => (
                                        <tr key={d.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 font-medium text-gray-800">{d.product}</td>
                                            <td className="px-4 py-3 text-right font-bold text-gray-900">{formatQty(d.quantity)}</td>
                                            <td className="px-4 py-3 text-right text-emerald-600">{d.received_quantity !== null ? formatQty(d.received_quantity) : '-'}</td>
                                            <td className="px-4 py-3"><span className="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{d.condition}</span></td>
                                            <td className="px-4 py-3 text-gray-500">{d.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </JihansLayout>
    );
}
