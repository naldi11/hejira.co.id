import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import Icon from '@/Components/Icon';
import SelectField from '@/Components/SelectField';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import Layout, { input, Errors } from '../StockOpname/Layout';
import { uuid } from '../Izzi/Common';

const route = window.route;
const emptyItem = () => ({ product_id: '', quantity: '', notes: '' });

function Form({ products, catalogue: initialCatalogue, location, branches = [], today, currentTime }) {
    const { auth } = usePage().props;
    const catalogue = usePosCatalogue(
        route('production-materials.create'),
        products,
        initialCatalogue,
        location.branch_id,
    );
    const knownProducts = useRef(new Map(products.map((p) => [String(p.id), p])));
    catalogue.products.forEach((p) => knownProducts.current.set(String(p.id), p));

    const form = useForm({
        request_key: uuid(),
        branch_id: location.branch_id,
        date: today,
        time: currentTime,
        receiver_name: auth.user.name,
        reason: '',
        notes: '',
        evidence_reference: '',
        items: [emptyItem()],
    });

    const setItem = (index, key, value) =>
        form.setData(
            'items',
            form.data.items.map((item, i) =>
                i === index
                    ? {
                          ...item,
                          [key]: value,
                          ...(key === 'product_id' ? { quantity: '' } : {}),
                      }
                    : item,
            ),
        );

    const validItems = form.data.items.every((item) => {
        const product = knownProducts.current.get(String(item.product_id));
        return product?.unit_matches && Number(item.quantity) > 0 && Number(item.quantity) <= product.quantity;
    });

    return (
        <form
            className="space-y-6"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(route('production-materials.store'));
            }}
        >
            <Errors errors={form.errors} />

            {/* Card 1: Informasi Pemakaian */}
            <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 className="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">
                    1. Informasi Pemakaian Produksi
                </h3>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {branches.length > 1 && (
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cabang Hendhys
                            <select
                                className={`${input} mt-1.5`}
                                value={location.branch_id}
                                disabled={form.processing}
                                onChange={(e) =>
                                    router.get(route('production-materials.create'), { branch_id: e.target.value })
                                }
                            >
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                    )}

                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tanggal Pemakaian
                        <input
                            required
                            type="date"
                            max={today}
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
                        Digunakan Oleh
                        <input
                            required
                            minLength={2}
                            maxLength={255}
                            className={`${input} mt-1.5`}
                            value={form.data.receiver_name}
                            onChange={(e) => form.setData('receiver_name', e.target.value)}
                        />
                    </label>
                </div>

                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Keperluan Produksi
                        <input
                            required
                            minLength={5}
                            maxLength={1000}
                            placeholder="Contoh: Bahan untuk membuat adonan pagi"
                            className={`${input} mt-1.5`}
                            value={form.data.reason}
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                    </label>
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        No. Bukti / Catatan Tambahan (Opsional)
                        <input
                            maxLength={255}
                            placeholder="Contoh: Form Dapur #01"
                            className={`${input} mt-1.5`}
                            value={form.data.evidence_reference}
                            onChange={(e) => form.setData('evidence_reference', e.target.value)}
                        />
                    </label>
                </div>
            </div>

            {/* Card 2: Daftar Barang */}
            <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <div>
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">
                            2. Barang yang Digunakan dari {location.branch_name}
                        </h3>
                        <p className="text-xs text-gray-500">
                            Ketik nama atau kode barang pada kolom pilihan untuk mencari stok tersedia
                        </p>
                    </div>
                    <button
                        type="button"
                        disabled={form.data.items.length >= 100}
                        onClick={() => form.setData('items', [...form.data.items, emptyItem()])}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400"
                    >
                        <Icon name="add" className="text-[16px]" />
                        <span>Tambah Baris Barang</span>
                    </button>
                </div>

                <div className="divide-y divide-gray-100 p-6 dark:divide-gray-800">
                    {form.data.items.map((item, index) => {
                        const product = knownProducts.current.get(String(item.product_id));
                        const options = [...catalogue.products];
                        if (product && !options.some((p) => p.id === product.id)) options.unshift(product);

                        return (
                            <div
                                key={index}
                                className="grid grid-cols-1 items-start gap-4 py-4 first:pt-0 last:pb-0 lg:grid-cols-12"
                            >
                                <div className="lg:col-span-5">
                                    <label className="mb-1 block text-xs font-semibold text-gray-500">
                                        Barang #{index + 1}
                                    </label>
                                    <SelectField
                                        isLoading={catalogue.loading}
                                        filterOption={() => true}
                                        options={options.map((p) => ({
                                            value: p.id,
                                            label: `${p.name} (${p.code})`,
                                            sublabel: `Tersedia: ${p.quantity} ${p.stock_unit || p.unit}`,
                                            isDisabled:
                                                !p.unit_matches ||
                                                form.data.items.some(
                                                    (other, n) =>
                                                        n !== index && String(other.product_id) === String(p.id),
                                                ),
                                        }))}
                                        value={item.product_id}
                                        onChange={(value) => setItem(index, 'product_id', value)}
                                        onInputChange={(value, meta) => {
                                            if (meta.action === 'input-change') catalogue.setQuery(value);
                                        }}
                                        placeholder="Ketik & pilih barang…"
                                    />
                                </div>

                                <div className="lg:col-span-3">
                                    <label className="mb-1 block text-xs font-semibold text-gray-500">
                                        Jumlah Digunakan ({product?.unit || 'Satuan'})
                                    </label>
                                    <input
                                        required
                                        disabled={!product?.unit_matches}
                                        type="number"
                                        min="1"
                                        step="1"
                                        max={product?.quantity || 0}
                                        placeholder="0"
                                        className={`${input} font-bold`}
                                        value={item.quantity}
                                        onChange={(e) => setItem(index, 'quantity', e.target.value)}
                                    />
                                    {product?.unit_matches && (
                                        <span className="mt-1 block text-[11px] text-gray-500">
                                            Sisa stok:{' '}
                                            <strong>
                                                {product.quantity - Number(item.quantity || 0)} {product.unit}
                                            </strong>
                                        </span>
                                    )}
                                </div>

                                <div className="lg:col-span-3">
                                    <label className="mb-1 block text-xs font-semibold text-gray-500">
                                        Catatan Barang (Opsional)
                                    </label>
                                    <input
                                        maxLength={500}
                                        placeholder="Contoh: 2 adonan"
                                        className={input}
                                        value={item.notes}
                                        onChange={(e) => setItem(index, 'notes', e.target.value)}
                                    />
                                </div>

                                <div className="flex items-end justify-end pt-6 lg:col-span-1">
                                    {form.data.items.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'items',
                                                    form.data.items.filter((_, i) => i !== index),
                                                )
                                            }
                                            className="rounded-lg p-2 text-gray-400 hover:bg-rose-50 hover:text-rose-600"
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
                    <Link
                        href={route('production-materials.index')}
                        className="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        disabled={form.processing || !validItems || Boolean(catalogue.error)}
                        className="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-40"
                    >
                        <Icon name="check_circle" className="text-[18px]" />
                        <span>{form.processing ? 'Menyimpan…' : 'Catat Pemakaian & Kurangi Stok'}</span>
                    </button>
                </div>
            </div>
        </form>
    );
}

export default function Create(props) {
    return (
        <Layout pageTitle="Gunakan Stok untuk Produksi">
            <Head title="Gunakan Stok untuk Produksi" />
            <div className="space-y-6">
                <div>
                    <Link
                        href={route('production-materials.index')}
                        className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                    >
                        <Icon name="arrow_back" className="text-[15px]" />
                        <span>Kembali ke Riwayat Pemakaian Stok</span>
                    </Link>
                    <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                        Gunakan Stok untuk Produksi — {props.location.branch_name}
                    </h2>
                </div>
                <Form key={props.location.branch_id} {...props} />
            </div>
        </Layout>
    );
}
