import { Head, Link, useForm } from '@inertiajs/react';
import GudangLayout from '@/Layouts/GudangLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate, formatQty } from '@/lib/format';

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

    return (
        <GudangLayout title="Detail Penerimaan Retur" pageTitle={`Detail Retur ${ret.return_number}`}>
            <Head title={ret.return_number} />

            <div className="mx-auto max-w-5xl space-y-4">
                <Link href={route('gudang.returns.index')} className="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    <Icon name="arrow_back" className="text-[18px]" /> Kembali
                </Link>

                <form onSubmit={submit} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-6 border-b border-slate-100 bg-slate-50 p-6">
                        <div>
                            <h2 className="text-xl font-bold text-slate-800">{ret.return_number}</h2>
                            <p className="mt-1 text-sm text-slate-500">Tanggal Kirim: {formatDate(ret.date)}</p>
                        </div>
                        <div className="text-right">
                            <p className="mb-1 text-xs font-medium uppercase tracking-wider text-slate-500">Status Retur</p>
                            <StatusBadge status={ret.status} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-slate-100 p-6 md:grid-cols-2">
                        <div>
                            <p className="mb-1 text-xs font-medium uppercase tracking-wider text-slate-500">Asal Pengirim</p>
                            <p className="text-lg font-bold text-slate-800">{ret.from_entity_label}</p>
                            <p className="mt-1 text-sm text-slate-500">Dibuat oleh: {ret.creator ?? '-'}</p>
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium uppercase tracking-wider text-slate-500">Catatan Pengirim</p>
                            <p className="font-medium text-slate-800">{ret.notes || '-'}</p>
                        </div>
                    </div>

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-slate-800">Rincian Barang Retur</h3>
                        <div className="custom-scrollbar overflow-x-auto">
                            <table className="w-full border-collapse border border-slate-200 text-left text-sm">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                        <th className="w-12 border-r border-slate-200 px-4 py-3 font-medium">No</th>
                                        <th className="border-r border-slate-200 px-4 py-3 font-medium">Nama Produk</th>
                                        <th className="w-32 border-r border-slate-200 px-4 py-3 text-right font-medium">Qty Dikirim</th>
                                        <th className="w-40 border-r border-slate-200 px-4 py-3 text-right font-medium">Qty Diterima</th>
                                        <th className="w-44 border-r border-slate-200 px-4 py-3 font-medium">Kondisi Fisik</th>
                                        <th className="px-4 py-3 font-medium">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {ret.details.map((detail, i) => (
                                        <tr key={detail.id} className="hover:bg-slate-50">
                                            <td className="border-r border-slate-200 px-4 py-3 text-slate-500">{i + 1}</td>
                                            <td className="border-r border-slate-200 px-4 py-3 font-medium text-slate-800">{detail.product}</td>
                                            <td className="border-r border-slate-200 px-4 py-3 text-right font-bold text-slate-800">{formatQty(detail.quantity)}</td>
                                            <td className="border-r border-slate-200 px-4 py-3 text-right">
                                                {isSent ? (
                                                    <input type="number" step="0.001" min="0" max={detail.quantity} required
                                                        value={data.items[detail.id].received_quantity}
                                                        onChange={(e) => setItem(detail.id, 'received_quantity', e.target.value)}
                                                        className="w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                ) : (
                                                    <span className="font-bold text-emerald-600">{formatQty(detail.received_quantity)}</span>
                                                )}
                                            </td>
                                            <td className="border-r border-slate-200 px-4 py-3">
                                                {isSent ? (
                                                    <select required value={data.items[detail.id].condition}
                                                        onChange={(e) => setItem(detail.id, 'condition', e.target.value)}
                                                        className="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        {CONDITIONS.map((c) => <option key={c} value={c}>{c}</option>)}
                                                    </select>
                                                ) : (
                                                    <span className="rounded bg-slate-100 px-2 py-1 text-xs font-semibold uppercase text-slate-700">{detail.condition}</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">{detail.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {isSent ? (
                        <div className="flex flex-col items-start justify-between gap-4 border-t border-indigo-100 bg-indigo-50 p-6 sm:flex-row sm:items-center">
                            <div>
                                <p className="font-bold text-indigo-900">Konfirmasi Penerimaan Retur</p>
                                <p className="mt-1 text-sm text-indigo-700">Stok Gudang Utama akan otomatis bertambah sesuai jumlah kuantitas yang diterima.</p>
                            </div>
                            <button type="submit" disabled={processing} className="whitespace-nowrap rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-indigo-700 disabled:opacity-50">
                                {processing ? 'Memproses...' : 'Konfirmasi Penerimaan'}
                            </button>
                        </div>
                    ) : (
                        <div className="border-t border-slate-100 bg-slate-50 p-6">
                            <p className="text-sm text-slate-600">Diterima oleh: <span className="font-semibold text-slate-800">{ret.receiver ?? '-'}</span> pada {ret.received_at ?? '-'}</p>
                        </div>
                    )}
                </form>
            </div>
        </GudangLayout>
    );
}
