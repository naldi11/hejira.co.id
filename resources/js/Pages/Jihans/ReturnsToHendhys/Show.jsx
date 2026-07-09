import { Head, Link, router } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import StatusBadge from '@/Components/StatusBadge';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function ReturnsShow({ return: r }) {
    const handleReceive = () => { 
        if (confirm('Konfirmasi penerimaan retur ini?')) {
            router.post(route('jihans.returns-to-hendhys.receive', r.id)); 
        }
    };

    return (
        <JihansLayout pageTitle={`Detail Return ${r.return_number}`}>
            <Head title={`Return ${r.return_number}`} />
            
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-250 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{r.return_number}</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanggal: {r.date} · Pengirim: {r.creator}</p>
                        </div>
                        <StatusBadge status={r.status} />
                    </div>

                    <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-3 text-sm dark:border-gray-800">
                        <div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Cabang Asal</p>
                            <p className="font-medium text-gray-800 dark:text-white/90 mt-1">{r.branch}</p>
                        </div>
                        {r.receiver && (
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Penerima</p>
                                <p className="text-gray-800 dark:text-white/90 mt-1">{r.receiver}</p>
                            </div>
                        )}
                    </div>

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-855 dark:text-white/90">Rincian Barang</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-semibold">Produk</th>
                                        <th className="px-6 py-4 text-center font-semibold">Qty</th>
                                        <th className="px-6 py-4 text-center font-semibold">Satuan</th>
                                        <th className="px-6 py-4 text-center font-semibold">Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {r.details?.map((d, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">{d.product}</td>
                                            <td className="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{formatQty(d.quantity)}</td>
                                            <td className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">{d.unit}</td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="rounded-full bg-gray-100 dark:bg-gray-850 px-2.5 py-0.5 text-xs font-semibold text-gray-750 dark:text-gray-300 capitalize">
                                                    {d.condition}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="flex justify-between items-center pt-4 print:hidden">
                    <Link href={route('jihans.returns-to-hendhys.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>

                    {r.status === 'sent' && (
                        <button onClick={handleReceive} className="inline-flex items-center gap-2 rounded-xl bg-green-600 hover:bg-green-755 text-white px-6 py-2.5 text-sm font-bold shadow-sm transition-colors">
                            <Icon name="check_circle" className="text-[20px]" /> Terima Return
                        </button>
                    )}
                </div>
            </div>
        </JihansLayout>
    );
}
