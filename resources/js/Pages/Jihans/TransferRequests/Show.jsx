import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty } from '@/lib/format';

const route = window.route;

export default function JihansTransferRequestShow({ request }) {
    return (
        <JihansLayout pageTitle={`Detail Request ${request.request_number}`}>
            <Head title={request.request_number} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Link href={route('jihans.transfer-requests.index')} className="inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali ke Daftar Request
                </Link>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 bg-gray-50 p-6">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800">{request.request_number}</h2>
                            <p className="mt-1 text-sm text-gray-500">Tanggal Request: {formatDate(request.date)} · Oleh {request.creator ?? '-'}</p>
                        </div>
                        <StatusBadge status={request.status} />
                    </div>

                    {request.notes && (
                        <div className="border-b border-gray-100 p-6">
                            <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Catatan</p>
                            <p className="mt-1 text-sm text-gray-700">{request.notes}</p>
                        </div>
                    )}

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-800">Daftar Barang yang Direquest</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                    <tr><th className="px-4 py-3 font-medium">Produk</th><th className="px-4 py-3 text-center font-medium">Qty Diminta</th><th className="px-4 py-3 text-center font-medium">Qty Disetujui</th><th className="px-4 py-3 text-center font-medium">Satuan</th></tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {request.details.map((d, i) => (
                                        <tr key={i} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <p className="font-medium text-gray-800">{d.product}</p>
                                                <p className="font-mono text-xs text-gray-400">{d.product_code}</p>
                                            </td>
                                            <td className="px-4 py-3 text-center font-bold text-gray-900">{formatQty(d.quantity_requested)}</td>
                                            <td className="px-4 py-3 text-center font-semibold text-gray-700">{d.quantity_approved !== null ? formatQty(d.quantity_approved) : '-'}</td>
                                            <td className="px-4 py-3 text-center text-gray-500">{d.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {request.transfer_outs?.length > 0 && (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div className="flex items-center gap-2 border-b border-gray-100 bg-gray-50 p-4"><Icon name="local_shipping" className="text-[20px] text-orange-600" /><h3 className="font-bold text-gray-800">Pengiriman Terkait</h3></div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-gray-500">
                                    <tr><th className="px-4 py-3 font-medium">No. Transfer (DO)</th><th className="px-4 py-3 font-medium">Tanggal</th><th className="px-4 py-3 text-center font-medium">Status</th></tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {request.transfer_outs.map((t) => (
                                        <tr key={t.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 font-mono font-semibold text-gray-800">{t.transfer_number}</td>
                                            <td className="px-4 py-3 text-gray-600">{t.date}</td>
                                            <td className="px-4 py-3 text-center"><StatusBadge status={t.status} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </JihansLayout>
    );
}
