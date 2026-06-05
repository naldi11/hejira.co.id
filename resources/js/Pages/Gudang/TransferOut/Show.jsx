import { Head, Link } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

export default function TransferOutShow({ transfer }) {
    return (
        <GudangLayout title={`Transfer Keluar ${transfer.transfer_number}`} pageTitle={`Transfer Keluar — ${transfer.transfer_number}`}>
            <Head title={transfer.transfer_number} />

            <div className="max-w-5xl space-y-6 print:max-w-none print:space-y-4">
                <div className="flex justify-end print:hidden">
                    <Button onClick={() => window.print()} size="sm" startIcon={<Icon name="print" className="text-[18px]" />}>
                        Cetak Surat Jalan
                    </Button>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{transfer.transfer_number}</h2>
                    <p className="mt-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Dikirim pada {formatDate(transfer.date)} oleh {transfer.creator ?? 'Sistem'}</p>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <h3 className="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Informasi Tujuan Pengiriman</h3>
                        <div className="space-y-3.5">
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Dikirim Ke (Entitas)</p>
                                <p className="text-sm font-semibold text-gray-800 dark:text-white/90 capitalize">{transfer.to_entity === 'hendhys' ? `Hendhys - ${transfer.branch ?? 'Cabang'}` : 'Jihans - Stok Produksi'}</p>
                            </div>
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Catatan Pengiriman</p>
                                <p className="text-sm text-gray-700 dark:text-gray-300 font-semibold">{transfer.notes || '—'}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <h3 className="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Informasi Referensi</h3>
                        <div className="space-y-3.5">
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-550">Referensi Request</p>
                                {transfer.request ? (
                                    <Link href={route('gudang.transfer-requests.show', transfer.request.id)} className="text-sm font-bold text-brand-500 dark:text-brand-400 hover:underline">{transfer.request.request_number}</Link>
                                ) : (
                                    <p className="text-sm italic text-gray-400 dark:text-gray-550 font-semibold">Tanpa Request (Pengiriman Langsung)</p>
                                )}
                            </div>
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-550">Status Stok</p>
                                <p className="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-450"><Icon name="check_circle" className="text-[16px]" /> Stok telah berhasil dipindahkan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 p-5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Item Produk yang Dikirim</h3>
                        <span className="rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-550 dark:text-gray-400">Nilai HPP dicatat untuk mutasi</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-3.5">Produk</th>
                                    <th className="px-4 py-3.5 text-center w-32">Qty Dikirim</th>
                                    <th className="px-4 py-3.5 text-center w-32">Satuan</th>
                                    <th className="px-6 py-3.5 text-right w-44">HPP / Unit</th>
                                    <th className="px-6 py-3.5 text-right w-48">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {(transfer.details ?? []).map((item, i) => (
                                    <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                        <td className="px-6 py-4.5 font-bold text-gray-800 dark:text-white/90">{item.product}</td>
                                        <td className="px-4 py-4.5 text-center font-bold text-gray-700 dark:text-gray-300 tabular-nums">{formatQty(item.quantity)}</td>
                                        <td className="px-4 py-4.5 text-center text-gray-500 dark:text-gray-400 font-semibold">{item.unit}</td>
                                        <td className="px-6 py-4.5 text-right text-gray-550 dark:text-gray-400 font-semibold tabular-nums">{formatRupiah(item.hpp_price)}</td>
                                        <td className="px-6 py-4.5 text-right font-bold text-gray-850 dark:text-white/90 tabular-nums">{formatRupiah(item.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="border-t border-gray-150 bg-gray-50/30 dark:border-gray-800 dark:bg-white/[0.01]">
                                <tr>
                                    <td colSpan={4} className="px-6 py-5 text-right font-bold text-gray-700 dark:text-gray-300">Total Nilai Mutasi Barang:</td>
                                    <td className="px-6 py-5 text-right text-base font-extrabold text-brand-500 dark:text-brand-400 tabular-nums">{formatRupiah(transfer.grand_total ?? 0)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div className="flex justify-start pt-4 print:hidden">
                    <Link href={route('gudang.transfer-out.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                </div>
            </div>
        </GudangLayout>
    );
}
