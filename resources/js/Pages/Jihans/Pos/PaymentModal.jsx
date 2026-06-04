import { useEffect, useRef } from 'react';
import { formatRupiah } from '@/lib/format';

const QUICK = [10000, 20000, 50000, 100000, 200000];

/** Cash-payment modal: quick-cash buttons, amount received, change, and confirm. */
export default function PaymentModal({ grandTotal, amountPaid, setAmountPaid, onClose, onProcess, processing }) {
    const inputRef = useRef(null);
    useEffect(() => { inputRef.current?.focus(); inputRef.current?.select(); }, []);

    const change = amountPaid > grandTotal ? amountPaid - grandTotal : 0;
    const onKeyDown = (e) => { if (e.key === 'Enter') onProcess(); else if (e.key === 'Escape') onClose(); };

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div className="flex w-full max-w-md flex-col overflow-hidden rounded-xl border border-gray-400 bg-white shadow-2xl">
                <div className="flex items-center justify-between bg-green-700 px-4 py-2 text-white">
                    <span className="text-sm font-bold">Pembayaran Tunai</span>
                    <button onClick={onClose} className="font-bold hover:text-red-300">✕</button>
                </div>
                <div className="flex flex-col gap-4 p-5">
                    <div>
                        <label className="mb-1 block text-xs font-semibold text-gray-600">Total Tagihan</label>
                        <div className="rounded-lg bg-gray-100 px-4 py-2 text-right text-2xl font-bold text-red-600">{formatRupiah(grandTotal)}</div>
                    </div>
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-gray-600">Nominal Cepat</label>
                        <div className="grid grid-cols-3 gap-1.5">
                            <button type="button" onClick={() => setAmountPaid(grandTotal)} className="rounded border border-gray-400 bg-orange-100 py-2 text-xs font-bold text-orange-800 hover:bg-orange-200">Uang Pas</button>
                            {QUICK.map((v) => (
                                <button key={v} type="button" onClick={() => setAmountPaid(v)} className="rounded border border-gray-400 bg-gray-100 py-2 text-xs font-bold hover:bg-gray-200">{v.toLocaleString('id-ID')}</button>
                            ))}
                        </div>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-semibold text-gray-600">Nominal Diterima</label>
                        <input ref={inputRef} type="number" value={amountPaid} onChange={(e) => setAmountPaid(Number(e.target.value) || 0)} onKeyDown={onKeyDown}
                            className="w-full rounded-lg border-gray-300 py-2 text-right text-2xl font-bold text-blue-600 focus:border-orange-500 focus:ring-orange-500" />
                    </div>
                    <div className="text-right">
                        <span className="text-xs font-bold text-gray-600">Kembali: </span>
                        <span className="text-2xl font-bold text-green-600">{formatRupiah(change)}</span>
                    </div>
                </div>
                <div className="flex justify-end gap-2 border-t border-gray-200 bg-gray-100 p-3">
                    <button onClick={onClose} className="rounded border border-gray-400 bg-white px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button onClick={onProcess} disabled={processing} className="rounded bg-green-600 px-5 py-2 font-bold text-white hover:bg-green-700 disabled:opacity-50">
                        {processing ? 'Memproses...' : 'Simpan & Cetak'}
                    </button>
                </div>
            </div>
        </div>
    );
}
