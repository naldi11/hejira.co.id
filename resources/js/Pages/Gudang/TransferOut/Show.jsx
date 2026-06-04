import { Head, Link } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';

const route = window.route;

export default function TransferOutShow({ transfer }) {
    return (
        <GudangLayout title={`Transfer Keluar ${transfer.transfer_number}`} pageTitle={`Transfer Keluar — ${transfer.transfer_number}`}>
            <Head title={transfer.transfer_number} />

            <div className="max-w-5xl">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-800">{transfer.transfer_number}</h2>
                        <p className="mt-1 text-sm text-slate-500">Dikirim pada {formatDate(transfer.date)} oleh {transfer.creator ?? 'Sistem'}</p>
                    </div>
                    <div className="flex gap-2 print:hidden">
                        <Link href={route('gudang.transfer-out.index')} className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50">Kembali</Link>
                        <button onClick={() => window.print()} className="flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-900">
                            <Icon name="print" className="text-[18px]" /> Cetak Surat Jalan
                        </button>
                    </div>
                </div>

                <div className="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 className="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Tujuan Pengiriman</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-xs text-slate-500">Dikirim Ke (Entitas)</p>
                                <p className="text-sm font-medium capitalize text-slate-800">{transfer.to_entity === 'hendhys' ? `Hendhys - ${transfer.branch ?? 'Cabang'}` : 'Jihans - Stok Produksi'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-slate-500">Catatan Pengiriman</p>
                                <p className="text-sm text-slate-800">{transfer.notes || '-'}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 className="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Referensi</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-xs text-slate-500">Referensi Request</p>
                                {transfer.request ? (
                                    <Link href={route('gudang.transfer-requests.show', transfer.request.id)} className="text-sm font-medium text-indigo-600 hover:underline">{transfer.request.request_number}</Link>
                                ) : (
                                    <p className="text-sm italic text-slate-400">Tanpa Request (Pengiriman Langsung)</p>
                                )}
                            </div>
                            <div>
                                <p className="text-xs text-slate-500">Status Stok</p>
                                <p className="flex items-center gap-1 text-sm font-medium text-emerald-600"><Icon name="check_circle" className="text-[18px]" /> Stok telah berhasil dipindahkan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 p-5">
                        <h3 className="text-lg font-semibold text-slate-800">Item Produk yang Dikirim</h3>
                        <span className="rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">Nilai HPP dicatat untuk mutasi</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 text-slate-500">
                                <tr>
                                    <th className="px-5 py-3 font-medium">Produk</th>
                                    <th className="px-5 py-3 text-center font-medium">Qty Dikirim</th>
                                    <th className="px-5 py-3 text-center font-medium">Satuan</th>
                                    <th className="px-5 py-3 text-right font-medium">HPP / Unit</th>
                                    <th className="px-5 py-3 text-right font-medium">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {transfer.details.map((item, i) => (
                                    <tr key={i} className="hover:bg-slate-50">
                                        <td className="px-5 py-3 font-medium text-slate-800">{item.product}</td>
                                        <td className="px-5 py-3 text-center font-bold text-slate-900">{formatQty(item.quantity)}</td>
                                        <td className="px-5 py-3 text-center text-slate-500">{item.unit}</td>
                                        <td className="px-5 py-3 text-right text-slate-500">{formatRupiah(item.hpp_price)}</td>
                                        <td className="px-5 py-3 text-right font-medium text-slate-800">{formatRupiah(item.total)}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot className="bg-slate-50">
                                <tr>
                                    <td colSpan={4} className="px-5 py-4 text-right font-semibold text-slate-700">Total Nilai Mutasi Barang:</td>
                                    <td className="px-5 py-4 text-right text-base font-bold text-slate-900">{formatRupiah(transfer.grand_total)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </GudangLayout>
    );
}
