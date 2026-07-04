import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';

export default function Form({ karyawans, products, type, warning, targetDate, prediction_id, isEdit = false, production = null, formAction }) {
    const isPrediksi = type === 'prediksi';

    // State for basic info
    const [date, setDate] = useState(isEdit ? production.date : (targetDate || new Date().toISOString().slice(0, 10)));
    const [notes, setNotes] = useState(isEdit ? (production.notes || '') : '');

    // State for which products are selected for the matrix
    const [selectedProductIds, setSelectedProductIds] = useState([]);

    // State for the matrix data: matrix[karyawanId][productId] = quantity
    const [matrix, setMatrix] = useState({});

    // Initialize state on edit or predictions->aktual transition
    useEffect(() => {
        if (production && production.details) {
            const initialMatrix = {};
            const initialSelected = new Set();
            
            production.details.forEach(detail => {
                if (detail.quantity > 0) {
                    initialSelected.add(detail.product_id);
                    if (!initialMatrix[detail.karyawan_id]) {
                        initialMatrix[detail.karyawan_id] = {};
                    }
                    initialMatrix[detail.karyawan_id][detail.product_id] = parseInt(detail.quantity, 10);
                }
            });
            
            setSelectedProductIds(Array.from(initialSelected));
            setMatrix(initialMatrix);
        }
    }, [production]);

    const handleProductSelect = (productId) => {
        if (selectedProductIds.includes(productId)) {
            setSelectedProductIds(selectedProductIds.filter(id => id !== productId));
        } else {
            setSelectedProductIds([...selectedProductIds, productId]);
        }
    };

    const handleQtyChange = (karyawanId, productId, val) => {
        const value = val === '' ? '' : parseInt(val, 10);
        setMatrix(prev => ({
            ...prev,
            [karyawanId]: {
                ...(prev[karyawanId] || {}),
                [productId]: value
            }
        }));
    };

    const getColumnTotal = (productId) => {
        let total = 0;
        karyawans.forEach(k => {
            total += (parseInt(matrix[k.id]?.[productId], 10) || 0);
        });
        return total;
    };

    const getRowTotal = (karyawanId) => {
        let total = 0;
        selectedProductIds.forEach(pid => {
            total += (parseInt(matrix[karyawanId]?.[pid], 10) || 0);
        });
        return total;
    };

    // Calculate Grand Total
    const getGrandTotal = () => {
        let total = 0;
        karyawans.forEach(k => {
            total += getRowTotal(k.id);
        });
        return total;
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Convert matrix to array format: { karyawan_id, product_id, quantity }
        const details = [];
        karyawans.forEach(k => {
            selectedProductIds.forEach(pid => {
                const qty = parseInt(matrix[k.id]?.[pid], 10) || 0;
                if (qty > 0) {
                    details.push({
                        karyawan_id: k.id,
                        product_id: pid,
                        quantity: qty
                    });
                }
            });
        });

        const payload = { date, notes, prediction_id, details };

        if (isEdit) {
            router.put(formAction, payload);
        } else {
            router.post(formAction, payload);
        }
    };

    const selectedProducts = useMemo(() => {
        return products.filter(p => selectedProductIds.includes(p.id));
    }, [products, selectedProductIds]);

    return (
        <JihansLayout pageTitle={`Form ${isPrediksi ? 'Prediksi' : 'Aktual'} Produksi`}>
            <Head title={`Form ${isPrediksi ? 'Prediksi' : 'Aktual'} Produksi`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link
                        href={route('jihans.production.index')}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-500 shadow-theme-xs hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                    >
                        <Icon name="arrow_back" className="text-[20px]" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            {isEdit ? 'Edit ' : 'Input '} 
                            {isPrediksi ? 'Prediksi Produksi' : 'Aktual Produksi'}
                        </h1>
                    </div>
                </div>

                {warning && (
                    <div className="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-900/30 dark:bg-warning-900/10">
                        <div className="flex gap-3">
                            <Icon name="warning" className="text-warning-600 dark:text-warning-500" />
                            <div>
                                <h3 className="font-medium text-warning-800 dark:text-warning-400">Peringatan</h3>
                                <p className="mt-1 text-sm text-warning-700 dark:text-warning-500/80">
                                    {warning}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                    {/* SECTION 1: Informasi Sesi & Pemilihan Produk */}
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Sesi & Produk</h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                                <input
                                    type="date"
                                    value={date}
                                    onChange={(e) => setDate(e.target.value)}
                                    readOnly={!!warning}
                                    className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white read-only:bg-gray-100"
                                    required
                                />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan (Opsional)</label>
                                <textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    readOnly={!!warning}
                                    rows="1"
                                    className="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white read-only:bg-gray-100"
                                    placeholder="Tulis catatan jika ada..."
                                />
                            </div>
                        </div>

                        <div>
                            <label className="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Pilih Produk yang Diproduksi Hari Ini:
                            </label>
                            <div className="flex flex-wrap gap-3">
                                {products.map(product => (
                                    <label 
                                        key={product.id} 
                                        className={`flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition-colors ${
                                            selectedProductIds.includes(product.id) 
                                                ? 'bg-brand-50 border-brand-500 text-brand-700 dark:bg-brand-900/20 dark:border-brand-500 dark:text-brand-300' 
                                                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-750'
                                        }`}
                                    >
                                        <input 
                                            type="checkbox" 
                                            className="rounded text-brand-600 focus:ring-brand-500"
                                            checked={selectedProductIds.includes(product.id)}
                                            onChange={() => handleProductSelect(product.id)}
                                            disabled={!!warning}
                                        />
                                        <span className="text-sm font-medium">{product.name}</span>
                                    </label>
                                ))}
                                {products.length === 0 && (
                                    <p className="text-sm text-gray-500 italic">Belum ada data produk di Master Data dengan Tipe Sumber "Produksi Sendiri".</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* SECTION 2: Matriks Input */}
                    <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
                        <div className="p-5 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Rincian Produksi (Per Karyawan)</h2>
                            <span className="font-semibold text-brand-600 dark:text-brand-400">Total Keseluruhan: {getGrandTotal()}</span>
                        </div>
                        
                        <div className="overflow-x-auto">
                            {selectedProductIds.length === 0 ? (
                                <div className="p-10 text-center text-gray-500">
                                    Silakan centang produk di atas untuk memunculkan kolom input.
                                </div>
                            ) : (
                                <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                    <thead className="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-800/80 dark:text-gray-300 sticky top-0 z-10">
                                        <tr>
                                            <th className="px-4 py-4 font-semibold sticky left-0 bg-gray-100 dark:bg-gray-800/80 z-20 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151] w-48">Nama Karyawan</th>
                                            {selectedProducts.map(p => (
                                                <th key={p.id} className="px-4 py-4 font-semibold min-w-[130px] text-center">{p.name}</th>
                                            ))}
                                            <th className="px-4 py-4 font-semibold text-center w-24 border-l border-gray-200 dark:border-gray-700">Total Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                        {karyawans.map(karyawan => (
                                            <tr key={karyawan.id} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                                <td className="px-4 py-3 font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-[#111827] shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151]">
                                                    {karyawan.name}
                                                </td>
                                                {selectedProducts.map(p => (
                                                    <td key={p.id} className="px-2 py-3 text-center">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={matrix[karyawan.id]?.[p.id] || ''}
                                                            onChange={(e) => handleQtyChange(karyawan.id, p.id, e.target.value)}
                                                            readOnly={!!warning}
                                                            placeholder="0"
                                                            className="w-full text-center rounded border border-gray-300 px-3 py-2 text-base font-medium text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:text-white dark:bg-gray-900 read-only:bg-gray-100 dark:read-only:bg-gray-800 hover:border-gray-400 transition-colors"
                                                            onFocus={(e) => e.target.select()}
                                                        />
                                                    </td>
                                                ))}
                                                <td className="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100 text-center bg-gray-50/50 dark:bg-gray-800/30 border-l border-gray-200 dark:border-gray-700">
                                                    {getRowTotal(karyawan.id)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-brand-50 text-brand-900 dark:bg-brand-900/20 dark:text-brand-300 font-semibold sticky bottom-0 z-10 border-t border-brand-200 dark:border-brand-900/50">
                                        <tr>
                                            <td className="px-4 py-4 sticky left-0 bg-brand-50 dark:bg-[#1f2937] shadow-[1px_0_0_0_#bfdbfe] dark:shadow-[1px_0_0_0_#1e3a8a]">Total Kolom</td>
                                            {selectedProducts.map(p => (
                                                <td key={p.id} className="px-4 py-4 text-center text-lg">{getColumnTotal(p.id)}</td>
                                            ))}
                                            <td className="px-4 py-4 text-center border-l border-brand-200 dark:border-brand-900/50 text-xl">{getGrandTotal()}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 sticky bottom-4 z-30">
                        <Link
                            href={route('jihans.production.index')}
                            className="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700"
                        >
                            Batal
                        </Link>
                        {!warning && (
                            <button
                                type="submit"
                                className="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-8 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-700 transition-colors"
                            >
                                <Icon name="save" className="text-[18px]" />
                                Simpan {isPrediksi ? 'Prediksi' : 'Aktual'}
                            </button>
                        )}
                    </div>
                </form>
            </div>
        </JihansLayout>
    );
}
