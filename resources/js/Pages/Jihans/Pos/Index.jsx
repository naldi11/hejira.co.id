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

function tieredPrice(product, qty) {
    let price = product.selling_price || 0;
    for (const tier of product.tiered_prices ?? []) { // sudah DESC
        if (qty >= tier.min_qty) { price = tier.price; break; }
    }
    return price;
}

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

    // Resume a held transaction (set in localStorage by the Pending list page).
    useEffect(() => {
        const raw = localStorage.getItem('jihans_resume_cart');
        if (!raw) return;
        try {
            const d = JSON.parse(raw);
            setCart(d.items ?? []);
            setCustomerType(d.customerType ?? 'Pelanggan Retail');
            setCustomerId(d.customerId ?? '');
            setCustomerName(d.customerName ?? '');
            setNotes(d.notes ?? '');
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

    const addItem = (product) => {
        if (product.current_stock <= 0) { alert('Stok produk ini masih kosong!'); return; }
        setCart((prev) => {
            const idx = prev.findIndex((i) => i.product_id === product.id);
            if (idx > -1) {
                const next = [...prev];
                const qty = next[idx].quantity + 1;
                next[idx] = { ...next[idx], quantity: qty, price: tieredPrice(product, qty) };
                return next;
            }
            return [...prev, {
                product_id: product.id, product_name: product.name, product_code: product.code, barcode: product.barcode,
                price: product.selling_price || 0, quantity: 1, discount: 0,
                unit_name: product.unit, max_stock: product.current_stock,
            }];
        });
        setShowSearch(false);
    };

    const updateItem = (index, patch) => {
        setCart((prev) => prev.map((it, i) => {
            if (i !== index) return it;
            let next = { ...it, ...patch };
            if (patch.quantity !== undefined) {
                next.quantity = Math.max(1, Math.round(Number(patch.quantity) || 1));
                const product = products.find((p) => p.id === it.product_id);
                if (product) next.price = tieredPrice(product, next.quantity);
            }
            if ((Number(next.discount) || 0) > next.price * next.quantity) next.discount = 0;
            return next;
        }));
    };

    const removeItem = (index) => setCart((prev) => prev.filter((_, i) => i !== index));

    const onCustomerChange = (id) => {
        setCustomerId(id);
        const c = customers.find((x) => String(x.id) === String(id));
        setCustomerName(c ? c.name : '');
        setCustomerType(c ? c.type : 'Pelanggan Retail');
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

    // Keyboard shortcuts (Ins = cari, End = bayar, F5 = pending).
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

    const cellInput = 'w-full bg-transparent text-right outline-none focus:bg-orange-50';

    return (
        <JihansLayout pageTitle="Point of Sales — Kasir">
            <Head title="POS Kasir" />

            <div className="flex flex-col gap-3">
                {/* Top: form + total display */}
                <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div className="grid grid-cols-[110px_1fr] items-center gap-y-2 rounded-xl border border-gray-200 bg-white p-4">
                        <label className="text-xs font-semibold text-gray-600">Tanggal</label>
                        <input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500" />
                        <label className="text-xs font-semibold text-gray-600">Pelanggan</label>
                        <select value={customerId} onChange={(e) => onCustomerChange(e.target.value)} className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">-- Pelanggan Umum / Manual --</option>
                            {customers.map((c) => <option key={c.id} value={c.id}>{c.name}{c.phone ? ` | ${c.phone}` : ''}</option>)}
                        </select>
                        {!customerId && <>
                            <label className="text-xs font-semibold text-gray-600">Nama Manual</label>
                            <input type="text" value={customerName} onChange={(e) => setCustomerName(e.target.value)} placeholder="Ketik nama pelanggan..." className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500" />
                        </>}
                        <label className="text-xs font-semibold text-gray-600">Keterangan</label>
                        <input type="text" value={notes} onChange={(e) => setNotes(e.target.value)} className="rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500" />
                    </div>
                    <div className="flex flex-col items-end justify-center rounded-xl border-4 border-gray-700 bg-black p-5">
                        <span className="mb-1 text-lg font-bold text-green-500">TOTAL</span>
                        <span className="font-mono text-5xl font-bold leading-none tracking-wider text-green-500">{formatRupiah(totals.grand)}</span>
                    </div>
                </div>

                {/* Cart table */}
                <div className="overflow-auto rounded-xl border border-gray-200 bg-white" style={{ maxHeight: '46vh' }}>
                    <table className="w-full whitespace-nowrap text-sm">
                        <thead className="sticky top-0 bg-gray-100 text-xs text-gray-600">
                            <tr>
                                <th className="w-10 px-2 py-2 text-center">No</th>
                                <th className="px-3 py-2 text-left">Item</th>
                                <th className="w-24 px-2 py-2 text-center">Jml</th>
                                <th className="w-16 px-2 py-2 text-center">Satuan</th>
                                <th className="w-28 px-2 py-2 text-right">Harga</th>
                                <th className="w-24 px-2 py-2 text-right">Potongan</th>
                                <th className="w-32 px-2 py-2 text-right">Total</th>
                                <th className="w-10 px-2 py-2" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {cart.length === 0 ? (
                                <tr><td colSpan={8} className="py-10 text-center text-gray-400">Tekan <b>[Ins]</b> atau tombol <b>Tambah</b> untuk mencari item</td></tr>
                            ) : cart.map((it, i) => (
                                <tr key={i} className="hover:bg-gray-50">
                                    <td className="px-2 py-1 text-center text-gray-500">{i + 1}</td>
                                    <td className="px-3 py-1"><span className="font-medium text-gray-800">{it.product_name}</span> <span className="font-mono text-xs text-gray-400">{it.product_code}</span></td>
                                    <td className="px-2 py-1"><input type="number" min="1" value={it.quantity} onChange={(e) => updateItem(i, { quantity: e.target.value })} className={`${cellInput} text-center`} /></td>
                                    <td className="px-2 py-1 text-center text-gray-500">{it.unit_name}</td>
                                    <td className="px-2 py-1"><input type="number" min="0" value={it.price} onChange={(e) => updateItem(i, { price: Number(e.target.value) || 0 })} className={cellInput} /></td>
                                    <td className="px-2 py-1"><input type="number" min="0" value={it.discount} onChange={(e) => updateItem(i, { discount: Number(e.target.value) || 0 })} className={cellInput} /></td>
                                    <td className="px-2 py-1 text-right font-bold text-gray-800">{formatRupiah(lineTotal(it))}</td>
                                    <td className="px-2 py-1 text-center"><button onClick={() => removeItem(i)} className="text-red-400 hover:text-red-600"><Icon name="close" className="text-[18px]" /></button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Bottom: summary + actions */}
                <div className="grid grid-cols-1 gap-3 lg:grid-cols-3">
                    <div className="grid grid-cols-[110px_1fr] items-center gap-y-1.5 rounded-xl border border-gray-200 bg-white p-3 text-sm">
                        <span className="text-xs font-semibold text-gray-600">Sub Total</span>
                        <span className="text-right font-bold">{formatRupiah(totals.subtotal)}</span>
                        <span className="text-xs font-semibold text-gray-600">Diskon Item</span>
                        <span className="text-right text-red-600">{formatRupiah(totals.itemDiscount)}</span>
                        <span className="text-xs font-semibold text-gray-600">Pot. Tambahan</span>
                        <input type="number" min="0" value={extraDiscount} onChange={(e) => setExtraDiscount(Number(e.target.value) || 0)} className="rounded border-gray-300 py-1 text-right text-sm text-red-600 focus:border-orange-500 focus:ring-orange-500" />
                        <span className="text-xs font-semibold text-gray-600">PPN</span>
                        <select value={ppnType} onChange={(e) => setPpnType(e.target.value)} className="rounded border-gray-300 py-1 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="none">Tanpa PPN</option><option value="include">Include</option><option value="exclude">Exclude (+11%)</option>
                        </select>
                        {ppnType === 'exclude' && <><span className="text-xs font-semibold text-gray-600">Nilai PPN</span><span className="text-right">{formatRupiah(totals.tax)}</span></>}
                        <span className="text-sm font-bold text-gray-700">Total Akhir</span>
                        <span className="rounded bg-yellow-100 px-2 py-1 text-right text-lg font-bold">{formatRupiah(totals.grand)}</span>
                    </div>
                    <div className="col-span-2 flex flex-wrap items-end justify-end gap-2">
                        <button onClick={() => setShowSearch(true)} className="flex h-14 min-w-[110px] flex-col items-center justify-center rounded-lg border border-gray-300 bg-white font-bold text-gray-700 shadow-sm hover:bg-gray-50">Tambah <span className="text-[10px] text-gray-400">[Ins]</span></button>
                        <button onClick={holdTransaction} disabled={cart.length === 0} className="flex h-14 min-w-[110px] flex-col items-center justify-center rounded-lg border border-gray-300 bg-white font-bold text-blue-600 shadow-sm hover:bg-gray-50 disabled:opacity-40">Pending <span className="text-[10px] text-gray-400">[F5]</span></button>
                        <Link href={route('jihans.pending.index')} className="flex h-14 min-w-[110px] flex-col items-center justify-center rounded-lg border border-gray-300 bg-white text-center font-bold text-orange-600 shadow-sm hover:bg-gray-50">Daftar Pending</Link>
                        <button onClick={openPayment} disabled={cart.length === 0} className="flex h-14 min-w-[140px] flex-col items-center justify-center rounded-lg bg-green-600 text-lg font-bold text-white shadow-lg hover:bg-green-700 disabled:opacity-40">BAYAR <span className="text-[10px] text-green-200">[End]</span></button>
                    </div>
                </div>
            </div>

            {showSearch && <SearchModal products={products} onAdd={addItem} onClose={() => setShowSearch(false)} />}
            {showPayment && <PaymentModal grandTotal={totals.grand} amountPaid={amountPaid} setAmountPaid={setAmountPaid} onClose={() => setShowPayment(false)} onProcess={processTransaction} processing={processing} />}
        </JihansLayout>
    );
}
