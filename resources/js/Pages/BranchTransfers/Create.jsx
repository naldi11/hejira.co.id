import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import Icon from '@/Components/Icon';
import SelectField from '@/Components/SelectField';
import Layout, { input, Errors } from '../StockOpname/Layout';
import { uuid } from '../Izzi/Common';

const route = window.route;
const units = { gudang: 'Gudang', jihans: 'Jihan’s', hendhys: 'Hendhys', izzi: 'Izzi Kebab' };
const empty = () => ({
    product_id: '',
    to_product_id: '',
    conversion_from: '1',
    conversion_to: '1',
    quantity: '',
    notes: '',
});

function DestinationPicker({ to, initial, page, selected, source, onPick }) {
    const catalogue = usePosCatalogue(route('branch-transfers.destination-products', to.id), initial, page, to.id);
    const choices = new Map(catalogue.products.map((p) => [String(p.id), p]));
    if (selected) choices.set(String(selected.id), selected);
    if (source) choices.set(String(source.id), source);

    return (
        <div>
            <label className="mb-1 block text-xs font-semibold text-gray-500">Barang Tujuan di Cabang Penerima</label>
            <SelectField
                required
                isLoading={catalogue.loading}
                filterOption={() => true}
                onInputChange={(val, meta) => {
                    if (meta.action === 'input-change') catalogue.setQuery(val);
                }}
                value={selected?.id || ''}
                onChange={(val) => onPick(choices.get(String(val)))}
                options={[...choices.values()].map((p) => ({
                    value: p.id,
                    label: `${p.name} (${p.code})`,
                    sublabel: `Satuan tujuan: ${p.unit}`,
                }))}
                placeholder="Ketik & pilih barang tujuan…"
            />
        </div>
    );
}

function Form({
    sources,
    destinations,
    from,
    to,
    products,
    catalogue: page,
    destinationProducts,
    destinationCatalogue,
    today,
    currentTime,
}) {
    const { auth } = usePage().props;
    const sharedMaster = from.entity === 'izzi' || to?.entity === 'izzi';
    const [loadingTarget, setLoadingTarget] = useState(false);

    const sourceCatalogue = usePosCatalogue(
        route('branch-transfers.source-products', from.id),
        products,
        page,
        from.id,
    );
    const sourceBank = useRef(new Map(products.map((p) => [String(p.id), p])));
    const targetBank = useRef(new Map(destinationProducts.map((p) => [String(p.id), p])));

    for (const p of sourceCatalogue.products) sourceBank.current.set(String(p.id), p);
    for (const p of destinationProducts) targetBank.current.set(String(p.id), p);

    const form = useForm({
        request_key: uuid(),
        from_branch_id: from.id,
        to_branch_id: to?.id || '',
        date: today,
        occurred_time: currentTime,
        physical_sender_name: auth.user.name,
        physical_receiver_name: '',
        reason: '',
        notes: '',
        courier_name: '',
        items: [empty()],
        confirmed: false,
    });

    const changeItem = (index, key, value) =>
        form.setData(
            'items',
            form.data.items.map((item, i) => (i === index ? { ...item, [key]: value } : item)),
        );

    const chooseSource = (index, id) => {
        const p = sourceBank.current.get(String(id));
        const reuse = p && to;
        if (reuse) targetBank.current.set(String(p.id), p);
        form.setData(
            'items',
            form.data.items.map((r, i) =>
                i === index
                    ? { ...empty(), notes: r.notes, product_id: id, to_product_id: reuse ? p.id : '' }
                    : r,
            ),
        );
    };

    const chooseTarget = (index, p, source) => {
        if (p) targetBank.current.set(String(p.id), p);
        form.setData(
            'items',
            form.data.items.map((r, i) =>
                i === index
                    ? {
                          ...r,
                          to_product_id: p?.id || '',
                          conversion_from: '1',
                          conversion_to: p && Number(p.unit_id) !== Number(source?.unit_id) ? '' : '1',
                      }
                    : r,
            ),
        );
    };

    const changeDestination = (id) => {
        const destination = destinations.find((b) => String(b.id) === String(id));
        const shared = from.entity === 'izzi' || destination?.entity === 'izzi';
        form.setData((d) => ({
            ...d,
            to_branch_id: id,
            items: d.items.map((r) => ({
                ...r,
                to_product_id: shared ? r.product_id : '',
                conversion_from: '1',
                conversion_to: '1',
            })),
        }));
        router.get(
            route('branch-transfers.create'),
            { from_branch_id: from.id, to_branch_id: id || undefined },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setLoadingTarget(true),
                onFinish: () => setLoadingTarget(false),
            },
        );
    };

    const blocked = form.data.items.some(
        (i) => i.product_id && !sourceBank.current.get(String(i.product_id))?.unit_matches,
    );
    const choices = new Map(sourceCatalogue.products.map((p) => [String(p.id), p]));
    for (const i of form.data.items) {
        if (sourceBank.current.has(String(i.product_id))) {
            choices.set(String(i.product_id), sourceBank.current.get(String(i.product_id)));
        }
    }

    return (
        <form
            className="space-y-6"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(route('branch-transfers.store'));
            }}
        >
            <Errors errors={form.errors} />

            {/* Card 1: Informasi Asal, Tujuan & Petugas */}
            <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 className="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">
                    1. Lokasi Asal, Tujuan & Petugas
                </h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Lokasi Pengirim (Asal)
                        <select
                            className={`${input} mt-1.5`}
                            value={from.id}
                            disabled={form.processing}
                            onChange={(e) =>
                                router.get(route('branch-transfers.create'), { from_branch_id: e.target.value })
                            }
                        >
                            {sources.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {units[b.entity]} · {b.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Lokasi Penerima (Tujuan)
                        <select
                            required
                            className={`${input} mt-1.5`}
                            value={form.data.to_branch_id}
                            disabled={form.processing || loadingTarget}
                            onChange={(e) => changeDestination(e.target.value)}
                        >
                            <option value="">— Pilih Lokasi Tujuan —</option>
                            {destinations.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {units[b.entity]} · {b.name}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>

                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tanggal Mutasi
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
                        Jam Diterima (WIB)
                        <input
                            required
                            type="time"
                            className={`${input} mt-1.5`}
                            value={form.data.occurred_time}
                            onChange={(e) => form.setData('occurred_time', e.target.value)}
                        />
                    </label>
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Pengirim
                        <input
                            required
                            minLength={2}
                            maxLength={255}
                            className={`${input} mt-1.5`}
                            value={form.data.physical_sender_name}
                            onChange={(e) => form.setData('physical_sender_name', e.target.value)}
                        />
                    </label>
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Penerima di Tujuan
                        <input
                            required
                            minLength={2}
                            maxLength={255}
                            placeholder="Petugas penerima"
                            className={`${input} mt-1.5`}
                            value={form.data.physical_receiver_name}
                            onChange={(e) => form.setData('physical_receiver_name', e.target.value)}
                        />
                    </label>
                </div>

                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Alasan Mutasi
                        <input
                            required
                            minLength={5}
                            maxLength={1000}
                            placeholder="Contoh: Kebutuhan stok bahan operasional cabang"
                            className={`${input} mt-1.5`}
                            value={form.data.reason}
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                    </label>
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Catatan / Pengantar (Opsional)
                        <input
                            maxLength={2000}
                            placeholder="Catatan kondisi barang / nama kurir"
                            className={`${input} mt-1.5`}
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                    </label>
                </div>
            </div>

            {/* Card 2: Daftar Barang yang Dipindahkan */}
            <div className="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <div>
                        <h3 className="font-semibold text-gray-800 dark:text-white/90">
                            2. Daftar Barang yang Dipindahkan
                        </h3>
                        <p className="text-xs text-gray-500">
                            Ketik nama atau kode barang pada kolom pilihan untuk mencari stok cabang asal
                        </p>
                    </div>
                    <button
                        type="button"
                        disabled={form.data.items.length >= 100 || form.processing}
                        onClick={() => form.setData('items', [...form.data.items, empty()])}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3.5 py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400"
                    >
                        <Icon name="add" className="text-[16px]" />
                        <span>Tambah Baris Barang</span>
                    </button>
                </div>

                <div className="divide-y divide-gray-100 p-6 dark:divide-gray-800">
                    {products.length === 0 && (
                        <p className="mb-4 rounded-xl bg-rose-50 p-3 text-xs font-medium text-rose-700">
                            Belum ada stok siap jual di lokasi asal ({from.name}).
                        </p>
                    )}

                    {form.data.items.map((item, index) => {
                        const product = sourceBank.current.get(String(item.product_id));
                        const target = sharedMaster ? product : targetBank.current.get(String(item.to_product_id));
                        const amount =
                            (Number(item.quantity || 0) * Number(item.conversion_to || 0)) /
                            Number(item.conversion_from || 1);
                        const differing = target && product && Number(target.id) !== Number(product.id);

                        return (
                            <div key={index} className="space-y-3 py-4 first:pt-0 last:pb-0">
                                <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-12">
                                    <div className="lg:col-span-5">
                                        <label className="mb-1 block text-xs font-semibold text-gray-500">
                                            Produk Asal #{index + 1}
                                        </label>
                                        <SelectField
                                            required
                                            isLoading={sourceCatalogue.loading}
                                            filterOption={() => true}
                                            onInputChange={(val, meta) => {
                                                if (meta.action === 'input-change') sourceCatalogue.setQuery(val);
                                            }}
                                            value={item.product_id}
                                            onChange={(val) => chooseSource(index, val)}
                                            options={[...choices.values()].map((p) => ({
                                                value: p.id,
                                                label: `${p.name} (${p.code})`,
                                                sublabel: `Tersedia: ${p.quantity} ${p.stock_unit ?? p.unit}`,
                                                isDisabled:
                                                    !p.unit_matches ||
                                                    form.data.items.some(
                                                        (r, i) => i !== index && String(r.product_id) === String(p.id),
                                                    ),
                                            }))}
                                            placeholder="Ketik & pilih barang dari stok asal…"
                                        />
                                    </div>

                                    <div className="lg:col-span-3">
                                        <label className="mb-1 block text-xs font-semibold text-gray-500">
                                            Jumlah ({product?.unit || 'Satuan'})
                                        </label>
                                        <input
                                            required
                                            type="number"
                                            min={from.entity === 'izzi' ? '0.001' : '1'}
                                            step={from.entity === 'izzi' ? '0.001' : '1'}
                                            max={product?.quantity ?? 999999999}
                                            disabled={!product?.unit_matches}
                                            placeholder="0"
                                            className={`${input} font-bold`}
                                            value={item.quantity}
                                            onChange={(e) => changeItem(index, 'quantity', e.target.value)}
                                        />
                                        {product?.unit_matches && (
                                            <span className="mt-1 block text-[11px] text-gray-500">
                                                Sisa asal:{' '}
                                                <strong>
                                                    {(
                                                        Number(product.quantity) - Number(item.quantity || 0)
                                                    ).toLocaleString('id-ID', { maximumFractionDigits: 3 })}{' '}
                                                    {product.unit}
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
                                            placeholder="Keterangan…"
                                            className={input}
                                            value={item.notes}
                                            onChange={(e) => changeItem(index, 'notes', e.target.value)}
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

                                {product && to && !loadingTarget && !sharedMaster && (
                                    <div className="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/40">
                                        <DestinationPicker
                                            key={`${to.id}-${index}`}
                                            to={to}
                                            initial={destinationProducts}
                                            page={destinationCatalogue}
                                            selected={target}
                                            source={product}
                                            onPick={(p) => chooseTarget(index, p, product)}
                                        />
                                        {differing && (
                                            <div className="mt-3 grid grid-cols-2 gap-3">
                                                <label className="text-xs font-medium">
                                                    Acuan Asal ({product.unit})
                                                    <input
                                                        required
                                                        type="number"
                                                        min="0.001"
                                                        step="0.001"
                                                        className={`${input} mt-1`}
                                                        value={item.conversion_from}
                                                        onChange={(e) =>
                                                            changeItem(index, 'conversion_from', e.target.value)
                                                        }
                                                    />
                                                </label>
                                                <label className="text-xs font-medium">
                                                    Setara Tujuan ({target.unit})
                                                    <input
                                                        required
                                                        type="number"
                                                        min="0.001"
                                                        step="0.001"
                                                        className={`${input} mt-1`}
                                                        value={item.conversion_to}
                                                        onChange={(e) =>
                                                            changeItem(index, 'conversion_to', e.target.value)
                                                        }
                                                    />
                                                </label>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {target && Number(item.quantity) > 0 && (
                                    <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        Tujuan menerima:{' '}
                                        <strong>
                                            +{amount.toLocaleString('id-ID', { maximumFractionDigits: 3 })}{' '}
                                            {target.unit} ({target.name})
                                        </strong>
                                    </p>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Footer Confirmation */}
                <div className="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.01]">
                    <label className="flex cursor-pointer items-center gap-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={form.data.confirmed}
                            onChange={(e) => form.setData('confirmed', e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                        />
                        <span>
                            Barang sudah diterima fisik dan jumlah beserta lokasinya sudah saya periksa dengan benar.
                        </span>
                    </label>

                    <button
                        type="submit"
                        disabled={
                            form.processing ||
                            loadingTarget ||
                            !form.data.confirmed ||
                            !form.data.to_branch_id ||
                            blocked
                        }
                        className="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-40"
                    >
                        <Icon name="check_circle" className="text-[18px]" />
                        <span>{form.processing ? 'Menyimpan…' : 'Simpan Mutasi Selesai'}</span>
                    </button>
                </div>
            </div>
        </form>
    );
}

export default function Create(props) {
    return (
        <Layout pageTitle="Catat Mutasi Cabang & Gudang">
            <Head title="Catat Mutasi Baru" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href={route('branch-transfers.index')}
                            className="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            <Icon name="arrow_back" className="text-[15px]" />
                            <span>Kembali ke Daftar Mutasi</span>
                        </Link>
                        <h2 className="text-xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                            Catat Mutasi Stok Baru
                        </h2>
                    </div>
                </div>

                <Form key={props.from.id} {...props} />
            </div>
        </Layout>
    );
}
