import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty } from '@/lib/format';

const route = window.route;

export default function TransferRequestsShow({ request }) {
    const totalQty = request.details?.reduce((sum, item) => sum + item.quantity_requested, 0) ?? 0;

    return (
        <HendhysLayout pageTitle={`Detail Request ${request.request_number}`}>
            <Head title={request.request_number} />

            <div className="mx-auto max-w-6xl space-y-6 pb-12">

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Main Content (Left column) */}
                    <div className="space-y-6 lg:col-span-2">
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="p-6 md:p-8 space-y-8">
                                <div className="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                                    <div className="space-y-1.5">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h1 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{request.request_number}</h1>
                                            <StatusBadge status={request.status} />
                                        </div>
                                        <p className="text-xs font-semibold text-gray-455 dark:text-gray-500">Tanggal Request: {formatDate(request.date)}</p>
                                    </div>
                                    
                                    <div className="flex min-w-[240px] items-center gap-4 rounded-xl border border-gray-150 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/30 dark:bg-white/[0.01]">
                                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 shadow-inner">
                                            <Icon name="warehouse" className="text-[22px]" />
                                        </div>
                                        <div>
                                            <p className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-555">Tujuan Permintaan</p>
                                            <p className="text-sm font-bold text-gray-800 dark:text-white/90">Gudang Utama</p>
                                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400">Pusat Distribusi</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Daftar Barang</h3>
                                        <span className="rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-550 dark:text-gray-400">{request.details?.length ?? 0} SKU</span>
                                    </div>
                                    <div className="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                                        <table className="w-full border-collapse text-left text-sm">
                                            <thead>
                                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-855 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider dark:border-gray-800 dark:bg-white/[0.01]">
                                                    <th className="px-6 py-3.5">Produk</th>
                                                    <th className="px-4 py-3.5 text-center w-32">Qty Diminta</th>
                                                    <th className="px-4 py-3.5 text-center w-32">Qty Disetujui</th>
                                                    <th className="px-6 py-3.5 text-center w-28">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                {request.details?.map((item, index) => (
                                                    <tr key={index} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                        <td className="px-6 py-4.5">
                                                            <div className="flex flex-col">
                                                                <span className="text-sm font-bold text-gray-800 dark:text-white/90">{item.product}</span>
                                                                <span className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-550 dark:text-gray-500">{item.product_code}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4.5 text-center font-bold text-gray-750 dark:text-gray-300 tabular-nums">{formatQty(item.quantity_requested)}</td>
                                                        <td className="px-4 py-4.5 text-center font-bold text-gray-850 dark:text-white tabular-nums dark:text-white/90">
                                                            {item.quantity_approved !== null ? formatQty(item.quantity_approved) : '-'}
                                                        </td>
                                                        <td className="px-6 py-4.5 text-center">
                                                            <span className="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400">
                                                                {item.unit}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {request.notes && (
                            <div className="flex items-start gap-4.5 rounded-2xl border border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-gray-900/30 dark:bg-white/[0.01]">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-500 border border-gray-250 dark:border-gray-700 shadow-theme-xs dark:text-amber-400 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <Icon name="description" className="text-[20px]" />
                                </div>
                                <div>
                                    <h4 className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500">Catatan Peminta</h4>
                                    <p className="text-sm font-medium italic text-gray-850 dark:text-white/95 mt-1 dark:text-white/90">"{request.notes}"</p>
                                </div>
                            </div>
                        )}

                        {request.transfer_outs?.length > 0 && (
                            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                                <div className="flex items-center gap-2 border-b border-gray-150 bg-gray-50 p-4 dark:border-gray-850 dark:bg-white/[0.02] dark:border-gray-800">
                                    <Icon name="local_shipping" className="text-[20px] text-amber-600 dark:text-amber-550 dark:text-amber-400" />
                                    <h3 className="text-base font-bold text-gray-800 dark:text-white/90">Pengiriman Terkait</h3>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="w-full border-collapse text-left text-sm">
                                        <thead>
                                            <tr className="border-b border-gray-150 bg-gray-50/50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 dark:bg-white/[0.01]">
                                                <th className="px-6 py-3.5">No. Transfer (DO)</th>
                                                <th className="px-6 py-3.5">Tanggal</th>
                                                <th className="px-6 py-3.5 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                            {request.transfer_outs.map((t) => (
                                                <tr key={t.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                                    <td className="px-6 py-4 font-mono font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</td>
                                                    <td className="px-6 py-4 text-gray-650 dark:text-gray-300">{t.date}</td>
                                                    <td className="px-6 py-4 text-center">
                                                        <StatusBadge status={t.status} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Sidebar widgets (Right column) */}
                    <div className="space-y-6">
                        {/* Audit Trail Card */}
                        <div className="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                            <h3 className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Audit Trail</h3>
                            
                            <div className="flex items-start gap-3.5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-150 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-450 dark:text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
                                    <Icon name="person" className="text-[18px]" />
                                </div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500">Diminta Oleh</p>
                                    <p className="text-xs font-bold text-gray-800 dark:text-white/90 mt-0.5">{request.creator ?? '-'}</p>
                                    <p className="text-[9px] font-semibold text-gray-400 dark:text-gray-550 mt-1 tabular-nums dark:text-gray-500">{formatDate(request.date)}</p>
                                </div>
                            </div>

                            {request.approver && (
                                <div className="flex items-start gap-3.5">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-900/20 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        <Icon name="verified" className="text-[18px]" />
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-500">Disetujui Oleh</p>
                                        <p className="text-xs font-bold text-gray-800 dark:text-white/90 mt-0.5">{request.approver}</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Document Summary Card */}
                        <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="mb-4 flex items-center gap-2">
                                <Icon name="info" className="text-[20px] text-amber-600 dark:text-amber-400" />
                                <h3 className="text-xs font-bold uppercase tracking-wider text-gray-750 dark:text-white/90 dark:text-gray-300">Ringkasan Dokumen</h3>
                            </div>
                            <div className="space-y-1">
                                <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-850 py-3.5 text-xs dark:border-gray-800">
                                    <span className="font-semibold text-gray-500 dark:text-gray-450 dark:text-gray-400">Total Item</span>
                                    <span className="font-bold tabular-nums text-sm text-gray-800 dark:text-white/90">{request.details?.length ?? 0} SKU</span>
                                </div>
                                <div className="flex items-center justify-between py-3.5 text-xs">
                                    <span className="font-semibold text-gray-500 dark:text-gray-450 dark:text-gray-400">Total Kuantitas</span>
                                    <span className="font-bold tabular-nums text-sm text-gray-800 dark:text-white/90">{formatQty(totalQty)} Unit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex justify-start pt-4 print:hidden">
                    <Link href={route('hendhys.transfer-requests.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-755 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar Request
                    </Link>
                </div>
            </div>
        </HendhysLayout>
    );
}
