import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatDate } from '@/lib/format';

export default function TortillaShow({ tortilla }) {
    const isPrediksi = tortilla.type === 'prediksi';
    
    // Hitung total untuk aktual
    const totals = !isPrediksi ? tortilla.details.reduce((acc, curr) => ({
        tb: acc.tb + parseInt(curr.tb_qty || 0),
        ts: acc.ts + parseInt(curr.ts_qty || 0),
        tk: acc.tk + parseInt(curr.tk_qty || 0),
        tc: acc.tc + parseInt(curr.tc_qty || 0),
        kribab: acc.kribab + parseInt(curr.kribab_qty || 0),
    }), { tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0 }) : null;

    return (
        <JihansLayout pageTitle={`Detail Produksi ${tortilla.session_number}`}>
            <Head title={`Detail Produksi ${tortilla.session_number}`} />
            
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">{tortilla.session_number}</h2>
                        <p className="mt-1 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <Icon name="event" className="text-[16px]" /> {formatDate(tortilla.date)}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {isPrediksi && (
                            <a
                                href={route('jihans.tortilla.faktur', tortilla.id)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center justify-center gap-1.5 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors"
                            >
                                <Icon name="print" className="text-[18px]" /> Cetak Faktur
                            </a>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-1">
                        <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-200 p-5 dark:border-gray-800">
                                <h3 className="font-bold text-gray-850 dark:text-white/90">Informasi Produksi</h3>
                            </div>
                            <div className="p-5 space-y-4 text-sm">
                                <div className="grid grid-cols-2 gap-4 border-b border-gray-250 pb-4 dark:border-gray-800">
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Nomor Sesi</p>
                                        <p className="mt-1 font-semibold text-gray-850 dark:text-white/90">{tortilla.session_number}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Tanggal</p>
                                        <p className="mt-1 font-semibold text-gray-850 dark:text-white/90">{formatDate(tortilla.date)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Tipe</p>
                                        <p className="mt-1">
                                            <StatusBadge status={isPrediksi ? 'pending' : 'completed'} label={tortilla.type} />
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Dibuat Oleh</p>
                                        <p className="mt-1 font-semibold text-gray-855 dark:text-white/90">{tortilla.creator?.name || 'Sistem'}</p>
                                    </div>
                                </div>
                                
                                {tortilla.notes && (
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Catatan</p>
                                        <div className="mt-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 p-3 text-sm text-amber-800 dark:text-amber-300">
                                            {tortilla.notes}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="lg:col-span-2">
                        <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <div className="border-b border-gray-250 p-5 flex justify-between items-center dark:border-gray-800">
                                <h3 className="font-bold text-gray-850 dark:text-white/90">Rincian Produksi</h3>
                                <span className="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-750 dark:bg-orange-950/40 dark:text-orange-400">
                                    {tortilla.details.length} Data
                                </span>
                            </div>
                            
                            {isPrediksi ? (
                                <div className="p-6">
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100 dark:bg-orange-900/20 dark:border-orange-800/40">
                                            <p className="text-[10px] font-semibold text-orange-600 uppercase mb-2 dark:text-orange-400 tracking-wider">Tortilla Besar</p>
                                            <p className="text-2xl font-bold text-orange-900 dark:text-orange-300">{tortilla.details[0]?.tb_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100 dark:bg-orange-900/20 dark:border-orange-800/40">
                                            <p className="text-[10px] font-semibold text-orange-600 uppercase mb-2 dark:text-orange-400 tracking-wider">Tortilla Sedang</p>
                                            <p className="text-2xl font-bold text-orange-900 dark:text-orange-300">{tortilla.details[0]?.ts_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100 dark:bg-orange-900/20 dark:border-orange-800/40">
                                            <p className="text-[10px] font-semibold text-orange-600 uppercase mb-2 dark:text-orange-400 tracking-wider">Tortilla Kecil</p>
                                            <p className="text-2xl font-bold text-orange-900 dark:text-orange-300">{tortilla.details[0]?.tk_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100 dark:bg-orange-900/20 dark:border-orange-800/40">
                                            <p className="text-[10px] font-semibold text-orange-600 uppercase mb-2 dark:text-orange-400 tracking-wider">Catering</p>
                                            <p className="text-2xl font-bold text-orange-900 dark:text-orange-300">{tortilla.details[0]?.tc_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100 dark:bg-orange-900/20 dark:border-orange-800/40">
                                            <p className="text-[10px] font-semibold text-orange-600 uppercase mb-2 dark:text-orange-400 tracking-wider">Kribab</p>
                                            <p className="text-2xl font-bold text-orange-900 dark:text-orange-300">{tortilla.details[0]?.kribab_qty || 0}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="custom-scrollbar overflow-x-auto">
                                    <table className="w-full text-left text-sm">
                                        <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                            <tr>
                                                <th className="px-6 py-4 font-semibold">Nama Karyawan</th>
                                                <th className="px-4 py-4 font-semibold text-center w-24">TB</th>
                                                <th className="px-4 py-4 font-semibold text-center w-24">TS</th>
                                                <th className="px-4 py-4 font-semibold text-center w-24">TK</th>
                                                <th className="px-4 py-4 font-semibold text-center w-24">TC</th>
                                                <th className="px-4 py-4 font-semibold text-center w-24">Kribab</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                            {tortilla.details.map((detail) => (
                                                <tr key={detail.id} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                                    <td className="px-6 py-3 font-semibold text-gray-800 dark:text-white/90">
                                                        {detail.karyawan?.name || 'Karyawan Dihapus'}
                                                    </td>
                                                    <td className="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{detail.tb_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{detail.ts_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{detail.tk_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{detail.tc_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{detail.kribab_qty}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="bg-gray-50 dark:bg-white/[0.02] font-bold border-t border-gray-250 dark:border-gray-850 text-gray-800 dark:text-white/90">
                                            <tr>
                                                <td className="px-6 py-4 text-right">TOTAL</td>
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
                </div>

                <div className="flex justify-start pt-4 print:hidden">
                    <Link href={route('jihans.tortilla.index')} className="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                </div>
            </div>
        </JihansLayout>
    );
}
