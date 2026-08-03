import { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Modal';

/**
 * Dialog konfirmasi pembatalan transaksi penjualan.
 *
 * Sengaja dibuat sebagai komponen UI murni: rute tujuan dikirim lewat prop
 * `voidRoute`, sehingga Hendhys dan Jihan's tetap memakai controller & aturan
 * masing-masing (tidak ada logika bisnis salah satu unit di dalam sini).
 */
export default function VoidTransactionModal({ show, onClose, transaction, voidRoute, accent = 'amber' }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const close = () => {
        if (processing) return;
        setReason('');
        setError(null);
        onClose();
    };

    const submit = (e) => {
        e.preventDefault();
        if (processing) return;
        if (reason.trim().length < 5) {
            setError('Alasan pembatalan minimal 5 karakter.');
            return;
        }

        setProcessing(true);
        router.post(voidRoute, { reason: reason.trim() }, {
            preserveScroll: true,
            onSuccess: () => { setReason(''); setError(null); onClose(); },
            onError: (errs) => setError(errs.reason ?? 'Gagal membatalkan transaksi.'),
            onFinish: () => setProcessing(false),
        });
    };

    const accentBtn = accent === 'orange'
        ? 'bg-orange-600 hover:bg-orange-700'
        : 'bg-amber-600 hover:bg-amber-700';

    return (
        <Modal show={show} onClose={close} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                    Batalkan Transaksi {transaction?.transaction_number}
                </h2>

                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Stok setiap barang akan dikembalikan dan transaksi ini tidak lagi
                    dihitung dalam laporan. Nomor transaksi tetap disimpan sebagai jejak audit.
                </p>

                <div className="mt-4">
                    <label htmlFor="void-reason" className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Alasan pembatalan <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                        id="void-reason"
                        value={reason}
                        onChange={(e) => { setReason(e.target.value); setError(null); }}
                        rows={3}
                        autoFocus
                        placeholder="Contoh: salah input jumlah, pelanggan batal membeli"
                        className="w-full rounded-lg border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    {error && <p className="mt-1 text-sm text-rose-600 dark:text-rose-400">{error}</p>}
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <button type="button" onClick={close} disabled={processing}
                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Kembali
                    </button>
                    <button type="submit" disabled={processing}
                        className={`rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 ${accentBtn}`}>
                        {processing ? 'Membatalkan…' : 'Batalkan Transaksi'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
