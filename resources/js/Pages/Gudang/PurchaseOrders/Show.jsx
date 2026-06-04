import { Head, Link, router } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';

const route = window.route;

export default function PurchaseOrderShow({ po }) {
    const totalOrdered = po.details.reduce((s, d) => s + d.quantity_ordered, 0);
    const totalReceived = po.details.reduce((s, d) => s + d.quantity_received, 0);
    const progress = totalOrdered > 0 ? Math.min(100, Math.round((totalReceived / totalOrdered) * 100)) : 0;
    const showProgress = !['draft', 'cancelled'].includes(po.status);

    const cancel = () => {
        if (window.confirm('Batalkan PO ini? Tindakan tidak bisa dibatalkan.')) {
            router.post(route('gudang.po.cancel', po.id), {}, { preserveScroll: true });
        }
    };

    const actionBtn = 'inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-bold transition-all';

    return (
        <GudangLayout title={`Detail PO ${po.po_number}`} pageTitle={`Purchase Order — ${po.po_number}`}>
            <Head title={po.po_number} />

            <div className="space-y-6">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="p-6">
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                            <div>
                                <div className="mb-1 flex items-center gap-3">
                                    <h2 className="font-mono text-2xl font-black tracking-tight text-slate-900">{po.po_number}</h2>
                                    <StatusBadge status={po.status} />
                                </div>
                                <p className="text-xs text-slate-400">Dibuat oleh <span className="font-semibold text-slate-600">{po.creator}</span> · {po.created_at}</p>
                            </div>

                            <div className="flex flex-wrap items-center gap-2 print:hidden">
                                <Link href={route('gudang.po.index')} className={`${actionBtn} border border-slate-200 text-slate-500 hover:bg-slate-50`}><Icon name="arrow_back" className="text-[16px]" /> Kembali</Link>
                                {po.status === 'draft' && (
                                    <Link href={route('gudang.po.edit', po.id)} className={`${actionBtn} border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100`}><Icon name="edit" className="text-[16px]" /> Edit PO</Link>
                                )}
                                {['draft', 'sent', 'partial'].includes(po.status) && (
                                    <a href={route('gudang.receiving.create', { po_id: po.id })} className={`${actionBtn} bg-green-600 text-white shadow-lg shadow-green-600/20 hover:bg-green-700`}><Icon name="inventory" className="text-[16px]" /> Terima Barang</a>
                                )}
                                <a href={route('gudang.po.print', po.id)} target="_blank" rel="noreferrer" className={`${actionBtn} bg-slate-800 text-white hover:bg-slate-900`}><Icon name="print" className="text-[16px]" /> Cetak PO</a>
                                {['draft', 'sent'].includes(po.status) && (
                                    <button onClick={cancel} className={`${actionBtn} border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100`}><Icon name="cancel" className="text-[16px]" /> Batalkan</button>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-6 md:grid-cols-4">
                            <div>
                                <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Supplier</p>
                                <p className="font-bold text-slate-800">{po.supplier}</p>
                            </div>
                            <div>
                                <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Tanggal PO</p>
                                <p className="font-bold text-slate-800">{formatDate(po.date)}</p>
                            </div>
                            <div>
                                <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Est. Tiba</p>
                                <p className="font-bold text-slate-800">{po.expected_date ? formatDate(po.expected_date) : '—'}</p>
                            </div>
                            <div>
                                <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Total Tagihan</p>
                                <p className="text-lg font-black text-indigo-600">{formatRupiah(po.total_amount)}</p>
                            </div>
                            {po.notes && (
                                <div className="col-span-2 md:col-span-4">
                                    <p className="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">Catatan</p>
                                    <p className="text-sm text-slate-600">{po.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {showProgress && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-xs font-black uppercase tracking-wider text-slate-500">Progress Penerimaan</span>
                            <span className={`text-sm font-black ${progress >= 100 ? 'text-green-600' : 'text-indigo-600'}`}>{progress}%</span>
                        </div>
                        <div className="h-2.5 w-full rounded-full bg-slate-100">
                            <div className={`h-2.5 rounded-full transition-all duration-500 ${progress >= 100 ? 'bg-green-500' : 'bg-indigo-500'}`} style={{ width: `${progress}%` }} />
                        </div>
                        <p className="mt-1.5 text-xs text-slate-400">{formatQty(totalReceived)} dari {formatQty(totalOrdered)} unit diterima</p>
                    </div>
                )}

                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                        <h3 className="text-sm font-black uppercase tracking-wider text-slate-700">Daftar Item Pesanan</h3>
                        <span className="text-xs font-bold text-slate-400">{po.details.length} item</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-500">
                                    <th className="px-6 py-3">Produk</th>
                                    <th className="px-4 py-3 text-center">Qty Order</th>
                                    <th className="px-4 py-3 text-center">Qty Diterima</th>
                                    <th className="px-4 py-3 text-center">Satuan</th>
                                    <th className="px-4 py-3 text-right">Harga Satuan</th>
                                    <th className="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {po.details.map((d, i) => {
                                    const fulfilled = d.quantity_received >= d.quantity_ordered;
                                    const recvClass = fulfilled ? 'bg-green-100 text-green-700' : d.quantity_received > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500';
                                    return (
                                        <tr key={i} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-4">
                                                <p className="font-bold text-slate-800">{d.product}</p>
                                                {d.notes && <p className="mt-0.5 text-xs italic text-slate-400">{d.notes}</p>}
                                            </td>
                                            <td className="px-4 py-4 text-center font-black text-slate-700">{d.quantity_ordered}</td>
                                            <td className="px-4 py-4 text-center"><span className={`inline-flex min-w-[2rem] items-center justify-center rounded-lg px-2 py-0.5 text-xs font-black ${recvClass}`}>{d.quantity_received}</span></td>
                                            <td className="px-4 py-4 text-center font-mono text-xs uppercase text-slate-500">{d.unit}</td>
                                            <td className="px-4 py-4 text-right font-semibold text-slate-600">{formatRupiah(d.price)}</td>
                                            <td className="px-6 py-4 text-right font-black text-slate-800">{formatRupiah(d.total)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-indigo-200 bg-indigo-50">
                                    <td colSpan={5} className="px-6 py-4 text-right text-sm font-black uppercase tracking-wider text-indigo-700">Grand Total</td>
                                    <td className="px-6 py-4 text-right text-xl font-black text-indigo-700">{formatRupiah(po.total_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {po.receivings?.length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                            <Icon name="inventory_2" className="text-[20px] text-green-600" />
                            <h3 className="text-sm font-black uppercase tracking-wider text-slate-700">Riwayat Penerimaan Barang</h3>
                        </div>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-500">
                                        <th className="px-6 py-3 text-left">No. GRN</th>
                                        <th className="px-4 py-3 text-left">Tanggal Terima</th>
                                        <th className="px-4 py-3 text-center">Jml Item</th>
                                        <th className="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {po.receivings.map((grn) => (
                                        <tr key={grn.id} className="transition-colors hover:bg-slate-50/50">
                                            <td className="px-6 py-3 font-mono text-xs font-black text-indigo-700">{grn.grn_number}</td>
                                            <td className="px-4 py-3 font-semibold text-slate-600">{grn.date}</td>
                                            <td className="px-4 py-3 text-center"><span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-black text-slate-700">{grn.details_count}</span></td>
                                            <td className="px-6 py-3 text-right">
                                                <a href={route('gudang.receiving.show', grn.id)} className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 transition-all hover:bg-indigo-50 hover:text-indigo-700"><Icon name="visibility" className="text-[14px]" /> Detail</a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </GudangLayout>
    );
}
