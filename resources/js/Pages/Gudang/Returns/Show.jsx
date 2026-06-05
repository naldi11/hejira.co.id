import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

const route = window.route;

const CONDITIONS = ['Bagus (Siap Jual)', 'Rusak (Defect)', 'Kadaluwarsa'];

export default function ReturnShow({ return: ret }) {
    const isSent = ret.status === 'sent';

    const { data, setData, post, processing } = useForm({
        items: Object.fromEntries(
            ret.details.map((d) => [d.id, { received_quantity: d.quantity, condition: CONDITIONS[0] }]),
        ),
    });

    const setItem = (id, field, value) => {
        setData('items', { ...data.items, [id]: { ...data.items[id], [field]: value } });
    };

    const submit = (e) => {
        e.preventDefault();
        if (!window.confirm('Konfirmasi penerimaan barang retur ke Gudang Utama?')) return;
        post(route('gudang.returns.receive', ret.id), { preserveScroll: true });
    };

    const cellInput = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-right text-xs font-bold text-gray-850 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';
    const cellSelect = 'w-full h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50';

    return (
        <GudangLayout title="Detail Penerimaan Retur" pageTitle={`Detail Retur — ${ret.return_number}`}>
            <Head title={ret.return_number} />

            <div className="mx-auto max-w-5xl space-y-6">
                <form onSubmit={submit} className="space-y-6">
                    {/* Header Panel */}
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="flex flex-wrap items-center justify-between gap-6 border-b border-gray-150 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div>
                                <h2 className="text-lg font-bold text-gray-800 dark:text-white/90">{ret.return_number}</h2>
                                <p className="mt-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Tanggal Kirim: {formatDate(ret.date)}</p>
                            </div>
                            <div className="text-right">
                                <p className="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Status Retur</p>
                                <StatusBadge status={ret.status} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                            <div>
                                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Asal Pengirim</p>
                                <p className="text-sm font-bold text-gray-850 dark:text-white/90">{ret.from_entity_label}</p>
                                <p className="mt-1 text-xs font-semibold text-gray-400 dark:text-gray-500">Dibuat oleh: {ret.creator ?? '-'}</p>
                            </div>
                            <div>
                                <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-450 dark:text-gray-550">Catatan Pengirim</p>
                                <p className="text-sm font-semibold text-gray-800 dark:text-gray-300">{ret.notes || '—'}</p>
                            </div>
                        </div>
                    </div>

                    {/* Items Section */}
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div className="border-b border-gray-150 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-305">Rincian Barang Retur</h3>
                        </div>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b border-gray-150 bg-gray-50/50 text-xs font-bold text-gray-500 dark:border-gray-850 dark:bg-white/[0.02] dark:text-gray-400 tracking-wider">
                                        <th className="px-6 py-3.5 w-16">No</th>
                                        <th className="px-6 py-3.5">Nama Produk</th>
                                        <th className="px-4 py-3.5 text-right w-36">Qty Kirim</th>
                                        <th className="px-4 py-3.5 text-right w-40">Qty Terima</th>
                                        <th className="px-6 py-3.5 w-48">Kondisi Fisik</th>
                                        <th className="px-6 py-3.5 w-32">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {ret.details.map((detail, i) => (
                                        <tr key={detail.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4.5 text-xs text-gray-400 dark:text-gray-500 font-semibold">{i + 1}</td>
                                            <td className="px-6 py-4.5 font-bold text-gray-800 dark:text-white/90">{detail.product}</td>
                                            <td className="px-4 py-4.5 text-right font-semibold text-gray-400 dark:text-gray-500 tabular-nums">{formatQty(detail.quantity)}</td>
                                            <td className="px-4 py-3 text-right">
                                                {isSent ? (
                                                    <input type="number" step="0.001" min="0" max={detail.quantity} required
                                                        value={data.items[detail.id].received_quantity}
                                                        onChange={(e) => setItem(detail.id, 'received_quantity', e.target.value)}
                                                        className={cellInput} />
                                                ) : (
                                                    <span className="font-bold text-emerald-600 dark:text-emerald-450 tabular-nums">{formatQty(detail.received_quantity)}</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-3">
                                                {isSent ? (
                                                    <select required value={data.items[detail.id].condition}
                                                        onChange={(e) => setItem(detail.id, 'condition', e.target.value)}
                                                        className={cellSelect}>
                                                        {CONDITIONS.map((c) => <option key={c} value={c}>{c}</option>)}
                                                    </select>
                                                ) : (
                                                    <span className="inline-flex rounded-lg bg-gray-100 dark:bg-gray-850 px-2.5 py-1 text-xs font-bold uppercase text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                                        {detail.condition}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4.5 text-xs font-semibold text-gray-500 dark:text-gray-400">{detail.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Footer Action Card */}
                        {isSent ? (
                            <div className="flex flex-col items-start justify-between gap-4 border-t border-brand-100 bg-brand-50/10 p-6 dark:border-brand-900/20 dark:bg-brand-500/5 sm:flex-row sm:items-center">
                                <div className="space-y-1">
                                    <p className="text-sm font-bold text-brand-700 dark:text-brand-400">Konfirmasi Penerimaan Retur</p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Stok Gudang Utama akan otomatis bertambah sesuai jumlah kuantitas yang diterima.</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Link href={route('gudang.returns.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-250 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-sm hover:bg-gray-55 dark:hover:bg-gray-700 transition-colors">
                                        <Icon name="arrow_back" className="text-[20px]" /> Batal
                                    </Link>
                                    <Button type="submit" disabled={processing} className="whitespace-nowrap">
                                        {processing ? 'Memproses...' : 'Konfirmasi Penerimaan'}
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="border-t border-gray-150 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.02]">
                                <p className="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    Diterima oleh: <span className="font-bold text-gray-800 dark:text-white/90">{ret.receiver ?? '-'}</span> pada <span className="font-bold text-gray-855 dark:text-white/95 tabular-nums">{ret.received_at ?? '-'}</span>
                                </p>
                            </div>
                        )}
                    </div>
                </form>

                {!isSent && (
                    <div className="flex justify-start pt-4 print:hidden">
                        <Link href={route('gudang.returns.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                        </Link>
                    </div>
                )}
            </div>
        </GudangLayout>
    );
}
