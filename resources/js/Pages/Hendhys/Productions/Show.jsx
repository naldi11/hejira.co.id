import { Head, Link } from '@inertiajs/react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatQty } from '@/lib/format';

const route = window.route;

export default function ProductionsShow({ production }) {
    const p = production;
    return (
        <HendhysLayout pageTitle={`Detail Produksi ${p.production_number}`}>
            <Head title={`Produksi ${p.production_number}`} />
            
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-250 bg-gray-50/50 p-6 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div>
                            <h2 className="font-mono text-xl font-bold text-gray-800 dark:text-white/90">{p.production_number}</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanggal: {p.date} · Oleh {p.creator}</p>
                        </div>
                    </div>

                    {p.notes && (
                        <div className="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 text-sm dark:border-gray-800">
                            <div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider">Catatan</p>
                                <p className="text-gray-800 dark:text-gray-300 mt-1 dark:text-white/90">{p.notes}</p>
                            </div>
                        </div>
                    )}

                    <div className="p-6">
                        <h3 className="mb-4 font-bold text-gray-850 dark:text-white/90">Hasil Produksi</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-semibold">Produk</th>
                                        <th className="px-6 py-4 text-center font-semibold">Qty Produksi</th>
                                        <th className="px-6 py-4 text-center font-semibold">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {p.details?.map((d, i) => (
                                        <tr key={i} className="transition-colors hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                                            <td className="px-6 py-4 font-semibold text-gray-800 dark:text-white/90">
                                                <div className="flex flex-col">
                                                    <span>{d.product}</span>
                                                    <span className="font-mono text-xs text-gray-400 dark:text-gray-550 mt-0.5 dark:text-gray-500">{d.product_code}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{formatQty(d.quantity_produced)}</td>
                                            <td className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">{d.unit}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="flex justify-start pt-4 print:hidden">
                    <Link href={route('hendhys.productions.index')} className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-bold text-gray-750 dark:text-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:hover:bg-white/[0.01] dark:border-gray-800 dark:bg-white/[0.03]">
                        <Icon name="arrow_back" className="text-[20px]" /> Kembali ke Daftar
                    </Link>
                </div>
            </div>
        </HendhysLayout>
    );
}
