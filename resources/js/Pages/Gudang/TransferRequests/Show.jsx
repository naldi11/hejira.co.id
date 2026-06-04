import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import Modal from '@/Components/Modal';
import { formatDate, formatQty } from '@/lib/format';

const route = window.route;

const ENTITY_META = {
    hendhys: { icon: 'cake', color: 'bg-amber-100 text-amber-600' },
    jihans: { icon: 'bakery_dining', color: 'bg-orange-100 text-orange-600' },
};

function RejectModal({ show, onClose, requestId }) {
    const { data, setData, post, processing, errors } = useForm({ rejection_reason: '' });
    const submit = (e) => {
        e.preventDefault();
        post(route('gudang.transfer-requests.reject', requestId), { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal show={show} onClose={onClose} title="Tolak Request" subtitle="Permintaan tidak disetujui" icon="block">
            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
                    <label className="ml-1 block text-xs font-black uppercase tracking-widest text-slate-500">Alasan Penolakan <span className="text-rose-500">*</span></label>
                    <textarea required rows={3} value={data.rejection_reason} onChange={(e) => setData('rejection_reason', e.target.value)} placeholder="Jelaskan alasan penolakan..."
                        className="w-full resize-none rounded-2xl border-2 border-slate-100 bg-slate-50 px-6 py-4 text-sm font-medium text-slate-800 transition-all focus:border-rose-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10" />
                    {errors.rejection_reason && <p className="px-2 text-[11px] font-bold text-rose-600">{errors.rejection_reason}</p>}
                </div>
                <button type="submit" disabled={processing} className="w-full rounded-2xl bg-rose-600 px-8 py-4 text-sm font-black uppercase tracking-widest text-white transition-all hover:bg-rose-700 disabled:opacity-50">
                    {processing ? 'Memproses...' : 'Konfirmasi Penolakan'}
                </button>
            </form>
        </Modal>
    );
}

export default function TransferRequestShow({ request }) {
    const isPending = request.status === 'pending';
    const meta = ENTITY_META[request.from_entity] ?? { icon: 'inventory', color: 'bg-slate-100 text-slate-600' };
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

    return (
        <GudangLayout title="Detail Transfer Request" pageTitle="Review Permintaan">
            <Head title={request.request_number} />

            <form id="approve-form" onSubmit={approve} className="mx-auto max-w-6xl space-y-8 pb-12">
                {errors.items && (
                    <div className="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-rose-800">
                        <div className="flex items-center gap-2 text-sm font-black uppercase tracking-wider"><Icon name="error" className="text-[20px]" /> Terjadi Kesalahan</div>
                        <p className="mt-2 text-xs font-semibold">{errors.items}</p>
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <Link href={route('gudang.transfer-requests.index')} className="group inline-flex items-center gap-2 font-bold text-slate-500 transition-colors hover:text-slate-900">
                        <Icon name="arrow_back" className="text-[20px] transition-transform group-hover:-translate-x-1" /> Kembali ke Daftar
                    </Link>
                    {['approved', 'partial'].includes(request.status) && (
                        <Link href={route('gudang.transfer-out.create', { request_id: request.id })} className="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/20 transition-all hover:bg-indigo-700">
                            <Icon name="local_shipping" className="text-[20px]" /> Proses Pengiriman
                        </Link>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div className="space-y-8 lg:col-span-2">
                        <div className="overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-sm">
                            <div className="p-8 sm:p-10">
                                <div className="flex flex-col justify-between gap-6 md:flex-row md:items-start">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-3">
                                            <h1 className="font-headline text-3xl font-black tracking-tight text-slate-900">{request.request_number}</h1>
                                            <StatusBadge status={request.status} />
                                        </div>
                                        <p className="font-bold tracking-wide text-slate-500">Tanggal Permintaan: {formatDate(request.date)}</p>
                                    </div>
                                    <div className="flex min-w-[240px] items-center gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4">
                                        <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-inner ${meta.color}`}>
                                            <Icon name={meta.icon} className="text-[28px]" />
                                        </div>
                                        <div>
                                            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">Asal Permintaan</p>
                                            <p className="text-sm font-black capitalize leading-tight text-slate-800">{request.from_entity}</p>
                                            <p className="text-xs font-bold text-slate-500">{request.branch ?? 'Produksi Pusat'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-12 space-y-6">
                                    <div className="flex items-center justify-between">
                                        <h3 className="font-headline text-lg font-black tracking-tight text-slate-900">Daftar Barang</h3>
                                        <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">{request.details.length} Items</span>
                                    </div>
                                    <div className="overflow-hidden rounded-3xl border border-slate-100">
                                        <table className="w-full border-collapse text-left">
                                            <thead>
                                                <tr className="bg-slate-50 text-[10px] font-black uppercase tracking-[0.15em] text-slate-500">
                                                    <th className="px-6 py-4">Produk</th>
                                                    <th className="px-6 py-4 text-center">Diminta</th>
                                                    {isPending && <th className="px-6 py-4 text-center">Stok Gudang</th>}
                                                    <th className="px-6 py-4 text-center">Qty Disetujui</th>
                                                    <th className="px-6 py-4 text-center">Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {request.details.map((item) => {
                                                    const hasEnough = item.warehouse_stock >= item.quantity_requested;
                                                    const current = data.items.find((it) => it.id === item.id);
                                                    return (
                                                        <tr key={item.id} className="transition-colors hover:bg-slate-50/50">
                                                            <td className="px-6 py-5">
                                                                <div className="flex flex-col">
                                                                    <span className="text-sm font-black tracking-tight text-slate-800">{item.product}</span>
                                                                    <span className="mt-0.5 font-mono text-[10px] font-bold uppercase text-slate-400">{item.product_code}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-5 text-center"><span className="text-base font-black tabular-nums text-slate-900">{formatQty(item.quantity_requested)}</span></td>
                                                            {isPending && (
                                                                <td className="px-6 py-5 text-center">
                                                                    <div className={`inline-flex items-center gap-2 rounded-xl px-3 py-1 text-xs font-bold ${hasEnough ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'}`}>
                                                                        <span className="tabular-nums">{formatQty(item.warehouse_stock)}</span>
                                                                        {!hasEnough && <Icon name="warning" className="text-[14px]" />}
                                                                    </div>
                                                                </td>
                                                            )}
                                                            <td className="px-6 py-5 text-center">
                                                                {isPending ? (
                                                                    <input type="number" min="0.001" step="any" max={item.quantity_requested} value={current?.quantity_approved ?? ''}
                                                                        onChange={(e) => setQty(item.id, e.target.value)}
                                                                        className="w-28 rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm font-bold tabular-nums text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                                                                ) : (
                                                                    <span className="text-sm font-bold tabular-nums text-slate-700">{item.quantity_approved !== null ? formatQty(item.quantity_approved) : '-'}</span>
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-5 text-center"><span className="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-500">{item.unit}</span></td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {isPending && (
                                <div className="border-t border-white/10 bg-slate-900 p-8 sm:p-10">
                                    <div className="flex flex-col items-center gap-6 md:flex-row">
                                        <div className="flex-1">
                                            <h4 className="font-headline text-lg font-black tracking-tight text-white">Keputusan Approval</h4>
                                            <p className="mt-1 text-xs font-medium text-slate-400">Sesuaikan qty disetujui jika perlu, lalu klik Setujui.</p>
                                        </div>
                                        <div className="flex w-full shrink-0 items-center gap-4 md:w-auto">
                                            <button type="button" onClick={() => setRejecting(true)} className="flex-1 rounded-2xl bg-rose-600/10 px-8 py-4 text-xs font-black uppercase tracking-widest text-rose-500 transition-all hover:bg-rose-600 hover:text-white md:flex-none">Tolak Request</button>
                                            <button type="submit" form="approve-form" disabled={processing} className="flex-1 rounded-2xl bg-indigo-600 px-10 py-4 text-xs font-black uppercase tracking-widest text-white shadow-xl shadow-indigo-600/30 transition-all hover:bg-indigo-500 disabled:opacity-50 md:flex-none">
                                                {processing ? 'Memproses...' : 'Setujui (Approve)'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {request.notes && (
                            <div className="flex items-start gap-5 rounded-3xl border border-indigo-100 bg-indigo-50 p-8">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm"><Icon name="description" /></div>
                                <div>
                                    <h4 className="mb-1 text-sm font-black uppercase tracking-widest text-indigo-900">Catatan Peminta</h4>
                                    <p className="text-sm font-medium italic leading-relaxed text-indigo-800/80">"{request.notes}"</p>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="space-y-8">
                        <div className="space-y-6 rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                            <h3 className="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Audit Trail</h3>
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-400"><Icon name="person" className="text-[20px]" /></div>
                                <div>
                                    <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">Diminta Oleh</p>
                                    <p className="text-sm font-bold text-slate-800">{request.requester ?? '-'}</p>
                                    <p className="mt-0.5 text-[10px] font-medium text-slate-500">{request.created_at}</p>
                                </div>
                            </div>
                            {request.approver && (
                                <div className="flex items-start gap-4">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600"><Icon name="verified" className="text-[20px]" /></div>
                                    <div>
                                        <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">Disetujui Oleh</p>
                                        <p className="text-sm font-bold text-slate-800">{request.approver}</p>
                                        <p className="mt-0.5 text-[10px] font-medium text-slate-500">{request.approved_at ?? '-'}</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="rounded-[2rem] bg-indigo-600 p-8 text-white shadow-xl shadow-indigo-600/20">
                            <div className="mb-6 flex items-center gap-3"><Icon name="info" className="text-[24px]" /><h3 className="text-sm font-black uppercase tracking-widest">Ringkasan</h3></div>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between border-b border-white/10 py-3"><span className="text-xs font-bold text-indigo-200">Total Item</span><span className="text-sm font-black tabular-nums">{request.details.length} SKU</span></div>
                                <div className="flex items-center justify-between py-3"><span className="text-xs font-bold text-indigo-200">Total Kuantitas</span><span className="text-sm font-black tabular-nums">{formatQty(totalQty)} Unit</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <RejectModal show={rejecting} onClose={() => setRejecting(false)} requestId={request.id} />
        </GudangLayout>
    );
}
