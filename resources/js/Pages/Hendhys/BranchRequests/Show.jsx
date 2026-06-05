import { Head, Link, usePage } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function BranchRequestsShow({ branchRequest: r }) {
    const { auth } = usePage().props;
    const isPusat  = auth?.user?.branch?.type === 'pusat';
    const isCabang = auth?.user?.branch?.type === 'cabang';

    // Transfer yang statusnya 'sent' (belum dikonfirmasi cabang)
    const pendingTransfers = (r.transfer_outs ?? []).filter(t => t.status === 'sent');

    return (
        <HendhysLayout pageTitle={`Detail Request ${r.request_number}`}>
            <Head title={`Request ${r.request_number}`} />

            <div className="mx-auto max-w-4xl space-y-5">

                {/* ── Banner: barang sudah dikirim, cabang perlu konfirmasi ── */}
                {isCabang && pendingTransfers.length > 0 && (
                    <div className="flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 dark:border-green-500/20 dark:bg-green-500/10">
                        <Icon name="local_shipping" className="mt-0.5 shrink-0 text-[22px] text-green-500" />
                        <div className="flex-1">
                            <p className="font-semibold text-green-800 dark:text-green-300">
                                Barang sudah dikirim dari Pusat!
                            </p>
                            <p className="mt-0.5 text-sm text-green-700 dark:text-green-400">
                                Segera konfirmasi penerimaan agar stok cabang diperbarui.
                            </p>
                        </div>
                        <Link
                            href={route('hendhys.transfer-to-branch.show', pendingTransfers[0].id)}
                            className="shrink-0 rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700 transition-colors"
                        >
                            Konfirmasi →
                        </Link>
                    </div>
                )}

                {/* ── Detail card ── */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{r.request_number}</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanggal: {r.date} · Oleh {r.creator}</p>
                        </div>
                        <StatusBadge status={r.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-2 text-sm dark:border-gray-800">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cabang Peminta</p>
                            <p className="mt-1 font-medium text-gray-800 dark:text-white/90">{r.branch ?? 'N/A'}</p>
                        </div>
                        {r.notes && (
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan</p>
                                <p className="mt-1 text-gray-800 dark:text-white/90">{r.notes}</p>
                            </div>
                        )}
                    </div>

                    {/* Rincian barang */}
                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-800 dark:text-white/90">Rincian Barang</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-y border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-3">Produk</th>
                                        <th className="px-6 py-3 text-center">Diminta</th>
                                        <th className="px-6 py-3 text-center">Disetujui</th>
                                        <th className="px-6 py-3 text-center">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {r.details?.map((d, i) => (
                                        <tr key={i} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">
                                                <div className="flex flex-col">
                                                    <span>{d.product}</span>
                                                    <span className="font-mono text-xs text-gray-400 dark:text-gray-500">{d.product_code}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{formatQty(d.quantity_requested)}</td>
                                            <td className="px-6 py-4 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                                {d.quantity_approved !== null ? formatQty(d.quantity_approved) : '–'}
                                            </td>
                                            <td className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">{d.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* ── Transfer distribusi terkait ── */}
                {(r.transfer_outs ?? []).length > 0 && (
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="border-b border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                            <h3 className="font-bold text-gray-800 dark:text-white/90">Distribusi Terkait</h3>
                            <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Transfer barang dari Pusat untuk request ini</p>
                        </div>
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {r.transfer_outs.map((t) => (
                                <div key={t.id} className="flex items-center justify-between px-6 py-4">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-500/10">
                                            <Icon name="local_shipping" className="text-[20px] text-amber-500" />
                                        </div>
                                        <div>
                                            <p className="font-mono text-sm font-bold text-gray-800 dark:text-white/90">{t.transfer_number}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">{t.date}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <StatusBadge status={t.status} />
                                        <Link
                                            href={route('hendhys.transfer-to-branch.show', t.id)}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/[0.05] transition-colors"
                                        >
                                            <Icon name="visibility" className="text-[14px]" /> Detail
                                        </Link>
                                        <a
                                            href={route('hendhys.transfer-to-branch.bast', t.id)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors"
                                        >
                                            <Icon name="print" className="text-[14px]" /> BAST
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* ── Footer actions ── */}
                <div className="flex justify-between items-center pt-2 print:hidden">
                    <Link
                        href={route('hendhys.branch-requests.index')}
                        className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 dark:hover:bg-white/[0.05] transition-colors"
                    >
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>

                    {isPusat && r.status === 'pending' && (
                        <Link
                            href={route('hendhys.transfer-to-branch.create') + '?request_id=' + r.id}
                            className="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 text-sm font-bold shadow-sm transition-colors"
                        >
                            <Icon name="local_shipping" className="text-[20px]" /> Proses Distribusi
                        </Link>
                    )}
                </div>
            </div>
        </HendhysLayout>
    );
}
