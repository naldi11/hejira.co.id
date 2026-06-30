import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatQty } from '@/lib/format';

export default function TransferOutShow({ transfer: t }) {
    const route = window.route;
    return (
        <HendhysLayout pageTitle={`Detail Penerimaan Gudang ${t.transfer_number}`}>
            <Head title={`Transfer ${t.transfer_number}`} />
            
            <div className="mx-auto max-w-4xl space-y-5">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-255 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</h2>
                            <p className="mt-1 text-sm text-gray-550 dark:text-gray-400">Tanggal: {t.date} · Oleh {t.creator}</p>
                        </div>
                        <StatusBadge status={t.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-3 text-sm dark:border-gray-800">
                        <div>
                            <p className="text-xs text-gray-550 dark:text-gray-400 uppercase font-semibold tracking-wider">Cabang Penerima</p>
                            <p className="font-medium text-gray-800 dark:text-white/90 mt-1">{t.branch}</p>
                        </div>
                        {t.notes && (
                            <div className="col-span-1 md:col-span-2">
                                <p className="text-xs text-gray-550 dark:text-gray-400 uppercase font-semibold tracking-wider">Catatan</p>
                                <p className="text-gray-800 dark:text-gray-300 mt-1 dark:text-white/90">{t.notes}</p>
                            </div>
                        )}
                    </div>

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-855 dark:text-white/90">Rincian Barang</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-semibold">Produk</th>
                                        <th className="px-6 py-4 text-center font-semibold">Qty Kirim</th>
                                        <th className="px-6 py-4 text-center font-semibold">Qty Diterima</th>
                                        <th className="px-6 py-4 text-center font-semibold">Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {t.details?.map((d, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">{d.product}</td>
                                            <td className="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{formatQty(d.quantity)}</td>
                                            <td className="px-6 py-4 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                                {d.received_quantity !== null ? formatQty(d.received_quantity) : '-'}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="rounded-full bg-gray-100 dark:bg-gray-855 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-300 capitalize">
                                                    {d.kondisi ?? '-'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="flex justify-between items-center pt-4 print:hidden">
                    <Link href={route('hendhys.transfer-to-branch.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>

                    <div className="flex gap-3">
                        {t.status === 'sent' && (
                            <Link href={route('hendhys.gudang-transfers.receive-form', t.id)} className="inline-flex items-center gap-2 rounded-2xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700">
                                <Icon name="done_all" className="text-[20px]" /> Konfirmasi Diterima di Cabang
                            </Link>
                        )}
                        <a href={route('hendhys.transfer-requests.print-gudang', t.id)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                            <Icon name="print" className="text-[20px]" /> Cetak BAST
                        </a>
                    </div>
                </div>
            </div>
        </HendhysLayout>
    );
}
