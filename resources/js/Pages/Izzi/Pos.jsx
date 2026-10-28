import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import IzziLayout from '@/Layouts/IzziLayout';
import Icon from '@/Components/Icon';
import Modal from '@/Components/Modal';
import usePosCatalogue from '@/hooks/usePosCatalogue';
import { Errors, money, uuid } from './Common';

const route = window.route;

export default function Pos(props) {
    const catalogue = usePosCatalogue(route('izzi.pos'), props.products, props.catalogue, props.branch.id);
    const [cart, setCart] = useState([]);
    const [selectedCategory, setSelectedCategory] = useState('ALL');
    const [showPaymentModal, setShowPaymentModal] = useState(false);

    const form = useForm({
        checkout_key: uuid(),
        branch_id: props.branch.id,
        customer_name: '',
        payment_type: 'cash',
        amount_paid: '',
        payment_reference: '',
        items: [],
    });

    const total = cart.reduce((n, i) => n + i.price * i.quantity, 0);
    const totalQty = cart.reduce((n, i) => n + Number(i.quantity || 0), 0);

    const categories = useMemo(() => {
        const set = new Set();
        catalogue.products.forEach((p) => {
            if (p.category) set.add(p.category);
        });
        return ['ALL', ...Array.from(set)];
    }, [catalogue.products]);

    const filteredProducts = useMemo(() => {
        if (selectedCategory === 'ALL') return catalogue.products;
        return catalogue.products.filter((p) => p.category === selectedCategory);
    }, [catalogue.products, selectedCategory]);

    const add = (p) =>
        setCart((items) => {
            const old = items.find((i) => i.id === p.id);
            return old
                ? items.map((i) =>
                      i.id === p.id ? { ...i, quantity: Math.min(i.quantity + 1, i.available, 1000) } : i,
                  )
                : [...items, { ...p, quantity: 1 }];
        });

    const update = (id, value) =>
        setCart((items) =>
            items.map((i) =>
                i.id === id
                    ? { ...i, quantity: Math.max(1, Math.min(Number(value) || 1, i.available, 1000)) }
                    : i,
            ),
        );

    const paid = form.data.payment_type === 'transfer' ? total : Number(form.data.amount_paid);
    const change = Math.max(0, paid - total);

    const openPayment = () => {
        if (!cart.length) return;
        if (!form.data.amount_paid || Number(form.data.amount_paid) < total) {
            form.setData('amount_paid', String(total));
        }
        setShowPaymentModal(true);
    };

    const submitCheckout = (e) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            amount_paid: paid,
            items: cart.map((i) => ({ menu_id: i.id, quantity: i.quantity, price: i.price })),
        }));
        form.post(route('izzi.checkout'), {
            onSuccess: () => {
                setCart([]);
                setShowPaymentModal(false);
                form.setData({
                    checkout_key: uuid(),
                    branch_id: props.branch.id,
                    customer_name: '',
                    payment_type: 'cash',
                    amount_paid: '',
                    payment_reference: '',
                    items: [],
                });
            },
        });
    };

    return (
        <IzziLayout pageTitle="Point of Sales — Kasir Izzi">
            <Head title="POS Kasir Izzi" />

            <div className="space-y-5">
                {/* Top Header */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800 dark:text-white/90">
                            Kasir Point of Sales — {props.branch.name}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Pilih menu di sebelah kiri, tinjau pesanan di keranjang kanan, lalu klik Bayar.
                        </p>
                    </div>
                    <div className="flex items-center gap-2.5">
                        <Link
                            href={route('izzi.shifts', { branch_id: props.branch.id })}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <Icon name="account_balance_wallet" className="text-[18px] text-brand-500" />
                            <span>Laci Kasir</span>
                        </Link>
                        <Link
                            href={route('izzi.sales', { branch_id: props.branch.id })}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <Icon name="receipt_long" className="text-[18px] text-blue-500" />
                            <span>Riwayat Struk</span>
                        </Link>
                    </div>
                </div>

                <Errors errors={form.errors} />

                {/* Main Split Layout: Left = Menu Catalogue (8 cols), Right = Cart & Total (4 cols) */}
                <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                    {/* LEFT: Daftar Menu */}
                    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs lg:col-span-8 dark:border-gray-800 dark:bg-gray-900">
                        {/* Search & Category Bar */}
                        <div className="space-y-3 border-b border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div className="relative">
                                <Icon
                                    name="search"
                                    className="absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-gray-400"
                                />
                                <input
                                    type="search"
                                    aria-label="Cari menu"
                                    placeholder="Cari nama atau kode menu…"
                                    className="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    value={catalogue.query}
                                    onChange={(e) => catalogue.setQuery(e.target.value)}
                                />
                            </div>

                            {categories.length > 2 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {categories.map((cat) => (
                                        <button
                                            key={cat}
                                            type="button"
                                            onClick={() => setSelectedCategory(cat)}
                                            className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                                                selectedCategory === cat
                                                    ? 'bg-brand-500 text-white shadow-xs'
                                                    : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300'
                                            }`}
                                        >
                                            {cat === 'ALL' ? 'Semua Kategori' : cat}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Menu Grid */}
                        <div className="p-4">
                            {catalogue.loading && (
                                <div className="py-12 text-center text-sm text-gray-500">Memuat daftar menu…</div>
                            )}
                            {catalogue.error && (
                                <div className="rounded-xl bg-red-50 p-4 text-sm text-red-600">{catalogue.error}</div>
                            )}
                            {!catalogue.loading && !catalogue.error && !filteredProducts.length && (
                                <div className="py-14 text-center">
                                    <Icon name="restaurant_menu" className="mx-auto text-[40px] text-gray-300 dark:text-gray-700" />
                                    <p className="mt-2 font-semibold text-gray-700 dark:text-gray-300">Menu Tidak Ditemukan</p>
                                    <p className="mt-1 text-xs text-gray-500">
                                        Pastikan menu sudah diaktifkan dan stok bahan baku tersedia di cabang ini.
                                    </p>
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-3">
                                {filteredProducts.map((p) => {
                                    const inCart = cart.find((c) => c.id === p.id);
                                    const outOfStock = !p.available || p.available <= 0;
                                    return (
                                        <button
                                            disabled={outOfStock || form.processing}
                                            type="button"
                                            key={p.id}
                                            onClick={() => add(p)}
                                            className={`group relative flex flex-col justify-between rounded-2xl border p-4 text-left transition ${
                                                inCart
                                                    ? 'border-brand-500 bg-brand-50/40 ring-1 ring-brand-500 dark:bg-brand-500/10'
                                                    : 'border-gray-200 bg-white hover:border-brand-400 hover:shadow-md dark:border-gray-800 dark:bg-gray-800/60'
                                            } disabled:cursor-not-allowed disabled:opacity-45`}
                                        >
                                            <div>
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {p.category || 'Menu'}
                                                    </span>
                                                    {inCart ? (
                                                        <span className="rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-bold text-white">
                                                            {inCart.quantity}× dipilih
                                                        </span>
                                                    ) : (
                                                        <span
                                                            className={`text-[11px] font-semibold ${
                                                                outOfStock
                                                                    ? 'text-rose-600 dark:text-rose-400'
                                                                    : 'text-emerald-600 dark:text-emerald-400'
                                                            }`}
                                                        >
                                                            {outOfStock ? 'Habis' : `Stok ~${p.available}`}
                                                        </span>
                                                    )}
                                                </div>

                                                <h3 className="mt-2.5 font-bold text-gray-900 dark:text-white">{p.name}</h3>
                                                <p className="font-mono text-[11px] text-gray-400">{p.code}</p>
                                            </div>

                                            <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700/60">
                                                <span className="text-base font-extrabold text-brand-600 dark:text-brand-400">
                                                    {money(p.price)}
                                                </span>
                                                <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-500 group-hover:text-white dark:bg-brand-500/15 dark:text-brand-400">
                                                    <Icon name="add" className="text-[18px]" />
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Pagination Footer (Only if multiple pages) */}
                        {(catalogue.pagination.page > 1 || catalogue.pagination.has_more) && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-4 py-3 text-xs dark:border-gray-800">
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-3 py-1.5 font-semibold disabled:opacity-40 dark:border-gray-700"
                                    disabled={catalogue.loading || catalogue.pagination.page <= 1}
                                    onClick={() => catalogue.setPage(catalogue.pagination.page - 1)}
                                >
                                    ← Sebelumnya
                                </button>
                                <span className="text-gray-500">Halaman {catalogue.pagination.page}</span>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-3 py-1.5 font-semibold disabled:opacity-40 dark:border-gray-700"
                                    disabled={catalogue.loading || !catalogue.pagination.has_more}
                                    onClick={() => catalogue.setPage(catalogue.pagination.page + 1)}
                                >
                                    Berikutnya →
                                </button>
                            </div>
                        )}
                    </div>

                    {/* RIGHT: Total Display + Cart */}
                    <div className="space-y-4 lg:col-span-4">
                        {/* Total Tagihan Display (Same sleek dark card as Jihan's POS) */}
                        <div className="flex flex-col items-center justify-center gap-2 rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 shadow-lg">
                            <div className="flex items-center gap-2">
                                <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                                <span className="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">
                                    Total Tagihan
                                </span>
                            </div>
                            <span className="font-mono text-4xl font-black tracking-tight text-white">
                                {money(total)}
                            </span>
                            <span className="text-xs text-gray-400">
                                {cart.length} jenis menu • {totalQty} porsi
                            </span>
                        </div>

                        {/* Cart Card */}
                        <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                            <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
                                <span className="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Keranjang Pesanan
                                </span>
                                {cart.length > 0 && (
                                    <button
                                        type="button"
                                        disabled={form.processing}
                                        onClick={() => setCart([])}
                                        className="text-xs font-semibold text-rose-600 hover:underline"
                                    >
                                        Kosongkan
                                    </button>
                                )}
                            </div>

                            {!cart.length ? (
                                <div className="px-4 py-12 text-center">
                                    <Icon
                                        name="shopping_cart"
                                        className="mx-auto text-[36px] text-gray-300 dark:text-gray-700"
                                    />
                                    <p className="mt-2 text-sm font-medium text-gray-500">Keranjang masih kosong</p>
                                    <p className="mt-0.5 text-xs text-gray-400">
                                        Klik menu di sebelah kiri untuk menambahkan pesanan
                                    </p>
                                </div>
                            ) : (
                                <div className="max-h-[42vh] divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
                                    {cart.map((i) => (
                                        <div key={i.id} className="space-y-2 p-3.5">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="text-sm font-bold text-gray-800 dark:text-white">
                                                        {i.name}
                                                    </p>
                                                    <p className="text-xs text-gray-400">{money(i.price)} / porsi</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => setCart((items) => items.filter((p) => p.id !== i.id))}
                                                    className="rounded p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30"
                                                >
                                                    <Icon name="close" className="text-[16px]" />
                                                </button>
                                            </div>

                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        disabled={i.quantity <= 1}
                                                        onClick={() => update(i.id, i.quantity - 1)}
                                                        className="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 font-bold text-gray-700 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                    >
                                                        −
                                                    </button>
                                                    <input
                                                        aria-label={`Jumlah ${i.name}`}
                                                        type="number"
                                                        min="1"
                                                        max={Math.min(i.available, 1000)}
                                                        value={i.quantity}
                                                        onChange={(e) => update(i.id, e.target.value)}
                                                        className="w-14 rounded-lg border border-gray-200 bg-white py-1 text-center text-sm font-bold text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                    />
                                                    <button
                                                        type="button"
                                                        disabled={i.quantity >= Math.min(i.available, 1000)}
                                                        onClick={() => update(i.id, i.quantity + 1)}
                                                        className="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 font-bold text-gray-700 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                                <span className="text-sm font-bold text-gray-900 tabular-nums dark:text-white">
                                                    {money(i.price * i.quantity)}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="space-y-3 border-t border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                                <input
                                    type="text"
                                    maxLength="150"
                                    placeholder="Nama pelanggan / meja (opsional)…"
                                    value={form.data.customer_name}
                                    onChange={(e) => form.setData('customer_name', e.target.value)}
                                    className="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />

                                <button
                                    type="button"
                                    disabled={!cart.length || form.processing}
                                    onClick={openPayment}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 py-3.5 text-base font-black text-white shadow-md shadow-emerald-500/20 transition hover:from-emerald-600 hover:to-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <Icon name="payments" className="text-[22px]" />
                                    <span>BAYAR SEKARANG</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal Pembayaran (Separated cleanly from the main POS screen) */}
            <Modal show={showPaymentModal} onClose={() => setShowPaymentModal(false)} maxWidth="md">
                <form onSubmit={submitCheckout} className="p-6">
                    <div className="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">Penyelesaian Pembayaran</h3>
                            <p className="text-xs text-gray-500">
                                {cart.length} menu ({totalQty} porsi)
                                {form.data.customer_name ? ` • Pelanggan: ${form.data.customer_name}` : ''}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowPaymentModal(false)}
                            className="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                        >
                            <Icon name="close" className="text-[20px]" />
                        </button>
                    </div>

                    <div className="my-4 rounded-2xl bg-gray-900 p-4 text-center text-white">
                        <span className="text-xs font-semibold uppercase tracking-widest text-emerald-400">
                            Total Tagihan
                        </span>
                        <p className="mt-1 font-mono text-3xl font-black">{money(total)}</p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500">
                                Metode Pembayaran
                            </label>
                            <div className="grid grid-cols-2 gap-2.5">
                                {[
                                    ['cash', 'Tunai (Laci Kasir)', 'payments'],
                                    ['transfer', 'Transfer / QRIS', 'qr_code_2'],
                                ].map(([val, label, ic]) => (
                                    <button
                                        key={val}
                                        type="button"
                                        onClick={() => form.setData('payment_type', val)}
                                        className={`flex items-center justify-center gap-2 rounded-xl border p-3 text-sm font-bold transition ${
                                            form.data.payment_type === val
                                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300'
                                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                        }`}
                                    >
                                        <Icon name={ic} className="text-[18px]" />
                                        <span>{label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {form.data.payment_type === 'cash' ? (
                            <div className="space-y-3">
                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Uang Tunai Diterima (Rp)
                                    </label>
                                    <input
                                        required
                                        type="number"
                                        min={total}
                                        max="1000000000000"
                                        step="1"
                                        value={form.data.amount_paid}
                                        onChange={(e) => form.setData('amount_paid', e.target.value)}
                                        className="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-lg font-bold text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="flex flex-wrap gap-1.5">
                                    {[
                                        ['Uang Pas', total],
                                        ['Rp 20.000', 20000],
                                        ['Rp 50.000', 50000],
                                        ['Rp 100.000', 100000],
                                    ]
                                        .filter(([, val]) => val >= total)
                                        .map(([label, val]) => (
                                            <button
                                                key={label}
                                                type="button"
                                                onClick={() => form.setData('amount_paid', String(val))}
                                                className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                {label}
                                            </button>
                                        ))}
                                </div>

                                <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                    <span className="text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                        Uang Kembalian
                                    </span>
                                    <strong className="text-xl font-black text-emerald-700 dark:text-emerald-300">
                                        {money(change)}
                                    </strong>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Keterangan / Referensi Transfer (Opsional)
                                </label>
                                <input
                                    maxLength="255"
                                    placeholder="Contoh: QRIS / Transfer BCA"
                                    value={form.data.payment_reference}
                                    onChange={(e) => form.setData('payment_reference', e.target.value)}
                                    className="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                        )}
                    </div>

                    <div className="mt-6 flex items-center justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            onClick={() => setShowPaymentModal(false)}
                            className="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300"
                        >
                            Kembali
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing || !cart.length || paid < total}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-40"
                        >
                            <Icon name="check_circle" className="text-[18px]" />
                            <span>{form.processing ? 'Memproses…' : 'Selesaikan Transaksi'}</span>
                        </button>
                    </div>
                </form>
            </Modal>
        </IzziLayout>
    );
}
