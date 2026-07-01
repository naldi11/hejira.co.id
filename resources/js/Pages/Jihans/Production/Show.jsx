import React from 'react';
import { Head, Link } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

export default function Show({ production }) {
    // Process details into matrix format
    const matrix = {};
    const productMap = {}; // pid -> name
    const karyawanMap = {}; // kid -> name
    
    production.details.forEach(d => {
        const pid = d.product_id;
        const kid = d.karyawan_id;
        
        if (!productMap[pid] && d.product) {
            productMap[pid] = d.product.name;
        }
        
        if (!karyawanMap[kid]) {
            karyawanMap[kid] = d.karyawan ? d.karyawan.name : 'Karyawan Dihapus / Tidak Diketahui';
        }
        
        if (!matrix[kid]) matrix[kid] = {};
        matrix[kid][pid] = d.quantity;
    });
    
    const productIds = Object.keys(productMap);
    const karyawanIds = Object.keys(karyawanMap);

    const getColumnTotal = (pid) => {
        let total = 0;
        karyawanIds.forEach(kid => {
            total += Number(matrix[kid]?.[pid] || 0);
        });
        return total;
    };

    const getRowTotal = (kid) => {
        let total = 0;
        productIds.forEach(pid => {
            total += Number(matrix[kid]?.[pid] || 0);
        });
        return total;
    };

    const getGrandTotal = () => {
        let total = 0;
        karyawanIds.forEach(kid => {
            total += getRowTotal(kid);
        });
        return total;
    };

    return (
        <JihansLayout pageTitle={`Detail Produksi ${production.session_number}`}>
            <Head title={`Detail Produksi ${production.session_number}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link
                        href={route('jihans.production.index')}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-500 shadow-theme-xs hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                    >
                        <Icon name="arrow_back" className="text-[20px]" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Detail Produksi</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{production.session_number}</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Status Sesi</p>
                        <p className="font-semibold text-gray-900 dark:text-white uppercase">{production.type}</p>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Tanggal</p>
                        <p className="font-semibold text-gray-900 dark:text-white">{new Date(production.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</p>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Dibuat Oleh</p>
                        <p className="font-semibold text-gray-900 dark:text-white">{production.creator?.name || '-'}</p>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Produk Dihasilkan</p>
                        <p className="font-semibold text-gray-900 dark:text-white">{getGrandTotal()}</p>
                    </div>
                </div>

                {production.notes && (
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <h3 className="font-medium text-gray-900 dark:text-white mb-2">Catatan</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">{production.notes}</p>
                    </div>
                )}

                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
                    <div className="p-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Rincian Per Karyawan</h2>
                    </div>
                    
                    <div className="overflow-x-auto">
                        {productIds.length === 0 ? (
                            <div className="p-10 text-center text-gray-500">Tidak ada rincian produksi untuk sesi ini.</div>
                        ) : (
                            <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead className="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-800/80 dark:text-gray-300">
                                    <tr>
                                        <th className="px-4 py-4 font-semibold border-b border-gray-200 dark:border-gray-700 sticky left-0 bg-gray-100 dark:bg-gray-800/80 z-20 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151]">Nama Karyawan</th>
                                        {productIds.map(pid => (
                                            <th key={pid} className="px-4 py-4 font-semibold text-center border-b border-gray-200 dark:border-gray-700 whitespace-nowrap min-w-[120px]">{productMap[pid]}</th>
                                        ))}
                                        <th className="px-4 py-4 font-semibold text-center border-l border-b border-gray-200 dark:border-gray-700 w-24">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                    {karyawanIds.map(kid => (
                                        <tr key={kid} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-[#111827] shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151]">
                                                {karyawanMap[kid]}
                                            </td>
                                            {productIds.map(pid => (
                                                <td key={pid} className="px-4 py-3 text-center">
                                                    {Number(matrix[kid]?.[pid] || 0) > 0 ? (
                                                        <span className="font-semibold text-brand-600 dark:text-brand-400">{Number(matrix[kid][pid])}</span>
                                                    ) : (
                                                        <span className="text-gray-300 dark:text-gray-700">-</span>
                                                    )}
                                                </td>
                                            ))}
                                            <td className="px-4 py-3 font-bold text-gray-900 dark:text-gray-100 text-center bg-gray-50/50 dark:bg-gray-800/30 border-l border-gray-200 dark:border-gray-700">
                                                {getRowTotal(kid)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="bg-brand-50 text-brand-900 dark:bg-brand-900/20 dark:text-brand-300 font-semibold border-t border-brand-200 dark:border-brand-900/50">
                                    <tr>
                                        <td className="px-4 py-4 sticky left-0 bg-brand-50 dark:bg-[#1f2937] shadow-[1px_0_0_0_#bfdbfe] dark:shadow-[1px_0_0_0_#1e3a8a]">Total Kolom</td>
                                        {productIds.map(pid => (
                                            <td key={pid} className="px-4 py-4 text-center text-lg">{getColumnTotal(pid)}</td>
                                        ))}
                                        <td className="px-4 py-4 text-center border-l border-brand-200 dark:border-brand-900/50 text-xl">{getGrandTotal()}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </JihansLayout>
    );
}
