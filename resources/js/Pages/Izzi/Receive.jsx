import { Head, Link, useForm } from '@inertiajs/react';
import { useIngredients } from './IngredientCatalogue';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import SelectField from '@/Components/SelectField';
import { Navigation, input, Errors, uuid } from './Common';

const route = window.route;
const empty = () => ({ product_id: '', quantity: '', mode: 'base' });

export default function Receive(props) {
    const form = useForm({
        branch_id: props.branch.id,
        request_key: uuid(),
        date: props.today,
        time: props.currentTime,
        origin: '',
        notes: '',
        evidence_reference: '',
        items: [empty()],
    });

    const { catalogue, ingredients } = useIngredients(
        props.ingredients,
        props.catalogue,
        form.data.items.map((r) => r.product_id),
    );

    const set = (i, key, value) =>
        form.setData(
            'items',
            form.data.items.map((r, n) => (n === i ? { ...r, [key]: value } : r)),
        );

    return (
        <IzziLayout pageTitle="Terima Bahan Masuk Izzi">
            <Head title="Terima Bahan Masuk" />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route('izzi.receipts.store'));
                }}
                className="space-y-6"
            >
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Terima Bahan Masuk — {props.branch?.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Catat kedatangan bahan baku langsung di cabang (untuk kiriman dari Gudang Utama gunakan{' '}
                            <Link href={route('branch-transfers.index')} className="font-semibold text-brand-600 underline">
                                Mutasi Stok
                            </Link>
                            )
                        </p>
                    </div>
                    <Link
                        href={route('izzi.receipts', { branch_id: props.branch.id })}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <Icon name="history" className="text-[18px]" />
                        <span>Riwayat Penerimaan</span>
                    </Link>
                </div>

                <Navigation {...props} />
                <Errors errors={form.errors} />

                {/* Card 1: Informasi Penerimaan */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 className="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">
                        Informasi Dokumen Penerimaan
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tanggal Diterima
                            <input
                                required
                                type="date"
                                max={props.today}
                                className={`${input} mt-1.5`}
                                value={form.data.date}
                                onChange={(e) => form.setData('date', e.target.value)}
                            />
                        </label>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Jam (WIB)
                            <input
                                required
                                type="time"
                                className={`${input} mt-1.5`}
                                value={form.data.time}
                                onChange={(e) => form.setData('time', e.target.value)}
                            />
                        </label>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Asal / Pemasok
                            <input
                                required
                                placeholder="Contoh: Supplier Sayur"
                                className={`${input} mt-1.5`}
                                value={form.data.origin}
                                onChange={(e) => form.setData('origin', e.target.value)}
                            />
                        </label>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            No. Nota / Bukti (Opsional)
                            <input
                                placeholder="Contoh: INV-001"
                                className={`${input} mt-1.5`}
                                value={form.data.evidence_reference}
                                onChange={(e) => form.setData('evidence_reference', e.target.value)}
                            />
                        </label>
                    </div>
                    <label className="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Catatan Penerimaan
                        <input
                            required
                            minLength={5}
                            placeholder="Contoh: Bahan diterima lengkap dalam kondisi baik"
                            className={`${input} mt-1.5`}
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                    </label>
                </div>

                {/* Card 2: Tabel Daftar Bahan */}
                <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <div>
                            <h3 className="font-semibold text-gray-800 dark:text-white/90">Daftar Bahan yang Diterima</h3>
                            <p className="text-xs text-gray-500">
                                Ketik di kolom pilihan bahan untuk mencari dari Master Produk bersama
                            </p>
                        </div>
                        <button
                            type="button"
                            disabled={form.data.items.length >= 100}
                            onClick={() => form.setData('items', [...form.data.items, empty()])}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400"
                        >
                            <Icon name="add" className="text-[16px]" />
                            <span>Tambah Baris Bahan</span>
                        </button>
                    </div>

                    <div className="divide-y divide-gray-100 p-6 dark:divide-gray-800">
                        {form.data.items.map((item, i) => {
                            const p = ingredients.find((prod) => String(prod.id) === String(item.product_id));
                            const factor = item.mode === 'purchase' ? Number(p?.purchase_factor || 0) : 1;
                            const addedQty = (Number(item.quantity || 0) * factor).toLocaleString('id-ID', {
                                maximumFractionDigits: 3,
                            });

                            return (
                                <div
                                    key={i}
                                    className="grid grid-cols-1 items-start gap-4 py-4 first:pt-0 last:pb-0 lg:grid-cols-12"
                                >
                                    <div className="lg:col-span-5">
                                        <label className="mb-1 block text-xs font-semibold text-gray-500">
                                            Bahan Baku #{i + 1}
                                        </label>
                                        <SelectField
                                            required
                                            isLoading={catalogue.loading}
                                            filterOption={() => true}
                                            onInputChange={(val, meta) => {
                                                if (meta.action === 'input-change') catalogue.setQuery(val);
                                            }}
                                            value={item.product_id}
                                            onChange={(v) =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.map((r, n) =>
                                                        n === i ? { ...r, product_id: v, mode: 'base' } : r,
                                                    ),
                                                )
                                            }
                                            options={ingredients.map((prod) => ({
                                                value: prod.id,
                                                label: `${prod.name} (${prod.code})`,
                                                sublabel: `Satuan dasar: ${prod.unit}`,
                                                isDisabled: form.data.items.some(
                                                    (r, n) => n !== i && String(r.product_id) === String(prod.id),
                                                ),
                                            }))}
                                            placeholder="Ketik & pilih bahan baku…"
                                        />
                                    </div>

                                    <div className="lg:col-span-3">
                                        <label className="mb-1 block text-xs font-semibold text-gray-500">
                                            Satuan Input
                                        </label>
                                        <select
                                            className={input}
                                            value={item.mode}
                                            onChange={(e) => set(i, 'mode', e.target.value)}
                                        >
                                            {Boolean(p?.configured) && (
                                                <option value="purchase">
                                                    {p.purchase_unit} (1 {p.purchase_unit} = {p.purchase_factor} {p.unit})
                                                </option>
                                            )}
                                            <option value="base">{p?.unit || 'Satuan Dasar'}</option>
                                        </select>
                                    </div>

                                    <div className="lg:col-span-3">
                                        <label className="mb-1 block text-xs font-semibold text-gray-500">
                                            Jumlah Diterima
                                        </label>
                                        <input
                                            required
                                            type="number"
                                            min="0.001"
                                            step="0.001"
                                            placeholder="0"
                                            className={`${input} font-bold`}
                                            value={item.quantity}
                                            onChange={(e) => set(i, 'quantity', e.target.value)}
                                        />
                                        {p && Number(item.quantity) > 0 && (
                                            <span className="mt-1 block text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                +{addedQty} {p.unit} ke stok
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex items-end justify-end pt-6 lg:col-span-1">
                                        {form.data.items.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    form.setData(
                                                        'items',
                                                        form.data.items.filter((_, n) => n !== i),
                                                    )
                                                }
                                                className="rounded-lg p-2 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                title="Hapus baris"
                                            >
                                                <Icon name="delete" className="text-[20px]" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div className="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-40"
                        >
                            <Icon name="check_circle" className="text-[18px]" />
                            <span>{form.processing ? 'Menyimpan…' : 'Simpan Penerimaan Bahan'}</span>
                        </button>
                    </div>
                </div>
            </form>
        </IzziLayout>
    );
}
