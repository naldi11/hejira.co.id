import { Head, Link, router } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty, formatRupiah } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

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

    return (
        <GudangLayout title={`Detail PO ${po.po_number}`} pageTitle={`Purchase Order — ${po.po_number}`}>
            <Head title={po.po_number} />

            <div className="space-y-6">
                <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs md:p-6">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div>
                            <div className="mb-2 flex items-center gap-3">
                                <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{po.po_number}</h2>
                                <StatusBadge status={po.status} />
                            </div>
                            <p className="text-xs text-gray-400 dark:text-gray-500">
                                Dibuat oleh <span className="font-semibold text-gray-700 dark:text-gray-300">{po.creator}</span> · {po.created_at}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 print:hidden">
                            <Link href={route('gudang.po.index')}>
                                <Button variant="outline" size="sm" startIcon={<Icon name="arrow_back" className="text-[16px]" />}>
                                    Kembali
                                </Button>
                            </Link>
                            {po.status === 'draft' && (
                                <Link href={route('gudang.po.edit', po.id)}>
                                    <Button variant="outline" size="sm" startIcon={<Icon name="edit" className="text-[16px]" />}>
                                        Edit PO
                                    </Button>
                                </Link>
                            )}
                            {['draft', 'sent', 'partial'].includes(po.status) && (
                                <a href={route('gudang.receiving.create', { po_id: po.id })}>
                                    <Button size="sm" startIcon={<Icon name="inventory" className="text-[16px]" />} className="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-700">
                                        Terima Barang
                                    </Button>
                                </a>
                            )}
                            <a href={route('gudang.po.print', po.id)} target="_blank" rel="noreferrer">
                                <Button variant="outline" size="sm" startIcon={<Icon name="print" className="text-[16px]" />}>
                                    Cetak PO
                                </Button>
                            </a>
                            {['draft', 'sent'].includes(po.status) && (
                                <Button
                                    onClick={cancel}
                                    size="sm"
                                    className="bg-rose-600 hover:bg-rose-700 text-white dark:bg-rose-600 dark:hover:bg-rose-700"
                                    startIcon={<Icon name="cancel" className="text-[16px]" />}
                                >
                                    Batalkan
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-2 gap-4 border-t border-gray-100 pt-6 dark:border-gray-800 md:grid-cols-4">
                        <div>
                            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Supplier</p>
                            <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{po.supplier}</p>
                        </div>
                        <div>
                            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Tanggal PO</p>
                            <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{formatDate(po.date)}</p>
                        </div>
                        <div>
                            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Est. Tiba</p>
                            <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{po.expected_date ? formatDate(po.expected_date) : '—'}</p>
                        </div>
                        <div>
                            <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Tagihan</p>
                            <p className="text-base font-bold text-brand-500 dark:text-brand-400">{formatRupiah(po.total_amount)}</p>
                        </div>
                        {po.notes && (
                            <div className="col-span-2 md:col-span-4 mt-2">
                                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Catatan</p>
                                <p className="text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                                    {po.notes}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {showProgress && (
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Progress Penerimaan</span>
                            <span className={`text-sm font-bold ${progress >= 100 ? 'text-green-600' : 'text-brand-500 dark:text-brand-400'}`}>{progress}%</span>
                        </div>
                        <div className="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div className={`h-2 rounded-full transition-all duration-500 ${progress >= 100 ? 'bg-green-500' : 'bg-brand-500'}`} style={{ width: `${progress}%` }} />
                        </div>
                        <p className="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{formatQty(totalReceived)} dari {formatQty(totalOrdered)} unit diterima</p>
                    </div>
                )}

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex items-center justify-between border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Daftar Item Pesanan</h3>
                        <span className="text-xs font-semibold text-gray-400 dark:text-gray-500">{po.details.length} item</span>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                    <th className="px-6 py-3.5">Produk</th>
                                    <th className="px-4 py-3.5 text-center">Qty Order</th>
                                    <th className="px-4 py-3.5 text-center">Qty Diterima</th>
                                    <th className="px-4 py-3.5 text-center">Satuan</th>
                                    <th className="px-4 py-3.5 text-right">Harga Satuan</th>
                                    <th className="px-6 py-3.5 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {po.details.map((d, i) => {
                                    const fulfilled = d.quantity_received >= d.quantity_ordered;
                                    const recvClass = fulfilled
                                        ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                        : d.quantity_received > 0
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
                                    return (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4">
                                                <p className="font-semibold text-gray-800 dark:text-white/90">{d.product}</p>
                                                {d.notes && <p className="mt-0.5 text-xs italic text-gray-400 dark:text-gray-500">{d.notes}</p>}
                                            </td>
                                            <td className="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">{d.quantity_ordered}</td>
                                            <td className="px-4 py-4 text-center">
                                                <span className={`inline-flex min-w-[2rem] items-center justify-center rounded-lg px-2 py-0.5 text-xs font-bold ${recvClass}`}>
                                                    {d.quantity_received}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-center font-mono text-xs uppercase text-gray-400 dark:text-gray-500">{d.unit}</td>
                                            <td className="px-4 py-4 text-right font-semibold text-gray-600 dark:text-gray-400">{formatRupiah(d.price)}</td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-800 dark:text-white/90">{formatRupiah(d.total)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot>
                                <tr className="border-t border-brand-100 bg-brand-50/30 dark:border-brand-900/50 dark:bg-brand-500/5">
                                    <td colSpan={5} className="px-6 py-4.5 text-right text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Grand Total</td>
                                    <td className="px-6 py-4.5 text-right text-base font-bold text-brand-600 dark:text-brand-400">{formatRupiah(po.total_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {po.receivings?.length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="flex items-center gap-3 border-b border-gray-150 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <Icon name="inventory_2" className="text-[20px] text-green-600 dark:text-green-400" />
                            <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Riwayat Penerimaan Barang</h3>
                        </div>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                        <th className="px-6 py-3.5 text-left">No. GRN</th>
                                        <th className="px-4 py-3.5 text-left">Tanggal Terima</th>
                                        <th className="px-4 py-3.5 text-center">Jml Item</th>
                                        <th className="px-6 py-3.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {po.receivings.map((grn) => (
                                        <tr key={grn.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-3 font-mono text-xs font-bold text-brand-500 dark:text-brand-400">{grn.grn_number}</td>
                                            <td className="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">{grn.date}</td>
                                            <td className="px-4 py-3 text-center">
                                                <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-700 dark:bg-gray-850 dark:text-gray-300">
                                                    {grn.details_count}
                                                </span>
                                            </td>
                                            <td className="px-6 py-3 text-right">
                                                <a href={route('gudang.receiving.show', grn.id)}>
                                                    <Button variant="outline" size="sm" startIcon={<Icon name="visibility" className="text-[14px]" />}>
                                                        Detail
                                                    </Button>
                                                </a>
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

