import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { formatQty } from '@/lib/format';

const route = window.route;

/** Inner form — keyed by product id so useForm re-initialises per product. */
function AdjustForm({ product, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        product_id: product.id,
        unit_id: product.unit_id,
        quantity: Math.round(product.current_stock),
        notes: '',
    });

    const diff = Number(data.quantity) - Math.round(product.current_stock);

    const submit = (e) => {
        e.preventDefault();
        post(route('gudang.stock.adjust'), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <div className="space-y-5">
                <div className="space-y-4 rounded-3xl border border-slate-100 bg-slate-50 p-5">
                    <div className="flex items-center justify-between">
                        <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Produk Terpilih</span>
                        <span className="rounded-lg bg-indigo-50 px-2 py-0.5 text-[10px] font-black text-indigo-500">{product.id}</span>
                    </div>
                    <p className="font-headline text-base font-black leading-tight text-slate-800">{product.name}</p>
                    <div className="flex items-center gap-2 border-t border-slate-200/50 pt-2">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500">Stok Sistem:</span>
                        <span className="text-sm font-black text-slate-700">{formatQty(product.current_stock)} {product.unit ?? 'PCS'}</span>
                    </div>
                </div>

                <div className="space-y-2">
                    <label className="ml-1 block text-xs font-black uppercase tracking-widest text-slate-500">
                        Stok Fisik Sebenarnya <span className="text-rose-500">*</span>
                    </label>
                    <div className="relative">
                        <input
                            type="number"
                            min="0"
                            step="1"
                            required
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value === '' ? '' : Math.max(0, Math.round(Number(e.target.value))))}
                            className="w-full rounded-2xl border-2 border-slate-100 bg-slate-50 py-4 pl-6 pr-16 text-lg font-black tabular-nums text-slate-900 transition-all focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                        />
                        <span className="absolute right-6 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">{product.unit ?? 'PCS'}</span>
                    </div>
                    {errors.quantity && <p className="px-2 text-[11px] font-bold text-rose-600">{errors.quantity}</p>}
                    {diff !== 0 && (
                        <div className="flex items-center gap-2 px-2">
                            <div className="h-1.5 w-1.5 rounded-full bg-amber-500" />
                            <p className="text-[11px] font-bold italic text-amber-600">
                                Akan dicatat sebagai selisih {diff > 0 ? '+' : ''}{diff} units.
                            </p>
                        </div>
                    )}
                </div>

                <div className="space-y-2">
                    <label className="ml-1 block text-xs font-black uppercase tracking-widest text-slate-500">
                        Alasan Penyesuaian <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                        required
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder="Contoh: Barang rusak, hilang, salah input..."
                        className="w-full resize-none rounded-2xl border-2 border-slate-100 bg-slate-50 px-6 py-4 text-sm font-medium text-slate-800 transition-all placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                    />
                    {errors.notes && <p className="px-2 text-[11px] font-bold text-rose-600">{errors.notes}</p>}
                </div>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
                <button
                    type="submit"
                    disabled={processing}
                    className="flex-1 rounded-2xl bg-slate-900 px-8 py-4 text-sm font-black uppercase tracking-widest text-white shadow-xl shadow-slate-900/20 transition-all hover:bg-indigo-600 active:scale-[0.98] disabled:opacity-50"
                >
                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </button>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-2xl bg-slate-100 px-8 py-4 text-sm font-black uppercase tracking-widest text-slate-600 transition-all hover:bg-slate-200"
                >
                    Batal
                </button>
            </div>
        </form>
    );
}

export default function AdjustModal({ product, onClose }) {
    return (
        <Modal show={!!product} onClose={onClose} title="Stock Opname" subtitle="Penyesuaian Saldo Fisik" icon="balance">
            {product && <AdjustForm key={product.id} product={product} onClose={onClose} />}
        </Modal>
    );
}
