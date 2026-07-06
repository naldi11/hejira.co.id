import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import OwnerLayout from '@/Layouts/OwnerLayout';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import { formatRupiah, formatQty } from '@/lib/format';

export default function ShiftDetail({ shift, transactions, summary }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedTransaction, setSelectedTransaction] = useState(null);

    // Format date Helper
    const formatDateTime = (dateString) => {
        if (!dateString) return '-';
        const d = new Date(dateString);
        return d.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    };

    // Filter transactions locally
    const filteredTransactions = transactions.filter(t => {
        if (!searchQuery) return true;
        const q = searchQuery.toLowerCase();
        
        const matchTrx = t.transaction_number?.toLowerCase().includes(q);
        const matchCustomer = (t.customer_name || 'umum').toLowerCase().includes(q);
        
        // Cek apakah ada produk dalam detail struk yang cocok dengan pencarian
        const matchProduct = t.details?.some(d => 
            d.product_name?.toLowerCase().includes(q)
        );

        return matchTrx || matchCustomer || matchProduct;
    });

    return (
        <OwnerLayout>
            <Head title={`Detail Shift Kasir - ${shift.user?.name || 'Kasir'}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                
                {/* --- HEADER --- */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link href={route('owner.dashboard')} className="flex items-center gap-1 text-sm font-medium text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300">
                                <Icon name="arrow_back" className="text-[16px]" />
                                Kembali ke Dashboard
                            </Link>
                        </div>
                        <h1 className="text-2xl font-black text-gray-900 dark:text-white">Detail Shift Kasir</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Rincian performa dan transaksi pada shift terpilih.</p>
                    </div>
                </div>

                {/* --- SHIFT INFO CARDS --- */}
                <div className="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.02]">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <Icon name="person" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Kasir</p>
                                <p className="text-lg font-bold text-gray-900 dark:text-white">{shift.user?.name || '-'}</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <div>
                                <p className="text-xs text-gray-500">Waktu Buka</p>
                                <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{formatDateTime(shift.opened_at)}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">Waktu Tutup</p>
                                <p className="text-sm font-semibold text-gray-800 dark:text-white/90">{formatDateTime(shift.closed_at)}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.02]">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                <Icon name="point_of_sale" />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Omset (Penjualan)</p>
                                <p className="text-2xl font-black text-gray-900 dark:text-white">{formatRupiah(summary.omset)}</p>
                            </div>
                        </div>
                        <div className="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                            <span className="text-sm text-gray-500">Total Transaksi (Struk)</span>
                            <span className="font-bold text-gray-800 dark:text-white/90">{transactions.length}</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.02]">
                        <h3 className="text-sm font-medium text-gray-500 mb-4 dark:text-gray-400">Rekap Metode Pembayaran</h3>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Icon name="payments" className="text-green-500" />
                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Tunai</span>
                                </div>
                                <span className="font-bold text-gray-900 dark:text-white">{formatRupiah(summary.tunai)}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Icon name="account_balance" className="text-blue-500" />
                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Transfer</span>
                                </div>
                                <span className="font-bold text-gray-900 dark:text-white">{formatRupiah(summary.transfer)}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Icon name="credit_card" className="text-purple-500" />
                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Debit / Kredit</span>
                                </div>
                                <span className="font-bold text-gray-900 dark:text-white">{formatRupiah(summary.debit + summary.kredit)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* --- TRANSACTIONS LIST --- */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.02]">
                    <div className="flex flex-col sm:flex-row items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02] gap-4">
                        <h2 className="text-lg font-bold text-gray-800 dark:text-white/90">Riwayat Transaksi Lengkap</h2>
                        <div className="relative w-full sm:w-72">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <Icon name="search" className="h-4 w-4 text-gray-400" />
                            </div>
                            <input
                                type="text"
                                className="block w-full rounded-lg border border-gray-300 bg-white p-2 pl-9 text-sm text-gray-900 focus:border-orange-500 focus:ring-orange-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Cari No. Trx, Pelanggan, atau Produk..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-gray-50/50 text-gray-500 dark:bg-white/[0.01] dark:text-gray-400">
                                <tr>
                                    <th className="px-6 py-4 font-semibold">Waktu</th>
                                    <th className="px-6 py-4 font-semibold">No. Transaksi</th>
                                    <th className="px-6 py-4 font-semibold">Pelanggan</th>
                                    <th className="px-6 py-4 font-semibold">Produk Terjual</th>
                                    <th className="px-6 py-4 text-right font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {filteredTransactions.length === 0 ? (
                                    <tr>
                                        <td colSpan="5" className="px-6 py-8 text-center text-gray-500">
                                            {searchQuery ? 'Tidak ada transaksi yang cocok dengan pencarian Anda.' : 'Tidak ada transaksi pada shift ini.'}
                                        </td>
                                    </tr>
                                ) : (
                                    filteredTransactions.map((t) => (
                                        <tr 
                                            key={t.id} 
                                            className="hover:bg-orange-50 dark:hover:bg-white/[0.05] cursor-pointer transition-colors"
                                            onClick={() => setSelectedTransaction(t)}
                                        >
                                            <td className="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                                {formatDateTime(t.created_at)}
                                            </td>
                                            <td className="px-6 py-4 font-bold text-gray-800 dark:text-white/90">
                                                {t.transaction_number}
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-300">
                                                {t.customer_name || 'Umum'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <ul className="list-disc pl-4 space-y-1">
                                                    {t.details?.map((d, idx) => (
                                                        <li key={idx} className="text-xs text-gray-600 dark:text-gray-400">
                                                            <span className="font-semibold text-gray-800 dark:text-gray-300">{d.product_name}</span> &times; {formatQty(d.quantity)}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </td>
                                            <td className="px-6 py-4 text-right font-bold text-gray-800 dark:text-white/90">
                                                {formatRupiah(t.grand_total)}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {/* --- RECEIPT MODAL --- */}
            <Modal 
                show={selectedTransaction !== null} 
                onClose={() => setSelectedTransaction(null)} 
                maxWidth="md"
                title="Detail Struk"
                subtitle={selectedTransaction?.transaction_number}
            >
                {selectedTransaction && (
                    <div>
                        <div className="space-y-4">
                            <div className="text-sm">
                                <p><span className="text-gray-500 w-24 inline-block">Waktu:</span> <span className="font-medium text-gray-900 dark:text-white">{formatDateTime(selectedTransaction.created_at)}</span></p>
                                <p><span className="text-gray-500 w-24 inline-block">Pelanggan:</span> <span className="font-medium text-gray-900 dark:text-white">{selectedTransaction.customer_name || 'Umum'}</span></p>
                            </div>

                            <div className="border-t border-b border-gray-100 py-3 dark:border-gray-800">
                                <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Item Terjual</p>
                                <div className="space-y-2">
                                    {selectedTransaction.details?.map((d, idx) => (
                                        <div key={idx} className="flex justify-between text-sm">
                                            <div className="flex-1">
                                                <p className="font-semibold text-gray-800 dark:text-gray-200">{d.product_name}</p>
                                                <p className="text-xs text-gray-500">{formatQty(d.quantity)} x {formatRupiah(d.price)}</p>
                                            </div>
                                            <div className="font-medium text-gray-900 dark:text-white text-right">
                                                {formatRupiah(d.subtotal)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                <div className="flex justify-between">
                                    <span>Subtotal</span>
                                    <span>{formatRupiah(selectedTransaction.subtotal)}</span>
                                </div>
                                {selectedTransaction.discount_amount > 0 && (
                                    <div className="flex justify-between text-red-500">
                                        <span>Diskon</span>
                                        <span>-{formatRupiah(selectedTransaction.discount_amount)}</span>
                                    </div>
                                )}
                                {selectedTransaction.tax_amount > 0 && (
                                    <div className="flex justify-between">
                                        <span>Pajak (PPN)</span>
                                        <span>{formatRupiah(selectedTransaction.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between font-bold text-lg text-gray-900 dark:text-white pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                                    <span>Total</span>
                                    <span>{formatRupiah(selectedTransaction.grand_total)}</span>
                                </div>
                            </div>
                            
                            <div className="bg-gray-50 p-3 rounded-lg dark:bg-white/[0.02]">
                                <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Pembayaran</p>
                                {selectedTransaction.payments && selectedTransaction.payments.length > 0 ? (
                                    selectedTransaction.payments.map((p, idx) => (
                                        <div key={idx} className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
                                            <span className="capitalize">{p.payment_method || p.type}</span>
                                            <span>{formatRupiah(p.amount)}</span>
                                        </div>
                                    ))
                                ) : (
                                    <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
                                        <span>Belum ada data</span>
                                        <span>{formatRupiah(0)}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </OwnerLayout>
    );
}
