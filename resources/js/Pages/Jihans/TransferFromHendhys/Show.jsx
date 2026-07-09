import { Head, Link, usePage } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function TransferToBranchShow({ transfer: t }) {
    const { auth } = usePage().props;
    const isCabang = auth?.user?.branch?.type === 'cabang';
    const isPusat  = auth?.user?.branch?.type === 'pusat';
    const route    = window.route;
    return (
        <JihansLayout pageTitle={`Detail Distribusi ${t.transfer_number}`}>
            <Head title={`Transfer ${t.transfer_number}`} />
            
            <div className="mx-auto max-w-4xl space-y-5">

                {/* Banner Pusat: barang sudah dikirim, menunggu konfirmasi cabang */}
                {isPusat && t.status === 'sent' && (
                    <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 dark:border-amber-500/20 dark:bg-amber-500/10">
                        <Icon name="info" className="mt-0.5 shrink-0 text-[22px] text-amber-500" />
                        <div className="flex-1">
                            <p className="font-semibold text-amber-800 dark:text-amber-300">Stok sudah dikirim ke {t.branch}</p>
                            <p className="mt-0.5 text-sm text-amber-700 dark:text-amber-400">
                                Menunggu konfirmasi penerimaan dari Cabang. Jika Cabang sudah menerima, klik tombol di bawah untuk update stok.
                            </p>
                        </div>
                    </div>
                )}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-250 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanggal: {t.date} · Oleh {t.creator}</p>
                        </div>
                        <StatusBadge status={t.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-3 text-sm dark:border-gray-800">
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Cabang Tujuan</p>
                            <p className="font-medium text-gray-800 dark:text-white/90 mt-1">{t.branch}</p>
                        </div>
                        {t.receiver && (
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Penerima</p>
                                <p className="text-gray-800 dark:text-white/90 mt-1">{t.receiver}</p>
                            </div>
                        )}
                        {t.notes && (
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Catatan</p>
                                <p className="text-gray-800 dark:text-gray-300 mt-1 dark:text-white/90">{t.notes}</p>
                            </div>
                        )}
                        {t.receive_kendala && (
                            <div className="col-span-1 md:col-span-3 rounded-xl border border-red-200 dark:border-red-800/40 bg-red-50 dark:bg-red-950/20 p-4 text-red-650 dark:text-red-400 dark:bg-red-500/10">
                                <p className="text-xs font-semibold uppercase tracking-wider">Kendala Penerimaan</p>
                                <p className="mt-1 font-medium">{t.receive_kendala}</p>
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
                                                <span className="rounded-full bg-gray-100 dark:bg-gray-850 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:text-gray-300 capitalize">
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
                    <Link href={route('jihans.transfer-from-hendhys.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>

                    <div className="flex gap-3">
                        {/* Cabang: tombol konfirmasi penerimaan */}
                        {isCabang && t.status === 'sent' && (
                            <Link href={route('jihans.transfer-from-hendhys.receive-form', t.id)} className="inline-flex items-center gap-2 rounded-xl bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 text-sm font-bold shadow-sm transition-colors">
                                <Icon name="check_circle" className="text-[20px]" /> Konfirmasi Penerimaan
                            </Link>
                        )}

                        <a href={route('jihans.transfer-from-hendhys.bast', t.id)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                            <Icon name="print" className="text-[20px]" /> Cetak BAST
                        </a>
                    </div>
                </div>
            </div>
        </JihansLayout>
    );
}
