import { Head, Link, useForm } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

export default function TortillaForm({ karyawans, type, warning, targetDate }) {
    const isPrediksi = type === 'prediksi';

    // Prediksi hanya butuh total per produk, aktual butuh per karyawan
    const { data, setData, post, processing, errors } = useForm({
        date: (targetDate ? targetDate.substring(0, 10) : '') || new Date().toISOString().split('T')[0],
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

    const removeDetailRow = (index) => {
        setData('details', data.details.filter((_, i) => i !== index));
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

    const currentKaryawanIds = data.details ? data.details.map(d => Number(d.karyawan_id)) : [];
    const availableKaryawans = karyawans.filter(k => !currentKaryawanIds.includes(Number(k.id)));

    return (
        <JihansLayout pageTitle={isPrediksi ? "Input Prediksi Tortilla" : "Input Aktual Tortilla"}>
            <Head title={isPrediksi ? "Prediksi Produksi" : "Aktual Produksi"} />
            <form onSubmit={submit} className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            {isPrediksi ? 'Input Prediksi Produksi' : 'Input Aktual Produksi'}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {isPrediksi ? 'Masukkan rencana produksi tortilla untuk hari ini.' : 'Masukkan jumlah aktual yang dikerjakan per karyawan.'}
                        </p>
                    </div>
                    <Link href={route('jihans.tortilla.index')} className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        Batal
                    </Link>
                </div>

                {warning && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/20 p-4">
                        <div className="flex">
                            <Icon name="warning" className="mr-3 text-[20px] text-amber-600 dark:text-amber-400" />
                            <div className="text-sm font-medium text-amber-800 dark:text-amber-300">{warning}</div>
                        </div>
                    </div>
                )}
                
                {Object.keys(errors).length > 0 && (
                    <div className="rounded-xl border border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20 p-4 text-sm text-red-600 dark:text-red-400">
                        <ul className="list-inside list-disc">
                            {Object.values(errors).map((err, i) => <li key={i}>{err}</li>)}
                        </ul>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] md:col-span-1 h-fit">
                        <h3 className="mb-4 font-bold text-gray-850 dark:text-white/90">Informasi Sesi</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Tanggal Produksi</label>
                                <input
                                    type="date"
                                    value={data.date}
                                    onChange={e => setData('date', e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850 px-3 py-2 text-sm text-gray-850 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                    required
                                    readOnly={!!warning}
                                />
                            </div>
                            <div>
                                <label className="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Tipe Produksi</label>
                                <div className="mt-2 flex items-center gap-2">
                                    <span className="flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Icon name={isPrediksi ? 'event_note' : 'task_alt'} className="text-[16px]" />
                                        <span className="capitalize">{type}</span>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label className="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">Catatan Khusus (Opsional)</label>
                                <textarea
                                    rows="3"
                                    value={data.notes}
                                    onChange={e => setData('notes', e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                    placeholder="Misal: Adonan kurang kalis..."
                                />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] md:col-span-2">
                        <div className="border-b border-gray-200 dark:border-gray-800 px-5 py-4">
                            <h3 className="font-bold text-gray-850 dark:text-white/90">Rincian Produksi {isPrediksi ? '(Total Global)' : '(Per Karyawan)'}</h3>
                        </div>
                        
                        {isPrediksi ? (
                            <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tortilla Besar (TB)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.tb_qty}
                                        onChange={e => setData('tb_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tortilla Sedang (TS)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.ts_qty}
                                        onChange={e => setData('ts_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tortilla Kecil (TK)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.tk_qty}
                                        onChange={e => setData('tk_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tortilla Catering (TC)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.tc_qty}
                                        onChange={e => setData('tc_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kribab</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.kribab_qty}
                                        onChange={e => setData('kribab_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="custom-scrollbar overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-4 font-semibold">Nama Karyawan</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">TB</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">TS</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">TK</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">TC</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">Kribab</th>
                                            <th className="px-4 py-4 font-semibold w-12 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {data.details.length === 0 ? (
                                            <tr>
                                                <td colSpan={7} className="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                                    <Icon name="people" className="mb-2 block text-[32px] text-gray-400 mx-auto" />
                                                    <p className="text-sm font-semibold">Belum ada karyawan terdaftar.</p>
                                                    <p className="text-xs text-gray-400 mt-1">
                                                        Silakan daftarkan karyawan baru terlebih dahulu di menu{' '}
                                                        <Link href={route('jihans.master.karyawan.index')} className="text-orange-500 hover:underline">
                                                            Master Data &gt; Karyawan
                                                        </Link>.
                                                    </p>
                                                </td>
                                            </tr>
                                        ) : (
                                            data.details.map((detail, index) => (
                                                <tr key={detail.karyawan_id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-6 py-3 font-semibold text-gray-800 dark:text-white/90">{detail.name}</td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.tb_qty}
                                                            onChange={e => handleDetailChange(index, 'tb_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.ts_qty}
                                                            onChange={e => handleDetailChange(index, 'ts_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.tk_qty}
                                                            onChange={e => handleDetailChange(index, 'tk_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.tc_qty}
                                                            onChange={e => handleDetailChange(index, 'tc_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.kribab_qty}
                                                            onChange={e => handleDetailChange(index, 'kribab_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2 text-center">
                                                        <button 
                                                            type="button" 
                                                            onClick={() => removeDetailRow(index)} 
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors"
                                                            title="Hapus Karyawan"
                                                        >
                                                            <Icon name="delete" className="text-[18px]" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                    <tfoot className="bg-gray-50 dark:bg-white/[0.02] font-bold border-t border-gray-200 dark:border-gray-850 text-gray-800 dark:text-white/90">
                                        <tr>
                                            <td className="px-6 py-4 text-right">TOTAL</td>
                                            <td className="px-4 py-4 text-center">{totals.tb}</td>
                                            <td className="px-4 py-4 text-center">{totals.ts}</td>
                                            <td className="px-4 py-4 text-center">{totals.tk}</td>
                                            <td className="px-4 py-4 text-center">{totals.tc}</td>
                                            <td className="px-4 py-4 text-center">{totals.kribab}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                {availableKaryawans.length > 0 && (
                                    <div className="flex items-center gap-3 mt-4 px-5 pb-4">
                                        <select 
                                            id="add-karyawan-select" 
                                            defaultValue="" 
                                            onChange={(e) => {
                                                const kid = e.target.value;
                                                if (kid) {
                                                    const emp = karyawans.find(k => String(k.id) === String(kid));
                                                    if (emp) {
                                                        setData('details', [...data.details, {
                                                            karyawan_id: emp.id,
                                                            name: emp.name,
                                                            tb_qty: '',
                                                            ts_qty: '',
                                                            tk_qty: '',
                                                            tc_qty: '',
                                                            kribab_qty: ''
                                                        }]);
                                                    }
                                                    e.target.value = ""; // Reset select
                                                }
                                            }}
                                            className="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 cursor-pointer"
                                        >
                                            <option value="">+ Tambah Karyawan...</option>
                                            {availableKaryawans.map(k => (
                                                <option key={k.id} value={k.id}>{k.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3">
                    <button
                        type="submit"
                        disabled={processing || !!warning}
                        className="rounded-lg bg-orange-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors disabled:opacity-50 flex items-center gap-2"
                    >
                        {processing ? <Icon name="sync" className="animate-spin text-[20px]" /> : <Icon name="save" className="text-[20px]" />}
                        Simpan Data Produksi
                    </button>
                </div>
            </form>
        </JihansLayout>
    );
}
