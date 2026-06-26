import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import JihansLayout from '@/Layouts/JihansLayout';
import Icon from '@/Components/Icon';
import { formatRupiah } from '@/lib/format';
import SearchModal from './SearchModal';
import PaymentModal from './PaymentModal';

const route = window.route;
const axios = window.axios;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const todayISO = () => new Date().toISOString().slice(0, 10);

export default function PosIndex({ products, customers }) {
    const [cart, setCart] = useState([]);
    const [customerId, setCustomerId] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerType, setCustomerType] = useState('Pelanggan Retail');
    const [date, setDate] = useState(todayISO());
    const [notes, setNotes] = useState('');
    const [extraDiscount, setExtraDiscount] = useState(0);
    const [ppnType, setPpnType] = useState('none');
    const [showSearch, setShowSearch] = useState(false);
    const [showPayment, setShowPayment] = useState(false);
    const [amountPaid, setAmountPaid] = useState(0);
    const [processing, setProcessing] = useState(false);

    const recalculateCart = (currentCart, type = customerType) => {
        // 1. Calculate combined meat & tortilla quantity first
        const combinedQty = currentCart.reduce((sum, item) => {
            const product = products.find(p => p.id === item.product_id);
            if (product) {
                const catName = (product.category_name || '').toLowerCase();
                const prodName = (product.name || '').toLowerCase();
                const isMeatOrTortilla = catName === 'daging' || catName === 'tortilla' ||
                                         prodName.includes('daging') || prodName.includes('tortilla');
                if (isMeatOrTortilla) {
                    return sum + item.quantity;
                }
            }
            return sum;
        }, 0);

        // 2. Map items with recalculated prices
        return currentCart.map(item => {
            const product = products.find(p => p.id === item.product_id);
            if (!product) return item;

            // Determine effective quantity for pricing
            let effectiveQty = item.quantity;
            if (type === 'Reseller') {
                effectiveQty = Math.max(effectiveQty, 50);
            } else if (type === 'Agen') {
                effectiveQty = Math.max(effectiveQty, 300);
            }

            const catName = (product.category_name || '').toLowerCase();
            const prodName = (product.name || '').toLowerCase();
            const isMeatOrTortilla = catName === 'daging' || catName === 'tortilla' ||
                                     prodName.includes('daging') || prodName.includes('tortilla');
            
            if (isMeatOrTortilla && combinedQty >= 50) {
                effectiveQty = Math.max(effectiveQty, combinedQty);
            }

            // Calculate tiered price for this effective quantity
            let price = product.selling_price || 0;
            const sortedTiers = [...(product.tiered_prices ?? [])].sort((a, b) => b.min_qty - a.min_qty);
            for (const tier of sortedTiers) {
                if (effectiveQty >= tier.min_qty) {
                    price = tier.price;
                    break;
                }
            }

            return { ...item, price };
        });
    };

    useEffect(() => {
        const raw = localStorage.getItem('jihans_resume_cart');
        if (!raw) return;
        try {
            const d = JSON.parse(raw);
            const type = d.customerType ?? 'Pelanggan Retail';
            setCustomerType(type);
            setCustomerId(d.customerId ?? '');
            setCustomerName(d.customerName ?? '');
            setNotes(d.notes ?? '');
            setCart(recalculateCart(d.items ?? [], type));
        } catch { /* ignore */ }
        localStorage.removeItem('jihans_resume_cart');
    }, []);

    const totals = useMemo(() => {
        let subtotal = 0; let itemDiscount = 0;
        for (const it of cart) {
            const total = it.quantity * it.price - (Number(it.discount) || 0);
            subtotal += total;
            itemDiscount += Number(it.discount) || 0;
        }
        const afterDiscount = subtotal - (Number(extraDiscount) || 0);
        const tax = ppnType === 'exclude' ? afterDiscount * 0.11 : 0;
        const grand = Math.max(0, afterDiscount + tax);
        return { subtotal, itemDiscount, tax, grand };
    }, [cart, extraDiscount, ppnType]);

    const lineTotal = (it) => it.quantity * it.price - (Number(it.discount) || 0);

    const addItem = (input) => {
        const itemsToAdd = Array.isArray(input) ? input : [input];
        const validItems = itemsToAdd.filter(p => p.current_stock > 0);
        if (validItems.length === 0) return;

        setCart((prev) => {
            let next = [...prev];
            for (const product of validItems) {
                const idx = next.findIndex((i) => i.product_id === product.id);
                if (idx > -1) {
                    next[idx] = { ...next[idx], quantity: next[idx].quantity + 1 };
                } else {
                    next.push({
                        product_id: product.id,
                        product_name: product.name,
                        product_code: product.code,
                        barcode: product.barcode,
                        price: product.selling_price || 0,
                        quantity: 1,
                        discount: 0,
                        unit_name: product.unit,
                        max_stock: product.current_stock,
                    });
                }
            }
            return recalculateCart(next);
        });
        setShowSearch(false);
    };

    const updateItem = (index, patch) => {
        setCart((prev) => {
            const updated = prev.map((it, i) => {
                if (i !== index) return it;
                let next = { ...it, ...patch };
                if (patch.quantity !== undefined) {
                    next.quantity = Math.max(1, Math.round(Number(patch.quantity) || 1));
                }
                if ((Number(next.discount) || 0) > next.price * next.quantity) next.discount = 0;
                return next;
            });
            return recalculateCart(updated);
        });
    };

    const removeItem = (index) => setCart((prev) => {
        const next = prev.filter((_, i) => i !== index);
        return recalculateCart(next);
    });

    const onCustomerChange = (id) => {
        setCustomerId(id);
        const c = customers.find((x) => String(x.id) === String(id));
        const type = c ? c.type : 'Pelanggan Retail';
        setCustomerType(type);
        setCustomerName(c ? c.name : '');
        setCart((prev) => recalculateCart(prev, type));
    };

    const openPayment = () => {
        if (cart.length === 0) return;
        setAmountPaid(totals.grand);
        setShowPayment(true);
    };

    const buildItems = () => cart.map((i) => ({ product_id: i.product_id, quantity: i.quantity, price: i.price, discount: Number(i.discount) || 0, total: lineTotal(i) }));

    const processTransaction = async () => {
        if (amountPaid < totals.grand) { alert('Uang pembayaran kurang dari total tagihan.'); return; }
        setProcessing(true);
        try {
            const { data } = await axios.post(route('jihans.pos.store'), {
                transaction_date: date, customer_id: customerId || null, customer_name: customerName, customer_type: customerType,
                ppn_type: ppnType, ppn_rate: 11, subtotal: totals.subtotal, discount_amount: totals.itemDiscount,
                extra_discount: Number(extraDiscount) || 0, tax_amount: totals.tax, other_costs: 0,
                grand_total: totals.grand, amount_paid: amountPaid, reference_number: null, notes,
                items: buildItems(),
            }, { headers: { 'X-CSRF-TOKEN': csrf() } });
            if (data.success) { window.location.href = data.redirect; }
        } catch (err) {
            const r = err.response?.data;
            alert(r?.error || r?.message || (r?.errors && Object.values(r.errors)[0]?.[0]) || 'Terjadi kesalahan saat memproses transaksi.');
            setProcessing(false);
            setShowPayment(false);
        }
    };

    const holdTransaction = async () => {
        if (cart.length === 0) return;
        if (!window.confirm('Simpan transaksi ini sebagai pending?')) return;
        setProcessing(true);
        try {
            const { data } = await axios.post(route('jihans.pending.store'), {
                customer_id: customerId || null, customer_name: customerName, customer_type: customerType, notes, items: buildItems(),
            }, { headers: { 'X-CSRF-TOKEN': csrf() } });
            if (data.success) {
                alert(data.message);
                setCart([]); setCustomerName(''); setNotes(''); setCustomerId('');
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Terjadi kesalahan.');
        } finally {
            setProcessing(false);
        }
    };

    useEffect(() => {
        const onKey = (e) => {
            if (showSearch || showPayment) return;
            if (e.key === 'Insert') { e.preventDefault(); setShowSearch(true); }
            else if (e.key === 'End') { e.preventDefault(); openPayment(); }
            else if (e.key === 'F5') { e.preventDefault(); holdTransaction(); }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    const inputCls = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-2 focus:ring-orange-400/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-orange-500';
    const cellInput = 'w-full bg-transparent text-right outline-none tabular-nums transition focus:rounded focus:bg-orange-50 dark:focus:bg-orange-900/20';

    return (
        <JihansLayout pageTitle="Point of Sales — Kasir">
            <Head title="POS Kasir" />

            <div className="flex flex-col gap-4">

                {/* ── Top row: info form + total display ── */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-5">

                    {/* Form info */}
                    <div className="lg:col-span-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p className="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Informasi Transaksi</p>
                        <div className="grid grid-cols-[120px_1fr] items-center gap-x-4 gap-y-3">
                            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Tanggal</label>
                            <input type="date" value={date} onChange={(e) => setDate(e.target.value)} className={inputCls} />

                            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Pelanggan</label>
                            <select value={customerId} onChange={(e) => onCustomerChange(e.target.value)} className={inputCls}>
                                <option value="">— Pelanggan Umum —</option>
                                {customers.map((c) => <option key={c.id} value={c.id}>{c.name}{c.phone ? ` | ${c.phone}` : ''}</option>)}
                            </select>

                            {!customerId && <>
                                <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Nama Manual</label>
                                <input type="text" value={customerName} onChange={(e) => setCustomerName(e.target.value)}
                                    placeholder="Ketik nama pelanggan..." className={inputCls} />
                            </>}

                            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400">Keterangan</label>
                            <input type="text" value={notes} onChange={(e) => setNotes(e.target.value)}
                                placeholder="Catatan opsional..." className={inputCls} />
                        </div>
                    </div>

                    {/* Total display */}
                    <div className="lg:col-span-2 flex flex-col items-center justify-center gap-3 rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 shadow-lg">
                        <div className="flex items-center gap-2">
                            <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                            <span className="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Total Tagihan</span>
                        </div>
                        <span className="font-mono text-5xl font-black leading-none tracking-tight text-white">
                            {formatRupiah(totals.grand)}
                        </span>
                        <div className="mt-1 flex gap-4 text-xs text-gray-400">
                            <span>{cart.length} item</span>
                            {totals.itemDiscount > 0 && <span className="text-rose-400">Disc {formatRupiah(totals.itemDiscount)}</span>}
                        </div>
                    </div>
                </div>

                {/* ── Cart table ── */}
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-5 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
                        <span className="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Keranjang Belanja</span>
                        <button onClick={() => setShowSearch(true)}
                            className="flex items-center gap-1.5 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-orange-600">
                            <Icon name="add" className="text-[16px]" /> Tambah Item
                            <span className="ml-1 rounded bg-orange-400/40 px-1 text-[9px]">Ins</span>
                        </button>
                    </div>
                    <div className="custom-scrollbar overflow-x-auto" style={{ maxHeight: '38vh' }}>
                        <table className="w-full whitespace-nowrap text-sm">
                            <thead className="sticky top-0 z-10 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                <tr>
                                    <th className="w-10 px-3 py-3 text-center">#</th>
                                    <th className="px-3 py-3 text-left">Produk</th>
                                    <th className="w-24 px-3 py-3 text-center">Jumlah</th>
                                    <th className="w-16 px-3 py-3 text-center">Satuan</th>
                                    <th className="w-32 px-3 py-3 text-right">Harga</th>
                                    <th className="w-28 px-3 py-3 text-right">Diskon</th>
                                    <th className="w-36 px-3 py-3 text-right">Subtotal</th>
                                    <th className="w-10 px-3 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {cart.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="py-16 text-center">
                                            <div className="flex flex-col items-center gap-2 text-gray-400">
                                                <Icon name="shopping_cart" className="text-[40px] text-gray-300 dark:text-gray-700" />
                                                <p className="text-sm">Keranjang masih kosong</p>
                                                <p className="text-xs">Tekan <kbd className="rounded border border-gray-300 bg-gray-100 px-1.5 py-0.5 text-[10px] font-mono">Ins</kbd> atau klik <strong>Tambah Item</strong></p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : cart.map((it, i) => (
                                    <tr key={i} className="group hover:bg-orange-50/40 dark:hover:bg-orange-900/10 transition-colors">
                                        <td className="px-3 py-2.5 text-center text-xs font-bold text-gray-400">{i + 1}</td>
                                        <td className="px-3 py-2.5">
                                            <p className="font-semibold text-gray-800 dark:text-white">{it.product_name}</p>
                                            <p className="font-mono text-[10px] text-gray-400">{it.product_code}</p>
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <input type="number" min="1" value={it.quantity}
                                                onChange={(e) => updateItem(i, { quantity: e.target.value })}
                                                className="w-full rounded border-0 bg-gray-100 py-1 text-center text-sm font-bold text-gray-800 outline-none focus:ring-2 focus:ring-orange-400 dark:bg-gray-700 dark:text-white" />
                                        </td>
                                        <td className="px-3 py-2.5 text-center text-xs font-semibold text-gray-500">{it.unit_name}</td>
                                        <td className="px-3 py-2.5">
                                            <input type="number" min="0" value={it.price}
                                                onChange={(e) => updateItem(i, { price: Number(e.target.value) || 0 })}
                                                className={`${cellInput} rounded px-1`} />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <input type="number" min="0" value={it.discount}
                                                onChange={(e) => updateItem(i, { discount: Number(e.target.value) || 0 })}
                                                className={`${cellInput} rounded px-1 text-rose-500`} />
                                        </td>
                                        <td className="px-3 py-2.5 text-right font-bold text-gray-800 tabular-nums dark:text-white">{formatRupiah(lineTotal(it))}</td>
                                        <td className="px-3 py-2.5 text-center">
                                            <button onClick={() => removeItem(i)}
                                                className="rounded p-1 text-gray-300 opacity-0 transition hover:bg-rose-50 hover:text-rose-500 group-hover:opacity-100 dark:hover:bg-rose-900/20">
                                                <Icon name="close" className="text-[16px]" />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ── Bottom: summary + actions ── */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">

                    {/* Summary */}
                    <div className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p className="mb-3 text-[10px] font-bold uppercase tracking-widest text-gray-400">Rincian Pembayaran</p>
                        <div className="space-y-2.5 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Sub Total</span>
                                <span className="font-semibold tabular-nums">{formatRupiah(totals.subtotal)}</span>
                            </div>
                            {totals.itemDiscount > 0 && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Diskon Item</span>
                                    <span className="font-semibold text-rose-500 tabular-nums">-{formatRupiah(totals.itemDiscount)}</span>
                                </div>
                            )}
                            <div className="flex items-center justify-between">
                                <span className="text-gray-500">Pot. Tambahan</span>
                                <input type="number" min="0" value={extraDiscount}
                                    onChange={(e) => setExtraDiscount(Number(e.target.value) || 0)}
                                    className="w-32 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-right text-sm font-semibold text-rose-500 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/20 dark:border-gray-700 dark:bg-gray-800" />
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-gray-500">PPN</span>
                                <select value={ppnType} onChange={(e) => setPpnType(e.target.value)}
                                    className="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-sm outline-none focus:border-orange-400 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="none">Tanpa PPN</option>
                                    <option value="include">Include</option>
                                    <option value="exclude">Exclude (+11%)</option>
                                </select>
                            </div>
                            {ppnType === 'exclude' && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Nilai PPN</span>
                                    <span className="tabular-nums">{formatRupiah(totals.tax)}</span>
                                </div>
                            )}
                            <div className="mt-1 flex justify-between rounded-xl bg-orange-50 px-3 py-2.5 dark:bg-orange-900/20">
                                <span className="font-bold text-gray-700 dark:text-gray-200">Total Akhir</span>
                                <span className="text-lg font-black text-orange-600 tabular-nums dark:text-orange-400">{formatRupiah(totals.grand)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Action buttons */}
                    <div className="col-span-2 flex flex-wrap items-stretch justify-end gap-3">
                        <button onClick={() => setShowSearch(true)}
                            className="flex flex-1 min-w-[120px] flex-col items-center justify-center gap-1 rounded-2xl border-2 border-gray-200 bg-white py-4 font-bold text-gray-700 shadow-sm transition hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-orange-600 dark:hover:text-orange-400">
                            <Icon name="add_shopping_cart" className="text-[28px]" />
                            <span className="text-sm">Tambah</span>
                            <kbd className="rounded bg-gray-100 px-1.5 py-0.5 text-[9px] font-mono text-gray-400 dark:bg-gray-800">Ins</kbd>
                        </button>

                        <button onClick={holdTransaction} disabled={cart.length === 0}
                            className="flex flex-1 min-w-[120px] flex-col items-center justify-center gap-1 rounded-2xl border-2 border-blue-200 bg-blue-50 py-4 font-bold text-blue-600 shadow-sm transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            <Icon name="pause_circle" className="text-[28px]" />
                            <span className="text-sm">Pending</span>
                            <kbd className="rounded bg-blue-100 px-1.5 py-0.5 text-[9px] font-mono text-blue-400 dark:bg-blue-900">F5</kbd>
                        </button>

                        <Link href={route('jihans.pending.index')}
                            className="flex flex-1 min-w-[120px] flex-col items-center justify-center gap-1 rounded-2xl border-2 border-orange-200 bg-orange-50 py-4 text-center font-bold text-orange-600 shadow-sm transition hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                            <Icon name="list_alt" className="text-[28px]" />
                            <span className="text-sm">Daftar Pending</span>
                        </Link>

                        <button onClick={openPayment} disabled={cart.length === 0}
                            className="flex flex-1 min-w-[160px] flex-col items-center justify-center gap-1 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 py-4 font-bold text-white shadow-lg shadow-emerald-500/30 transition hover:from-emerald-600 hover:to-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">
                            <Icon name="payments" className="text-[32px]" />
                            <span className="text-lg font-black">BAYAR</span>
                            <kbd className="rounded bg-emerald-400/30 px-1.5 py-0.5 text-[9px] font-mono text-emerald-100">End</kbd>
                        </button>
                    </div>
                </div>
            </div>

            {showSearch && <SearchModal products={products} onAdd={addItem} onClose={() => setShowSearch(false)} />}
            {showPayment && <PaymentModal grandTotal={totals.grand} amountPaid={amountPaid} setAmountPaid={setAmountPaid} onClose={() => setShowPayment(false)} onProcess={processTransaction} processing={processing} />}
        </JihansLayout>
    );
}
