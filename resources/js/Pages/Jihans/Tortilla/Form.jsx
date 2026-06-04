import { Head, Link, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import EmptyState from '@/Components/EmptyState';

export default function TortillaForm({ karyawans, type, formAction, warning, targetDate }) {
    const isPrediksi = type === 'prediksi';

    // Prediksi hanya butuh total per produk, aktual butuh per karyawan
    const { data, setData, post, processing, errors } = useForm({
        date: targetDate || new Date().toISOString().split('T')[0],
        notes: '',
        ...(isPrediksi ? {
            tb_qty: '',
            ts_qty: '',
            tk_qty: '',
            tc_qty: '',
            kribab_qty: ''
        } : {
            details: karyawans.map(k => ({
                karyawan_id: k.id,
                name: k.name,
                tb_qty: '',
                ts_qty: '',
                tk_qty: '',
                tc_qty: '',
                kribab_qty: ''
            }))
        })
    });

    const submit = (e) => {
        e.preventDefault();
        
        // For Inertia we just use post/put natively instead of formAction prop.
        // We can just look at `type` to determine the route
        if (isPrediksi) {
            post(route('jihans.tortilla.prediksi.store'));
        } else {
            post(route('jihans.tortilla.store'));
        }
    };

    const handleDetailChange = (index, field, value) => {
        const newDetails = [...data.details];
        newDetails[index][field] = value;
        setData('details', newDetails);
    };

    const getTotals = () => {
        if (isPrediksi) {
            return {
                tb: parseInt(data.tb_qty || 0),
                ts: parseInt(data.ts_qty || 0),
                tk: parseInt(data.tk_qty || 0),
                tc: parseInt(data.tc_qty || 0),
                kribab: parseInt(data.kribab_qty || 0),
            };
        }
        
        return data.details.reduce((acc, curr) => ({
            tb: acc.tb + parseInt(curr.tb_qty || 0),
            ts: acc.ts + parseInt(curr.ts_qty || 0),
            tk: acc.tk + parseInt(curr.tk_qty || 0),
            tc: acc.tc + parseInt(curr.tc_qty || 0),
            kribab: acc.kribab + parseInt(curr.kribab_qty || 0),
        }), { tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0 });
    };

    const totals = getTotals();

    return (
        <JihansLayout pageTitle={isPrediksi ? "Input Prediksi Tortilla" : "Input Aktual Tortilla"}>
            <Head title={isPrediksi ? "Prediksi Produksi" : "Aktual Produksi"} />
            <form onSubmit={submit} className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-gray-800">
                            {isPrediksi ? 'Input Prediksi Produksi' : 'Input Aktual Produksi'}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {isPrediksi ? 'Masukkan rencana produksi tortilla untuk hari ini.' : 'Masukkan jumlah aktual yang dikerjakan per karyawan.'}
                        </p>
                    </div>
                    <Link href={route('jihans.tortilla.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </Link>
                </div>

                {warning && (
                    <div className="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                        <div className="flex">
                            <Icon name="warning" className="mr-3 text-[20px] text-yellow-600" />
                            <div className="text-sm font-medium text-yellow-800">{warning}</div>
                        </div>
                    </div>
                )}
                
                {Object.keys(errors).length > 0 && (
                    <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                        <ul className="list-inside list-disc">
                            {Object.values(errors).map((err, i) => <li key={i}>{err}</li>)}
                        </ul>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:col-span-1">
                        <h3 className="mb-4 font-bold text-gray-800">Informasi Sesi</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Tanggal Produksi</label>
                                <input type="date" value={data.date} onChange={e => setData('date', e.target.value)} className="w-full rounded-lg border-gray-300 py-2.5 text-sm" required readOnly={!!warning} />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Tipe Produksi</label>
                                <div className="mt-2 flex items-center gap-2">
                                    <span className="flex items-center gap-1.5 rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">
                                        <Icon name={isPrediksi ? 'event_note' : 'task_alt'} className="text-[16px]" />
                                        <span className="capitalize">{type}</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Catatan Khusus (Opsional)</label>
                                <textarea rows="3" value={data.notes} onChange={e => setData('notes', e.target.value)} className="w-full rounded-lg border-gray-300 py-2.5 text-sm" placeholder="Misal: Adonan kurang kalis..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white shadow-sm md:col-span-2">
                        <div className="border-b border-gray-100 px-5 py-4">
                            <h3 className="font-bold text-gray-800">Rincian Produksi {isPrediksi ? '(Total Global)' : '(Per Karyawan)'}</h3>
                        </div>
                        
                        {isPrediksi ? (
                            <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Tortilla Besar (TB)</label>
                                    <input type="number" min="0" value={data.tb_qty} onChange={e => setData('tb_qty', e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" placeholder="0" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Tortilla Sedang (TS)</label>
                                    <input type="number" min="0" value={data.ts_qty} onChange={e => setData('ts_qty', e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" placeholder="0" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Tortilla Kecil (TK)</label>
                                    <input type="number" min="0" value={data.tk_qty} onChange={e => setData('tk_qty', e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" placeholder="0" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Tortilla Catering (TC)</label>
                                    <input type="number" min="0" value={data.tc_qty} onChange={e => setData('tc_qty', e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" placeholder="0" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Kribab</label>
                                    <input type="number" min="0" value={data.kribab_qty} onChange={e => setData('kribab_qty', e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm" placeholder="0" />
                                </div>
                            </div>
                        ) : (
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Nama Karyawan</th>
                                            <th className="px-4 py-3 font-medium w-24">TB</th>
                                            <th className="px-4 py-3 font-medium w-24">TS</th>
                                            <th className="px-4 py-3 font-medium w-24">TK</th>
                                            <th className="px-4 py-3 font-medium w-24">TC</th>
                                            <th className="px-4 py-3 font-medium w-24">Kribab</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {data.details.map((detail, index) => (
                                            <tr key={detail.karyawan_id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 font-medium text-gray-800">{detail.name}</td>
                                                <td className="px-4 py-3"><input type="number" min="0" value={detail.tb_qty} onChange={e => handleDetailChange(index, 'tb_qty', e.target.value)} className="w-full rounded-md border-gray-300 py-1.5 px-2 text-sm text-center" placeholder="0" /></td>
                                                <td className="px-4 py-3"><input type="number" min="0" value={detail.ts_qty} onChange={e => handleDetailChange(index, 'ts_qty', e.target.value)} className="w-full rounded-md border-gray-300 py-1.5 px-2 text-sm text-center" placeholder="0" /></td>
                                                <td className="px-4 py-3"><input type="number" min="0" value={detail.tk_qty} onChange={e => handleDetailChange(index, 'tk_qty', e.target.value)} className="w-full rounded-md border-gray-300 py-1.5 px-2 text-sm text-center" placeholder="0" /></td>
                                                <td className="px-4 py-3"><input type="number" min="0" value={detail.tc_qty} onChange={e => handleDetailChange(index, 'tc_qty', e.target.value)} className="w-full rounded-md border-gray-300 py-1.5 px-2 text-sm text-center" placeholder="0" /></td>
                                                <td className="px-4 py-3"><input type="number" min="0" value={detail.kribab_qty} onChange={e => handleDetailChange(index, 'kribab_qty', e.target.value)} className="w-full rounded-md border-gray-300 py-1.5 px-2 text-sm text-center" placeholder="0" /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50 font-bold border-t border-gray-200 text-gray-800">
                                        <tr>
                                            <td className="px-4 py-3 text-right">TOTAL</td>
                                            <td className="px-4 py-3 text-center">{totals.tb}</td>
                                            <td className="px-4 py-3 text-center">{totals.ts}</td>
                                            <td className="px-4 py-3 text-center">{totals.tk}</td>
                                            <td className="px-4 py-3 text-center">{totals.tc}</td>
                                            <td className="px-4 py-3 text-center">{totals.kribab}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3">
                    <button type="submit" disabled={processing || !!warning} className="rounded-xl bg-orange-600 px-8 py-3 text-sm font-bold text-white shadow-sm hover:bg-orange-700 disabled:opacity-50 flex items-center gap-2">
                        {processing ? <Icon name="sync" className="animate-spin text-[20px]" /> : <Icon name="save" className="text-[20px]" />}
                        Simpan Data Produksi
                    </button>
                </div>
            </form>
        </JihansLayout>
    );
}
