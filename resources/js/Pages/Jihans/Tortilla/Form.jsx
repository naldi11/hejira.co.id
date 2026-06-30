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
            kribab_qty: '',
            hitam_besar_qty: '', hitam_sedang_qty: '', hitam_mini_qty: '',
            albaik_besar_qty: '', albaik_sedang_qty: '', albaik_mini_qty: '',
            regular_besar_qty: '', regular_sedang_qty: '', regular_mini_qty: '',
            lentur_besar_qty: '', lentur_sedang_qty: '', lentur_mini_qty: ''
        } : {
            details: karyawans.map(k => ({
                karyawan_id: k.id,
                name: k.name,
                tb_qty: '',
                ts_qty: '',
                tk_qty: '',
                tc_qty: '',
                kribab_qty: '',
                hitam_besar_qty: '', hitam_sedang_qty: '', hitam_mini_qty: '',
                albaik_besar_qty: '', albaik_sedang_qty: '', albaik_mini_qty: '',
                regular_besar_qty: '', regular_sedang_qty: '', regular_mini_qty: '',
                lentur_besar_qty: '', lentur_sedang_qty: '', lentur_mini_qty: ''
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
                hitam_besar: parseInt(data.hitam_besar_qty || 0),
                hitam_sedang: parseInt(data.hitam_sedang_qty || 0),
                hitam_mini: parseInt(data.hitam_mini_qty || 0),
                albaik_besar: parseInt(data.albaik_besar_qty || 0),
                albaik_sedang: parseInt(data.albaik_sedang_qty || 0),
                albaik_mini: parseInt(data.albaik_mini_qty || 0),
                regular_besar: parseInt(data.regular_besar_qty || 0),
                regular_sedang: parseInt(data.regular_sedang_qty || 0),
                regular_mini: parseInt(data.regular_mini_qty || 0),
                lentur_besar: parseInt(data.lentur_besar_qty || 0),
                lentur_sedang: parseInt(data.lentur_sedang_qty || 0),
                lentur_mini: parseInt(data.lentur_mini_qty || 0),
            };
        }
        
        return data.details.reduce((acc, curr) => ({
            tb: acc.tb + parseInt(curr.tb_qty || 0),
            ts: acc.ts + parseInt(curr.ts_qty || 0),
            tk: acc.tk + parseInt(curr.tk_qty || 0),
            tc: acc.tc + parseInt(curr.tc_qty || 0),
            kribab: acc.kribab + parseInt(curr.kribab_qty || 0),
            hitam_besar: acc.hitam_besar + parseInt(curr.hitam_besar_qty || 0),
            hitam_sedang: acc.hitam_sedang + parseInt(curr.hitam_sedang_qty || 0),
            hitam_mini: acc.hitam_mini + parseInt(curr.hitam_mini_qty || 0),
            albaik_besar: acc.albaik_besar + parseInt(curr.albaik_besar_qty || 0),
            albaik_sedang: acc.albaik_sedang + parseInt(curr.albaik_sedang_qty || 0),
            albaik_mini: acc.albaik_mini + parseInt(curr.albaik_mini_qty || 0),
            regular_besar: acc.regular_besar + parseInt(curr.regular_besar_qty || 0),
            regular_sedang: acc.regular_sedang + parseInt(curr.regular_sedang_qty || 0),
            regular_mini: acc.regular_mini + parseInt(curr.regular_mini_qty || 0),
            lentur_besar: acc.lentur_besar + parseInt(curr.lentur_besar_qty || 0),
            lentur_sedang: acc.lentur_sedang + parseInt(curr.lentur_sedang_qty || 0),
            lentur_mini: acc.lentur_mini + parseInt(curr.lentur_mini_qty || 0),
        }), { tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0, hitam_besar: 0, hitam_sedang: 0, hitam_mini: 0, albaik_besar: 0, albaik_sedang: 0, albaik_mini: 0, regular_besar: 0, regular_sedang: 0, regular_mini: 0, lentur_besar: 0, lentur_sedang: 0, lentur_mini: 0 });
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
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Hitam Besar</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.hitam_besar_qty}
                                        onChange={e => setData('hitam_besar_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Hitam Sedang</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.hitam_sedang_qty}
                                        onChange={e => setData('hitam_sedang_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Hitam Mini</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.hitam_mini_qty}
                                        onChange={e => setData('hitam_mini_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Albaik Besar</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.albaik_besar_qty}
                                        onChange={e => setData('albaik_besar_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Albaik Sedang</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.albaik_sedang_qty}
                                        onChange={e => setData('albaik_sedang_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Albaik Mini</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.albaik_mini_qty}
                                        onChange={e => setData('albaik_mini_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Regular Besar</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.regular_besar_qty}
                                        onChange={e => setData('regular_besar_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Regular Sedang</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.regular_sedang_qty}
                                        onChange={e => setData('regular_sedang_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Regular Mini</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.regular_mini_qty}
                                        onChange={e => setData('regular_mini_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lentur Besar</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.lentur_besar_qty}
                                        onChange={e => setData('lentur_besar_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lentur Sedang</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.lentur_sedang_qty}
                                        onChange={e => setData('lentur_sedang_qty', e.target.value)}
                                        className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 px-3 py-2 text-sm text-gray-855 dark:text-white outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lentur Mini</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.lentur_mini_qty}
                                        onChange={e => setData('lentur_mini_qty', e.target.value)}
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
                                            <th className="px-4 py-4 font-semibold w-24 text-center">HTM BSR</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">HTM SDG</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">HTM MNI</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">ALB BSR</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">ALB SDG</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">ALB MNI</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">REG BSR</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">REG SDG</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">REG MNI</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">LEN BSR</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">LEN SDG</th>
                                            <th className="px-4 py-4 font-semibold w-24 text-center">LEN MNI</th>
                                            <th className="px-4 py-4 font-semibold w-12 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {data.details.length === 0 ? (
                                            <tr>
                                                <td colSpan={19} className="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
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
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.hitam_besar_qty}
                                                            onChange={e => handleDetailChange(index, 'hitam_besar_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.hitam_sedang_qty}
                                                            onChange={e => handleDetailChange(index, 'hitam_sedang_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.hitam_mini_qty}
                                                            onChange={e => handleDetailChange(index, 'hitam_mini_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.albaik_besar_qty}
                                                            onChange={e => handleDetailChange(index, 'albaik_besar_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.albaik_sedang_qty}
                                                            onChange={e => handleDetailChange(index, 'albaik_sedang_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.albaik_mini_qty}
                                                            onChange={e => handleDetailChange(index, 'albaik_mini_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.regular_besar_qty}
                                                            onChange={e => handleDetailChange(index, 'regular_besar_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.regular_sedang_qty}
                                                            onChange={e => handleDetailChange(index, 'regular_sedang_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.regular_mini_qty}
                                                            onChange={e => handleDetailChange(index, 'regular_mini_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.lentur_besar_qty}
                                                            onChange={e => handleDetailChange(index, 'lentur_besar_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.lentur_sedang_qty}
                                                            onChange={e => handleDetailChange(index, 'lentur_sedang_qty', e.target.value)}
                                                            className="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-855 py-1.5 px-2 text-sm text-center text-gray-855 dark:text-white outline-none focus:border-orange-550 focus:ring-2 focus:ring-orange-500/20"
                                                            placeholder="0"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-2">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={detail.lentur_mini_qty}
                                                            onChange={e => handleDetailChange(index, 'lentur_mini_qty', e.target.value)}
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
                                            <td className="px-4 py-4 text-center">{totals.hitam_besar}</td>
                                            <td className="px-4 py-4 text-center">{totals.hitam_sedang}</td>
                                            <td className="px-4 py-4 text-center">{totals.hitam_mini}</td>
                                            <td className="px-4 py-4 text-center">{totals.albaik_besar}</td>
                                            <td className="px-4 py-4 text-center">{totals.albaik_sedang}</td>
                                            <td className="px-4 py-4 text-center">{totals.albaik_mini}</td>
                                            <td className="px-4 py-4 text-center">{totals.regular_besar}</td>
                                            <td className="px-4 py-4 text-center">{totals.regular_sedang}</td>
                                            <td className="px-4 py-4 text-center">{totals.regular_mini}</td>
                                            <td className="px-4 py-4 text-center">{totals.lentur_besar}</td>
                                            <td className="px-4 py-4 text-center">{totals.lentur_sedang}</td>
                                            <td className="px-4 py-4 text-center">{totals.lentur_mini}</td>
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
                                                            kribab_qty: '',
                                                            hitam_besar_qty: '', hitam_sedang_qty: '', hitam_mini_qty: '',
                                                            albaik_besar_qty: '', albaik_sedang_qty: '', albaik_mini_qty: '',
                                                            regular_besar_qty: '', regular_sedang_qty: '', regular_mini_qty: '',
                                                            lentur_besar_qty: '', lentur_sedang_qty: '', lentur_mini_qty: ''
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
