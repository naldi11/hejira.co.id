import { Head, useForm } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function TransferToBranchReceive({ transfer: t }) {
    const initialQty    = {};
    const initialKondisi = {};
    t.details?.forEach(d => {
        initialQty[d.id]     = d.quantity;
        initialKondisi[d.id] = 'baik';
    });

    const { data, setData, post, processing, errors } = useForm({
        received_quantities:      initialQty,
        kondisi:                  initialKondisi,
        receive_notes:            '',
        receive_kendala:          '',
        receive_received_by_name: '',
        receive_pengirim_name:    '',
    });

    const submit = (e) => { e.preventDefault(); post(route('hendhys.transfer-to-branch.receive', t.id)); };

    const fieldClass = 'w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3.5 py-2.5 text-sm text-gray-800 dark:text-white/90 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-sm transition-all';
    const selectClass = 'rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3 py-2.5 text-sm text-gray-800 dark:text-white/90 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-sm transition-all';

    return (
        <HendhysLayout pageTitle="Konfirmasi Penerimaan">
            <Head title={`Terima ${t.transfer_number}`} />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-5">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                        Konfirmasi Penerimaan
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {t.transfer_number} — dari Pusat ke {t.branch}
                    </p>
                </div>

                {/* Tabel rincian barang */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <h3 className="font-bold text-gray-800 dark:text-white/90">Rincian Barang Diterima</h3>
                        <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Sesuaikan qty jika ada selisih dengan yang dikirim</p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-y border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-3">Produk</th>
                                    <th className="px-6 py-3 text-center">Qty Kirim</th>
                                    <th className="px-6 py-3 text-center">Qty Diterima</th>
                                    <th className="px-6 py-3 text-center">Kondisi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {t.details?.map((d) => (
                                    <tr key={d.id} className="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                        <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">
                                            {d.product}
                                            {d.product_code && (
                                                <span className="ml-2 font-mono text-xs text-gray-400 dark:text-gray-500">{d.product_code}</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-center font-bold text-gray-700 dark:text-gray-300">
                                            {formatQty(d.quantity)} <span className="text-xs font-normal text-gray-400">{d.unit}</span>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <input
                                                type="number"
                                                min="0"
                                                max={d.quantity}
                                                value={data.received_quantities[d.id] ?? 0}
                                                onChange={(e) => {
                                                    const rawVal = e.target.value;
                                                    let val = rawVal === '' ? '' : parseFloat(rawVal);
                                                    if (val !== '') {
                                                        if (isNaN(val) || val < 0) val = 0;
                                                        if (val > d.quantity) val = d.quantity;
                                                    }
                                                    setData('received_quantities', {
                                                        ...data.received_quantities,
                                                        [d.id]: val,
                                                    });
                                                }}
                                                className="w-24 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-3 py-2.5 text-center text-sm text-gray-800 dark:text-white/90 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-sm transition-all"
                                            />
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <select
                                                value={data.kondisi[d.id] ?? 'baik'}
                                                onChange={(e) => setData('kondisi', {
                                                    ...data.kondisi,
                                                    [d.id]: e.target.value,
                                                })}
                                                className={selectClass}
                                            >
                                                <option value="baik" className="dark:bg-gray-800">Baik</option>
                                                <option value="rusak" className="dark:bg-gray-800">Rusak</option>
                                                <option value="kurang" className="dark:bg-gray-800">Kurang</option>
                                            </select>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Form catatan & nama */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <h3 className="font-bold text-gray-800 dark:text-white/90">Informasi Serah Terima</h3>
                    </div>
                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                        {/* Catatan */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Catatan Penerimaan
                            </label>
                            <textarea
                                value={data.receive_notes}
                                onChange={(e) => setData('receive_notes', e.target.value)}
                                rows={3}
                                placeholder="Kondisi umum, keterangan tambahan..."
                                className={fieldClass}
                            />
                        </div>

                        {/* Kendala */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Kendala (jika ada)
                            </label>
                            <textarea
                                value={data.receive_kendala}
                                onChange={(e) => setData('receive_kendala', e.target.value)}
                                rows={3}
                                placeholder="Kendala saat penerimaan..."
                                className={fieldClass}
                            />
                        </div>

                        {/* Nama Penerima */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nama Penerima <span className="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.receive_received_by_name}
                                onChange={(e) => setData('receive_received_by_name', e.target.value)}
                                placeholder="Nama yang menerima barang"
                                className={fieldClass}
                            />
                        </div>

                        {/* Nama Pengirim */}
                        <div>
                            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nama Pengirim
                            </label>
                            <input
                                type="text"
                                value={data.receive_pengirim_name}
                                onChange={(e) => setData('receive_pengirim_name', e.target.value)}
                                placeholder="Nama kurir/pengirim dari Pusat"
                                className={fieldClass}
                            />
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-white/[0.03] px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 dark:hover:bg-white/[0.05] transition-colors"
                    >
                        <Icon name="arrow_back" className="text-[18px]" /> Batal
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:opacity-50 transition-colors"
                    >
                        <Icon name="check_circle" className="text-[18px]" />
                        {processing ? 'Menyimpan...' : 'Konfirmasi Terima'}
                    </button>
                </div>
            </form>
        </HendhysLayout>
    );
}
