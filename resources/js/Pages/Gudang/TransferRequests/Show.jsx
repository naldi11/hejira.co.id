import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import Modal from '@/Components/Modal';
import { formatDate, formatQty } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const ENTITY_META = {
    hendhys: { icon: 'cake', color: 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' },
    jihans: { icon: 'bakery_dining', color: 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400' },
};

function RejectModal({ show, onClose, requestId }) {
    const { data, setData, post, processing, errors } = useForm({ rejection_reason: '' });
    const submit = (e) => {
        e.preventDefault();
        post(route('gudang.transfer-requests.reject', requestId), { preserveScroll: true, onSuccess: onClose });
    };

    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-850 resize-none';

    return (
        <Modal show={show} onClose={onClose} title="Tolak Request" subtitle="Permintaan tidak disetujui" icon="block" maxWidth="max-w-md">
            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Alasan Penolakan <span className="text-rose-500">*</span></label>
                    <textarea required rows={3} value={data.rejection_reason} onChange={(e) => setData('rejection_reason', e.target.value)} placeholder="Jelaskan alasan penolakan..."
                        className={areaClass} />
                    {errors.rejection_reason && <p className="text-xs font-bold text-rose-600 dark:text-rose-455">{errors.rejection_reason}</p>}
                </div>
                <Button type="submit" disabled={processing} className="w-full">
                    {processing ? 'Memproses...' : 'Konfirmasi Penolakan'}
                </Button>
            </form>
        </Modal>
    );
}

export default function TransferRequestShow({ request }) {
    const isPending = request.status === 'pending';
    const meta = ENTITY_META[request.from_entity] ?? { icon: 'inventory', color: 'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400' };
    const [rejecting, setRejecting] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        items: request.details.map((d) => ({ id: d.id, quantity_approved: d.quantity_requested })),
        notes: '',
    });

    const setQty = (id, value) => {
        setData('items', data.items.map((it) => (it.id === id ? { ...it, quantity_approved: value } : it)));
    };

    const approve = (e) => {
        e.preventDefault();
        post(route('gudang.transfer-requests.approve', request.id), { preserveScroll: true });
    };

    const totalQty = request.details.reduce((s, d) => s + d.quantity_requested, 0);
    const cellInput = 'w-24 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-center text-xs font-bold text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';

    return (
        <GudangLayout title="Detail Transfer Request" pageTitle="Review Permintaan">
            <Head title={request.request_number} />

            <form id="approve-form" onSubmit={approve} className="mx-auto max-w-6xl space-y-6 pb-12">
                {errors.items && (
                    <div className="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/30 dark:bg-rose-500/5">
                        <Icon name="error" className="mt-0.5 shrink-0 text-[20px] text-rose-500 dark:text-rose-455" />
                        <div className="space-y-1">
                            <p className="text-sm font-bold text-rose-600 dark:text-rose-350">Terjadi Kesalahan</p>
                            <p className="text-xs font-semibold text-rose-600 dark:text-rose-400">{errors.items}</p>
                        </div>
                    </div>
                )}

                <div className="flex justify-end">
                    {['approved', 'partial'].includes(request.status) && (
                        <Link href={route('gudang.transfer-out.create', { request_id: request.id })}>
                            <Button size="sm" startIcon={<Icon name="local_shipping" className="text-[18px]" />}>
                                Proses Pengiriman
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {/* Main Document Details */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="p-6 md:p-8 space-y-8">
                                <div className="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                                    <div className="space-y-1.5">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <h1 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{request.request_number}</h1>
                                            <StatusBadge status={request.status} />
                                        </div>
                                        <p className="text-xs font-semibold text-gray-450 dark:text-gray-500">Tanggal Permintaan: {formatDate(request.date)}</p>
                                    </div>
                                    <div className="flex min-w-[240px] items-center gap-4 rounded-xl border border-gray-150 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/30">
                                        <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-inner ${meta.color}`}>
                                            <Icon name={meta.icon} className="text-[22px]" />
                                        </div>
                                        <div>
                                            <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Asal Permintaan</p>
                                            <p className="text-sm font-bold capitalize text-gray-800 dark:text-white/90">{request.from_entity}</p>
                                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400">{request.branch ?? 'Produksi Pusat'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-305">Daftar Barang</h3>
                                        <span className="rounded-lg bg-gray-100 dark:bg-gray-850 px-2.5 py-1 text-xs font-semibold text-gray-550 dark:text-gray-400">{request.details.length} SKU</span>
                                    </div>
                                    <div className="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 shadow-theme-xs">
                                        <table className="w-full border-collapse text-left text-sm">
                                            <thead>
                                                <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                                    <th className="px-6 py-3.5">Produk</th>
                                                    <th className="px-4 py-3.5 text-center w-28">Diminta</th>
                                                    {isPending && <th className="px-4 py-3.5 text-center w-36">Stok Gudang</th>}
                                                    <th className="px-4 py-3.5 text-center w-32">Qty Disetujui</th>
                                                    <th className="px-6 py-3.5 text-center w-28">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                {request.details.map((item) => {
                                                    const hasEnough = item.warehouse_stock >= item.quantity_requested;
                                                    const current = data.items.find((it) => it.id === item.id);
                                                    return (
                                                        <tr key={item.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                            <td className="px-6 py-4.5">
                                                                <div className="flex flex-col">
                                                                    <span className="text-sm font-bold text-gray-800 dark:text-white/90">{item.product}</span>
                                                                    <span className="mt-1 font-mono text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">{item.product_code}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-4.5 text-center font-bold text-gray-700 dark:text-gray-300 tabular-nums">{formatQty(item.quantity_requested)}</td>
                                                            {isPending && (
                                                                <td className="px-4 py-4.5 text-center">
                                                                    <div className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 text-xs font-bold ${hasEnough ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-450 border border-emerald-100 dark:border-emerald-900/30' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-455 border border-rose-100 dark:border-rose-900/30'}`}>
                                                                        <span className="tabular-nums">{formatQty(item.warehouse_stock)}</span>
                                                                        {!hasEnough && <Icon name="warning" className="text-[14px]" />}
                                                                    </div>
                                                                </td>
                                                            )}
                                                            <td className="px-4 py-4.5 text-center">
                                                                {isPending ? (
                                                                    <input type="number" min="0.001" step="any" max={item.quantity_requested} value={current?.quantity_approved ?? ''}
                                                                        onChange={(e) => setQty(item.id, e.target.value)}
                                                                        className={cellInput} />
                                                                ) : (
                                                                    <span className="text-sm font-bold tabular-nums text-gray-700 dark:text-gray-300">{item.quantity_approved !== null ? formatQty(item.quantity_approved) : '-'}</span>
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-4.5 text-center">
                                                                <span className="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400">
                                                                    {item.unit}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {isPending && (
                                <div className="border-t border-gray-150 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.02] flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                                    <div className="space-y-1">
                                        <h4 className="text-sm font-bold text-gray-800 dark:text-white/90">Keputusan Review Approval</h4>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Sesuaikan kuantitas disetujui, kemudian klik Setujui atau Tolak.</p>
                                    </div>
                                    <div className="flex w-full shrink-0 items-center gap-3 sm:w-auto">
                                        <Button type="button" variant="outline" onClick={() => setRejecting(true)} className="flex-1 sm:flex-none">
                                            Tolak Request
                                        </Button>
                                        <Button type="submit" form="approve-form" disabled={processing} className="flex-1 sm:flex-none">
                                            {processing ? 'Memproses...' : 'Setujui (Approve)'}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {request.notes && (
                            <div className="flex items-start gap-4.5 rounded-2xl border border-gray-200 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-gray-900/30">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-gray-800 text-brand-500 border border-gray-250 dark:border-gray-700 shadow-sm">
                                    <Icon name="description" className="text-[20px]" />
                                </div>
                                <div>
                                    <h4 className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Catatan Peminta</h4>
                                    <p className="text-sm font-medium italic text-gray-850 dark:text-white/95 mt-1">"{request.notes}"</p>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="space-y-6">
                        {/* Audit Trail Card */}
                        <div className="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
                            <h3 className="text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Audit Trail</h3>
                            <div className="flex items-start gap-3.5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-150 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-450"><Icon name="person" className="text-[18px]" /></div>
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Diminta Oleh</p>
                                    <p className="text-xs font-bold text-gray-800 dark:text-white/90 mt-0.5">{request.requester ?? '-'}</p>
                                    <p className="text-[9px] font-semibold text-gray-400 dark:text-gray-550 mt-1 tabular-nums">{request.created_at}</p>
                                </div>
                            </div>
                            {request.approver && (
                                <div className="flex items-start gap-3.5">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-brand-100 bg-brand-50 text-brand-500 dark:border-brand-900/20 dark:bg-brand-500/10 dark:text-brand-400"><Icon name="verified" className="text-[18px]" /></div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Disetujui Oleh</p>
                                        <p className="text-xs font-bold text-gray-800 dark:text-white/90 mt-0.5">{request.approver}</p>
                                        <p className="text-[9px] font-semibold text-gray-400 dark:text-gray-550 mt-1 tabular-nums">{request.approved_at ?? '-'}</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Summary Summary Card */}
                        <div className="rounded-2xl bg-gray-950 p-5 text-white dark:bg-white/[0.03] dark:border dark:border-gray-800 shadow-xl">
                            <div className="mb-4 flex items-center gap-2">
                                <Icon name="info" className="text-[20px] text-brand-500" />
                                <h3 className="text-xs font-bold uppercase tracking-wider">Ringkasan Dokumen</h3>
                            </div>
                            <div className="space-y-1">
                                <div className="flex items-center justify-between border-b border-gray-850 py-3.5 text-xs">
                                    <span className="font-semibold text-gray-400 dark:text-gray-500">Total Item</span>
                                    <span className="font-bold tabular-nums text-sm">{request.details.length} SKU</span>
                                </div>
                                <div className="flex items-center justify-between py-3.5 text-xs">
                                    <span className="font-semibold text-gray-400 dark:text-gray-500">Total Kuantitas</span>
                                    <span className="font-bold tabular-nums text-sm">{formatQty(totalQty)} Unit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="flex justify-start pt-4">
                    <Link href={route('gudang.transfer-requests.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                </div>
            </form>

            <RejectModal show={rejecting} onClose={() => setRejecting(false)} requestId={request.id} />
        </GudangLayout>
    );
}
