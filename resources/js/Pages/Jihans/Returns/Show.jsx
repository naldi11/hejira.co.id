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

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-250 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{ret.return_number}</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Dikirim {formatDate(ret.date)} · Oleh {ret.creator ?? '-'}</p>
                        </div>
                        <StatusBadge status={ret.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-3 text-sm dark:border-gray-800">
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Tujuan</p>
                            <p className="font-medium text-gray-800 dark:text-white/90 mt-1">Gudang Utama</p>
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Catatan</p>
                            <p className="text-gray-800 dark:text-gray-300 mt-1">{ret.notes || '-'}</p>
                        </div>
                        {ret.status === 'received' && (
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Diterima Oleh</p>
                                <p className="text-gray-800 dark:text-white/90 mt-1">
                                    {ret.receiver ?? '-'} {ret.received_at ? `(${ret.received_at})` : ''}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-850 dark:text-white/90">Rincian Barang</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-semibold">Produk</th>
                                        <th className="px-6 py-4 text-right font-semibold">Qty Dikirim</th>
                                        <th className="px-6 py-4 text-right font-semibold">Qty Diterima</th>
                                        <th className="px-6 py-4 font-semibold">Kondisi</th>
                                        <th className="px-6 py-4 font-semibold">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {ret.details.map((d) => (
                                        <tr key={d.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">{d.product}</td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-950 dark:text-white">{formatQty(d.quantity)}</td>
                                            <td className="px-6 py-4 text-right font-bold text-emerald-600 dark:text-emerald-400">{d.received_quantity !== null ? formatQty(d.received_quantity) : '-'}</td>
                                            <td className="px-6 py-4">
                                                <span className="rounded-full bg-gray-100 dark:bg-gray-850 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    {d.condition}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{d.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div className="flex justify-start pt-4 print:hidden">
                    <Link href={route('jihans.returns-to-gudang.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                </div>
            </div>
        </JihansLayout>
    );
}
