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
                    <div className="flex items-center gap-3">
                        <Link href={route('jihans.tortilla.index')} className="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700">
                            <Icon name="arrow_back" className="text-[20px]" />
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight text-gray-800">{tortilla.session_number}</h2>
                            <p className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                <Icon name="event" className="text-[16px]" /> {formatDate(tortilla.date)}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {isPrediksi && (
                            <a href={route('jihans.tortilla.faktur', tortilla.id)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-orange-700">
                                <Icon name="print" className="text-[20px]" /> Cetak Faktur
                            </a>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-1">
                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-100 p-4">
                                <h3 className="font-bold text-gray-800">Informasi Produksi</h3>
                            </div>
                            <div className="p-4 space-y-4 text-sm">
                                <div className="grid grid-cols-2 gap-4 border-b border-gray-100 pb-4">
                                    <div>
                                        <p className="text-gray-500">Nomor Sesi</p>
                                        <p className="mt-1 font-semibold text-gray-800">{tortilla.session_number}</p>
                                    </div>
                                    <div>
                                        <p className="text-gray-500">Tanggal</p>
                                        <p className="mt-1 font-semibold text-gray-800">{formatDate(tortilla.date)}</p>
                                    </div>
                                    <div>
                                        <p className="text-gray-500">Tipe</p>
                                        <p className="mt-1">
                                            <StatusBadge status={isPrediksi ? 'pending' : 'completed'} label={tortilla.type} />
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-gray-500">Dibuat Oleh</p>
                                        <p className="mt-1 font-semibold text-gray-800">{tortilla.creator?.name || 'Sistem'}</p>
                                    </div>
                                </div>
                                
                                {tortilla.notes && (
                                    <div>
                                        <p className="text-gray-500">Catatan</p>
                                        <div className="mt-2 rounded-lg bg-yellow-50 p-3 text-yellow-800">
                                            {tortilla.notes}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="lg:col-span-2">
                        <div className="rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-100 p-4 flex justify-between items-center">
                                <h3 className="font-bold text-gray-800">Rincian Produksi</h3>
                                <span className="rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-800">
                                    {tortilla.details.length} Data
                                </span>
                            </div>
                            
                            {isPrediksi ? (
                                <div className="p-6">
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100">
                                            <p className="text-xs font-medium text-orange-600 uppercase mb-2">Tortilla Besar</p>
                                            <p className="text-2xl font-bold text-orange-900">{tortilla.details[0]?.tb_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100">
                                            <p className="text-xs font-medium text-orange-600 uppercase mb-2">Tortilla Sedang</p>
                                            <p className="text-2xl font-bold text-orange-900">{tortilla.details[0]?.ts_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100">
                                            <p className="text-xs font-medium text-orange-600 uppercase mb-2">Tortilla Kecil</p>
                                            <p className="text-2xl font-bold text-orange-900">{tortilla.details[0]?.tk_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100">
                                            <p className="text-xs font-medium text-orange-600 uppercase mb-2">Catering</p>
                                            <p className="text-2xl font-bold text-orange-900">{tortilla.details[0]?.tc_qty || 0}</p>
                                        </div>
                                        <div className="rounded-lg bg-orange-50 p-4 text-center border border-orange-100">
                                            <p className="text-xs font-medium text-orange-600 uppercase mb-2">Kribab</p>
                                            <p className="text-2xl font-bold text-orange-900">{tortilla.details[0]?.kribab_qty || 0}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="custom-scrollbar overflow-x-auto">
                                    <table className="w-full text-left text-sm">
                                        <thead className="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">Nama Karyawan</th>
                                                <th className="px-4 py-3 font-medium text-center">TB</th>
                                                <th className="px-4 py-3 font-medium text-center">TS</th>
                                                <th className="px-4 py-3 font-medium text-center">TK</th>
                                                <th className="px-4 py-3 font-medium text-center">TC</th>
                                                <th className="px-4 py-3 font-medium text-center">Kribab</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {tortilla.details.map((detail) => (
                                                <tr key={detail.id} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 font-medium text-gray-800">
                                                        {detail.karyawan?.name || 'Karyawan Dihapus'}
                                                    </td>
                                                    <td className="px-4 py-3 text-center text-gray-600">{detail.tb_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600">{detail.ts_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600">{detail.tk_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600">{detail.tc_qty}</td>
                                                    <td className="px-4 py-3 text-center text-gray-600">{detail.kribab_qty}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="bg-gray-50 font-bold border-t border-gray-200 text-gray-800">
                                            <tr>
                                                <td className="px-4 py-3 text-right text-xs uppercase tracking-wider text-gray-500">TOTAL</td>
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
            </div>
        </JihansLayout>
    );
}
