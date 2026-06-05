import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { formatQty } from '@/lib/format';
import Button from '@/Components/ui/button/Button';

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

    const inputClass = 'w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800';
    const areaClass = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:bg-gray-900/50 dark:focus:border-brand-800 resize-none';

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="space-y-5">
                <div className="space-y-3.5 rounded-xl border border-gray-200 bg-gray-50/50 p-4.5 dark:border-gray-800 dark:bg-gray-900/30">
                    <div className="flex items-center justify-between">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">ID Produk</span>
                        <span className="rounded-md bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-500 dark:bg-brand-500/10 dark:text-brand-400">{product.id}</span>
                    </div>
                    <p className="text-sm font-bold text-gray-800 dark:text-white/90">{product.name}</p>
                    <div className="flex items-center gap-2 border-t border-gray-150/60 pt-3 dark:border-gray-800">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Stok Sistem:</span>
                        <span className="text-xs font-bold text-gray-700 dark:text-gray-350">{formatQty(product.current_stock)} {product.unit ?? 'PCS'}</span>
                    </div>
                </div>

                <div className="space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
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
                            className={`${inputClass} pr-16`}
                        />
                        <span className="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400 dark:text-gray-500">{product.unit ?? 'PCS'}</span>
                    </div>
                    {errors.quantity && <p className="text-xs font-bold text-rose-600 dark:text-rose-455">{errors.quantity}</p>}
                    {diff !== 0 && (
                        <div className="flex items-center gap-2 px-1">
                            <div className={`h-1.5 w-1.5 rounded-full ${diff > 0 ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                            <p className={`text-xs font-semibold ${diff > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-605 dark:text-rose-400'}`}>
                                Selisih penyesuaian: {diff > 0 ? '+' : ''}{diff} {product.unit ?? 'PCS'}.
                            </p>
                        </div>
                    )}
                </div>

                <div className="space-y-2">
                    <label className="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Alasan Penyesuaian <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                        required
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder="Contoh: Barang rusak, hilang, salah input..."
                        className={areaClass}
                    />
                    {errors.notes && <p className="text-xs font-bold text-rose-605 dark:text-rose-455">{errors.notes}</p>}
                </div>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row pt-2">
                <Button
                    type="submit"
                    disabled={processing}
                    className="flex-1"
                >
                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                >
                    Batal
                </Button>
            </div>
        </form>
    );
}

export default function AdjustModal({ product, onClose }) {
    return (
        <Modal show={!!product} onClose={onClose} title="Stock Opname" subtitle="Penyesuaian Saldo Fisik" icon="balance" maxWidth="max-w-md">
            {product && <AdjustForm key={product.id} product={product} onClose={onClose} />}
        </Modal>
    );
}
