import { Head, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';
const route = window.route;
export default function PosIndex({ products, paymentMethods }) {
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState([]);
    const [customerName, setCustomerName] = useState('');
    const [discount, setDiscount] = useState(0);
    const [notes, setNotes] = useState('');
    const [selectedPayment, setSelectedPayment] = useState(paymentMethods[0]?.id ?? '');
    const [processing, setProcessing] = useState(false);
    const filtered = useMemo(() => { if (!search) return products; const s = search.toLowerCase(); return products.filter(p => p.name.toLowerCase().includes(s) || p.code.toLowerCase().includes(s)); }, [search, products]);
    const addToCart = (product) => { const existing = cart.find(c => c.product_id === product.id); if (existing) { setCart(cart.map(c => c.product_id === product.id ? { ...c, qty: c.qty + 1 } : c)); } else { setCart([...cart, { product_id: product.id, name: product.name, price: product.price, qty: 1, unit: product.unit, unit_id: product.unit_id, tiered_prices: product.tiered_prices }]); }};
    const updateQty = (productId, qty) => { if (qty <= 0) { setCart(cart.filter(c => c.product_id !== productId)); } else { setCart(cart.map(c => c.product_id === productId ? { ...c, qty } : c)); }};
    const getPrice = (item) => { if (item.tiered_prices?.length) { const sorted = [...item.tiered_prices].sort((a, b) => b.min_qty - a.min_qty); const tier = sorted.find(t => item.qty >= t.min_qty); if (tier) return tier.price; } return item.price; };
    const subtotal = cart.reduce((sum, c) => sum + getPrice(c) * c.qty, 0);
    const grandTotal = subtotal - (discount || 0);
    const checkout = () => { if (cart.length === 0) return alert('Keranjang kosong!'); setProcessing(true); const payload = { customer_name: customerName || 'Pelanggan Umum', discount_amount: discount, notes, payment_method_id: selectedPayment, items: cart.map(c => ({ product_id: c.product_id, quantity: c.qty, price: getPrice(c), unit_id: c.unit_id })) }; fetch(route('hendhys.pos.store'), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' }, body: JSON.stringify(payload) }).then(r => r.json()).then(data => { if (data.success) { setCart([]); setCustomerName(''); setDiscount(0); setNotes(''); alert('Transaksi berhasil! No: ' + (data.transaction_number ?? '')); if (data.receipt_url) window.open(data.receipt_url, '_blank'); } else { alert(data.message || 'Gagal memproses transaksi'); } }).catch(() => alert('Error jaringan')).finally(() => setProcessing(false)); };
    return (
        <HendhysLayout pageTitle="POS Kasir Hendhys">
            <Head title="POS Kasir" />
            <div className="flex h-[calc(100vh-7rem)] gap-6">
                <div className="flex flex-1 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4"><div className="relative"><Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400" /><input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari produk..." className="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500" /></div></div>
                    <div className="custom-scrollbar flex-1 overflow-auto p-4"><div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">{filtered.map(p => (<button key={p.id} onClick={() => addToCart(p)} className="group flex flex-col items-center rounded-xl border border-gray-100 bg-white p-4 text-center shadow-sm transition-all hover:border-amber-200 hover:shadow-md"><div className="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-100"><Icon name="inventory_2" className="text-[24px]" /></div><p className="text-xs font-bold text-gray-800 line-clamp-2">{p.name}</p><p className="text-[10px] text-gray-400">{p.code}</p><p className="mt-1 text-sm font-black text-amber-600">{formatRupiah(p.price)}</p><p className="text-[10px] text-gray-400">stok: {formatQty(p.current_stock)}</p></button>))}</div></div>
                </div>
                <div className="flex w-96 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-100 bg-amber-50/50 p-4"><h3 className="font-bold text-gray-800">Keranjang ({cart.length})</h3></div>
                    <div className="border-b p-3"><input type="text" value={customerName} onChange={(e) => setCustomerName(e.target.value)} placeholder="Nama Pelanggan (opsional)" className="w-full rounded-lg border-gray-300 py-2 text-sm" /></div>
                    <div className="custom-scrollbar flex-1 overflow-auto p-3 space-y-2">{cart.length === 0 ? <div className="py-8 text-center text-sm text-gray-400">Belum ada item</div> : cart.map(c => (<div key={c.product_id} className="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3"><div className="flex-1"><p className="text-sm font-bold text-gray-800">{c.name}</p><p className="text-xs text-amber-600">{formatRupiah(getPrice(c))} / {c.unit}</p></div><div className="flex items-center gap-2"><button onClick={() => updateQty(c.product_id, c.qty - 1)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300"><Icon name="remove" className="text-[16px]" /></button><span className="w-8 text-center text-sm font-bold">{c.qty}</span><button onClick={() => updateQty(c.product_id, c.qty + 1)} className="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200"><Icon name="add" className="text-[16px]" /></button></div></div>))}</div>
                    <div className="border-t border-gray-100 bg-gray-50 p-4 space-y-3">
                        <div className="flex items-center justify-between text-sm"><span className="text-gray-500">Subtotal</span><span className="font-bold">{formatRupiah(subtotal)}</span></div>
                        <div className="flex items-center gap-2"><span className="text-sm text-gray-500">Diskon</span><input type="number" min="0" value={discount} onChange={(e) => setDiscount(Number(e.target.value))} className="w-28 rounded-lg border-gray-300 py-1 text-right text-sm" /></div>
                        <div className="flex items-center justify-between text-lg"><span className="font-bold text-gray-800">Total</span><span className="font-black text-amber-600">{formatRupiah(grandTotal)}</span></div>
                        <select value={selectedPayment} onChange={(e) => setSelectedPayment(e.target.value)} className="w-full rounded-lg border-gray-300 py-2 text-sm">{paymentMethods.map(pm => <option key={pm.id} value={pm.id}>{pm.name}</option>)}</select>
                        <button onClick={checkout} disabled={processing || cart.length === 0} className="w-full rounded-xl bg-amber-600 py-3 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50">{processing ? 'Memproses...' : 'Bayar & Cetak Struk'}</button>
                    </div>
                </div>
            </div>
        </HendhysLayout>
    );
}
