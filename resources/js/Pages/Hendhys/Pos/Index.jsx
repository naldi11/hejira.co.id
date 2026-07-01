import { Head, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import HendhysLayout from '@/Layouts/HendhysLayout';
import Icon from '@/Components/Icon';
import { formatRupiah, formatQty } from '@/lib/format';

const route = window.route;

export default function PosIndex({ products, paymentMethods }) {
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState([]);
    
    // Customer Info states
    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [customerSuggestions, setCustomerSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [searchLoading, setSearchLoading] = useState(false);

    // Payment and Checkout states
    const [discount, setDiscount] = useState(0);
    const [amountPaid, setAmountPaid] = useState('');
    const [notes, setNotes] = useState('');
    const [selectedPayment, setSelectedPayment] = useState(() => {
        const cashMethod = paymentMethods.find(pm =>
            pm.type === 'tunai' ||
            pm.name.toLowerCase().includes('tunai') ||
            pm.name.toLowerCase().includes('cash')
        );
        return cashMethod?.id ?? (paymentMethods[0]?.id ?? '');
    });
    const [processing, setProcessing] = useState(false);

    const selectedMethod = paymentMethods.find(pm => pm.id === selectedPayment);
    const isNonCash = selectedMethod && selectedMethod.type !== 'tunai' &&
        !selectedMethod.name.toLowerCase().includes('tunai') &&
        !selectedMethod.name.toLowerCase().includes('cash');

    const filtered = useMemo(() => {
        if (!search) return products;
        const s = search.toLowerCase();
        return products.filter(p => p.name.toLowerCase().includes(s) || p.code.toLowerCase().includes(s));
    }, [search, products]);

    const addToCart = (product) => {
        if (product.current_stock <= 0) {
            alert(`Stok produk "${product.name}" habis. Tidak bisa ditambahkan ke keranjang.`);
            return;
        }
        const existing = cart.find(c => c.product_id === product.id);
        if (existing) {
            setCart(cart.map(c => c.product_id === product.id ? { ...c, qty: c.qty + 1 } : c));
        } else {
            setCart([...cart, {
                product_id: product.id,
                name: product.name,
                price: product.price,
                qty: 1,
                unit: product.unit,
                unit_id: product.unit_id,
                tiered_prices: product.tiered_prices
            }]);
        }
    };

    const updateQty = (productId, qty) => {
        if (qty <= 0) {
            setCart(cart.filter(c => c.product_id !== productId));
        } else {
            setCart(cart.map(c => c.product_id === productId ? { ...c, qty } : c));
        }
    };

    const getPrice = (item) => {
        if (item.tiered_prices?.length) {
            const sorted = [...item.tiered_prices].sort((a, b) => b.min_qty - a.min_qty);
            const tier = sorted.find(t => item.qty >= t.min_qty);
            if (tier) return tier.price;
        }
        return item.price;
    };

    const subtotal = cart.reduce((sum, c) => sum + getPrice(c) * c.qty, 0);
    const grandTotal = subtotal - (discount || 0);

    const changeAmount = useMemo(() => {
        if (isNonCash) return 0;
        const paid = parseFloat(amountPaid) || 0;
        return Math.max(0, paid - grandTotal);
    }, [amountPaid, grandTotal, isNonCash]);

    const quickCashOptions = useMemo(() => {
        if (grandTotal <= 0) return [];
        const options = new Set();
        options.add(grandTotal); // Exact cash (Uang Pas)
        
        // Standard denominations in Indonesia:
        const denominations = [5000, 10000, 20000, 50000, 100000];
        
        // Find next denomination greater than total
        denominations.forEach(denom => {
            if (denom > grandTotal) {
                options.add(denom);
            }
            // Check if multiple of denomination makes sense
            const multiple = Math.ceil(grandTotal / denom) * denom;
            if (multiple > grandTotal && multiple <= grandTotal + 100000) {
                options.add(multiple);
            }
        });
        
        // Sort and limit to 4 options
        return Array.from(options).sort((a, b) => a - b).slice(0, 4);
    }, [grandTotal]);

    const handleCustomerNameChange = (val) => {
        setCustomerName(val);
        if (val.length >= 2) {
            setSearchLoading(true);
            fetch(`/hendhys/pos/customer-search?q=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    setCustomerSuggestions(data);
                    setShowSuggestions(true);
                })
                .catch(err => console.error('Gagal memuat saran pelanggan', err))
                .finally(() => setSearchLoading(false));
        } else {
            setCustomerSuggestions([]);
            setShowSuggestions(false);
        }
    };

    const selectCustomer = (cust) => {
        setCustomerName(cust.customer_name);
        setCustomerPhone(cust.customer_phone || '');
        setCustomerSuggestions([]);
        setShowSuggestions(false);
    };

    const clearCustomer = () => {
        setCustomerName('');
        setCustomerPhone('');
        setCustomerSuggestions([]);
        setShowSuggestions(false);
    };

    const checkout = () => {
        if (cart.length === 0) return alert('Keranjang kosong!');
        const paid = isNonCash ? grandTotal : (parseFloat(amountPaid) || 0);
        if (paid < grandTotal) return alert('Nominal bayar kurang dari grand total!');

        setProcessing(true);
        const payload = {
            customer_name: customerName || 'Pelanggan Umum',
            customer_phone: customerPhone || null,
            customer_type: 'Pelanggan Individual',
            subtotal: subtotal,
            grand_total: grandTotal,
            discount_amount: discount || 0,
            amount_paid: isNonCash ? grandTotal : paid,
            notes: notes,
            payment_method_id: selectedPayment,
            ppn_type: 'none',
            tax_amount: 0,
            other_costs: 0,
            items: cart.map(c => ({
                product_id: c.product_id,
                quantity: c.qty,
                price: getPrice(c),
                discount: 0,
                total: getPrice(c) * c.qty
            }))
        };

        fetch(route('hendhys.pos.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setCart([]);
                clearCustomer();
                setDiscount(0);
                setAmountPaid('');
                setNotes('');
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                alert(data.error || data.message || 'Gagal memproses transaksi');
            }
        })
        .catch((err) => {
            console.error(err);
            alert('Error jaringan');
        })
        .finally(() => setProcessing(false));
    };

    // Only scroll if cart has more than 5 items
    const shouldScroll = cart.length > 5;

    return (
        <HendhysLayout pageTitle="POS Kasir Hendhys">
            <Head title="POS Kasir" />
            <div className="flex h-[calc(100vh-7rem)] gap-6">
                {/* Catalog Grid Column */}
                <div className="flex flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <div className="border-b border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.01]">
                        <div className="relative">
                            <Icon name="search" className="absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-gray-400 dark:text-gray-500" />
                            <input 
                                type="text" 
                                value={search} 
                                onChange={(e) => setSearch(e.target.value)} 
                                placeholder="Cari produk..." 
                                className="w-full rounded-lg border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" 
                            />
                        </div>
                    </div>
                    
                    <div className="custom-scrollbar flex-1 overflow-auto p-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2">
                            {filtered.map(p => (
                                <div 
                                    key={p.id} 
                                    className={`group flex flex-col rounded-2xl border bg-white shadow-theme-xs transition-all dark:bg-white/[0.03] overflow-hidden ${p.current_stock <= 0 ? 'border-gray-200 dark:border-gray-800 opacity-55' : 'border-gray-255 dark:border-gray-800 hover:border-amber-250 hover:shadow-md'}`}
                                >
                                    {/* Image and Stock Overlay */}
                                    <div 
                                        onClick={() => addToCart(p)}
                                        className={`relative h-44 w-full overflow-hidden bg-gray-50 dark:bg-white/[0.01] flex items-center justify-center border-b border-gray-150 dark:border-gray-800 ${p.current_stock <= 0 ? 'cursor-not-allowed' : 'cursor-pointer'}`}
                                    >
                                        {p.photo ? (
                                            <img src={p.photo} alt={p.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                        ) : (
                                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:text-amber-400 dark:bg-amber-500/10 transition-colors group-hover:bg-amber-100">
                                                <Icon name="inventory_2" className="text-[32px]" />
                                            </div>
                                        )}
                                        
                                        {/* Stock Badge Overlay */}
                                        <div className={`absolute top-2.5 right-2.5 backdrop-blur-xs text-[10px] font-bold text-white px-2.5 py-0.5 rounded-full flex items-center gap-1 shadow-sm ${p.current_stock > 0 ? 'bg-black/60 dark:bg-black/80' : 'bg-amber-600/90'}`}>
                                            <span className={`h-1.5 w-1.5 rounded-full ${p.current_stock > 0 ? 'bg-green-400 animate-pulse' : 'bg-yellow-300'}`}></span>
                                            {p.current_stock > 0 ? `Stok: ${formatQty(p.current_stock)}` : 'Stok Habis'}
                                        </div>
                                    </div>

                                    {/* Info & Add Button */}
                                    <div className="p-4 flex flex-col flex-1">
                                        <h4 
                                            onClick={() => addToCart(p)}
                                            className="text-sm font-bold text-gray-800 line-clamp-2 dark:text-white/90 cursor-pointer hover:text-amber-600 dark:hover:text-amber-400 transition-colors flex-1"
                                        >
                                            {p.name}
                                        </h4>
                                        <p className="text-[10px] text-gray-400 dark:text-gray-500 font-mono mt-0.5">{p.code}</p>
                                        
                                        <div className="mt-3 flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                                            <span className="text-base font-black text-amber-600 dark:text-amber-400">
                                                {formatRupiah(p.price)}
                                            </span>
                                            
                                            {/* Dedicated Add Product Button */}
                                            <button 
                                                onClick={() => addToCart(p)}
                                                disabled={p.current_stock <= 0}
                                                className={`flex h-9 w-9 items-center justify-center rounded-xl text-white transition-all shadow-md ${p.current_stock <= 0 ? 'bg-gray-400 cursor-not-allowed shadow-none' : 'bg-amber-600 hover:bg-amber-700 active:scale-95 shadow-amber-600/10 hover:shadow-lg hover:shadow-amber-600/20'}`}
                                                title={p.current_stock <= 0 ? 'Stok habis' : 'Tambah ke Keranjang'}
                                            >
                                                <Icon name="add" className="text-[22px] font-bold" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Checkout Sidebar Column (Scrollable Sidebar container to prevent squishing) */}
                <div className="flex w-96 flex-col overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] custom-scrollbar">
                    {/* Header */}
                    <div className="border-b border-gray-100 bg-amber-50/50 p-4 dark:border-gray-800 dark:bg-white/[0.02] shrink-0">
                        <h3 className="font-bold text-gray-800 dark:text-white/90">Keranjang ({cart.length})</h3>
                    </div>
                    
                    {/* Customer Info Form (Fixed at top layout, shrink-0) */}
                    <div className="border-b border-gray-200 p-4 space-y-3.5 dark:border-gray-800 shrink-0">
                        <div className="relative">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-1">
                                Pelanggan
                            </label>
                            <div className="relative">
                                <input 
                                    type="text" 
                                    value={customerName} 
                                    onChange={(e) => handleCustomerNameChange(e.target.value)}
                                    onFocus={() => { if (customerSuggestions.length > 0) setShowSuggestions(true); }}
                                    onBlur={() => setTimeout(() => setShowSuggestions(false), 250)}
                                    placeholder="Nama Pelanggan / Cari..." 
                                    className="w-full rounded-lg border-gray-300 py-2.5 px-3 pr-10 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 font-semibold" 
                                />
                                {customerName && (
                                    <button
                                        type="button"
                                        onClick={clearCustomer}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-650 dark:hover:text-gray-200"
                                    >
                                        <Icon name="close" className="text-[16px]" />
                                    </button>
                                )}
                            </div>
                            
                            {showSuggestions && customerSuggestions.length > 0 && (
                                <div className="absolute left-0 right-0 z-50 mt-1 max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-750 dark:bg-gray-800">
                                    {customerSuggestions.map((cust, idx) => (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => selectCustomer(cust)}
                                            className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/[0.02] border-b border-gray-100 dark:border-gray-700 last:border-b-0 flex flex-col"
                                        >
                                            <span className="font-semibold text-gray-800 dark:text-white/90">{cust.customer_name}</span>
                                            <span className="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{cust.customer_phone || '-'}</span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                        
                        <div>
                            <label className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-0.5">
                                No. Telepon
                            </label>
                            <input 
                                type="text" 
                                value={customerPhone} 
                                onChange={(e) => setCustomerPhone(e.target.value)} 
                                placeholder="08..." 
                                className="w-full rounded-lg border-gray-300 py-2.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500 font-semibold" 
                            />
                        </div>
                    </div>

                    {/* Cart Items List Container (shrink-0, min-h constraint) */}
                    <div className="p-4 flex flex-col min-h-[140px] shrink-0 border-b border-gray-200 dark:border-gray-800">
                        <label className="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-2 shrink-0">
                            Item Belanja
                        </label>
                        
                        <div className={`custom-scrollbar space-y-2 ${shouldScroll ? 'max-h-[340px] overflow-y-auto pr-1' : 'overflow-visible'}`}>
                            {cart.length === 0 ? (
                                <div className="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada item</div>
                            ) : (
                                cart.map(c => (
                                    <div key={c.product_id} className="flex items-center justify-between rounded-lg border border-gray-150 bg-gray-50 p-3 dark:border-gray-850 dark:bg-white/[0.02]">
                                        <div className="flex-1 min-w-0 pr-2">
                                            <p className="text-sm font-bold text-gray-800 truncate dark:text-white/90">{c.name}</p>
                                            <p className="text-xs text-amber-600 dark:text-amber-400">{formatRupiah(getPrice(c))} / {c.unit}</p>
                                        </div>
                                        <div className="flex items-center gap-2 shrink-0">
                                            <button 
                                                onClick={() => updateQty(c.product_id, c.qty - 1)} 
                                                className="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                            >
                                                <Icon name="remove" className="text-[16px]" />
                                            </button>
                                            <span className="w-8 text-center text-sm font-bold text-gray-800 dark:text-white/90">{c.qty}</span>
                                            <button 
                                                onClick={() => updateQty(c.product_id, c.qty + 1)} 
                                                className="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-400 dark:hover:bg-amber-500/30"
                                            >
                                                <Icon name="add" className="text-[16px]" />
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                    
                    {/* Compact Calculation Footer Panel (shrink-0) */}
                    <div className="bg-gray-50 p-4 space-y-4 dark:border-gray-855 dark:bg-white/[0.02] shrink-0">
                        {/* Subtotal & Diskon */}
                        <div className="flex items-center justify-between text-sm">
                            <div className="flex items-center gap-1">
                                <span className="text-gray-500 dark:text-gray-400">Subtotal:</span>
                                <span className="font-bold">{formatRupiah(subtotal)}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-500 dark:text-gray-400 font-semibold text-xs">Diskon:</span>
                                <input 
                                    type="number" 
                                    min="0" 
                                    value={discount} 
                                    onChange={(e) => setDiscount(Number(e.target.value))} 
                                    className="w-28 rounded-lg border-gray-300 py-2 px-3 text-sm font-bold text-right dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" 
                                />
                            </div>
                        </div>
                        
                        {/* Total & Nominal Bayar */}
                        <div className="flex items-center justify-between border-t border-dashed border-gray-250 pt-3 dark:border-gray-700">
                            <div>
                                <span className="text-[10px] text-gray-400 block font-bold uppercase tracking-wider">Total</span>
                                <span className="text-lg font-black text-amber-600 dark:text-amber-400">{formatRupiah(grandTotal)}</span>
                            </div>
                            {isNonCash ? (
                                <div className="text-right">
                                    <span className="text-[10px] text-gray-400 block font-bold uppercase tracking-wider">Nominal Bayar</span>
                                    <span className="text-sm font-extrabold text-gray-700 dark:text-gray-200">{formatRupiah(grandTotal)}</span>
                                    <span className="block text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Lunas Otomatis</span>
                                </div>
                            ) : (
                                <div className="text-right">
                                    <span className="text-[10px] text-gray-400 block font-bold uppercase tracking-wider">Nominal Bayar</span>
                                    <input 
                                        type="number" 
                                        min="0" 
                                        value={amountPaid} 
                                        onChange={(e) => setAmountPaid(e.target.value)} 
                                        placeholder="0"
                                        className="w-36 rounded-lg border-gray-300 py-2 px-3 text-sm font-extrabold text-right dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" 
                                    />
                                </div>
                            )}
                        </div>

                        {/* Quick cash suggestions - hanya untuk tunai */}
                        {!isNonCash && quickCashOptions.length > 0 && (
                            <div className="flex flex-wrap justify-end gap-1.5 mt-1">
                                {quickCashOptions.map((opt, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        onClick={() => setAmountPaid(opt.toString())}
                                        className={`text-[10px] font-bold px-2.5 py-1 rounded border transition-colors ${
                                            parseFloat(amountPaid) === opt
                                                ? 'border-amber-600 bg-amber-600 text-white dark:border-amber-500 dark:bg-amber-500'
                                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-650 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/[0.05]'
                                        }`}
                                    >
                                        {opt === grandTotal ? 'Pas' : formatRupiah(opt)}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Kembalian - hanya tampil untuk pembayaran tunai */}
                        {!isNonCash && (
                        <div className="flex items-center justify-between text-sm pt-0.5">
                            <span className="text-gray-500 dark:text-gray-400">Kembalian:</span>
                            <span className={`text-base font-extrabold ${changeAmount > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-800 dark:text-white/90'}`}>
                                {formatRupiah(changeAmount)}
                            </span>
                        </div>
                        )}

                        {/* Metode Pembayaran */}
                        {paymentMethods.length > 0 && (
                        <div className="space-y-1.5 pt-0.5">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Metode Pembayaran</label>
                            <div className="grid grid-cols-2 gap-1.5">
                                {paymentMethods.map(pm => (
                                    <button
                                        key={pm.id}
                                        type="button"
                                        onClick={() => setSelectedPayment(pm.id)}
                                        className={`py-2 px-3 rounded-lg text-xs font-bold border transition-all ${
                                            selectedPayment === pm.id
                                                ? 'bg-amber-600 border-amber-600 text-white shadow-md shadow-amber-600/20'
                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-amber-400 hover:text-amber-600'
                                        }`}
                                    >
                                        {pm.name}
                                    </button>
                                ))}
                            </div>
                            {isNonCash && selectedMethod?.bank_name && (
                                <p className="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                                    {selectedMethod.bank_name}{selectedMethod.account_number ? ` · ${selectedMethod.account_number}` : ''}
                                    {selectedMethod.account_name ? ` (${selectedMethod.account_name})` : ''}
                                </p>
                            )}
                        </div>
                        )}

                        {/* Catatan (Full Width) */}
                        <div className="space-y-1">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Catatan</label>
                            <input 
                                type="text" 
                                value={notes} 
                                onChange={(e) => setNotes(e.target.value)} 
                                placeholder="Catatan transaksi (opsional)..." 
                                className="w-full rounded-lg border-gray-300 py-2.5 px-3 text-sm dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-amber-500 focus:ring-amber-500" 
                            />
                        </div>
                        
                        <button 
                            onClick={checkout} 
                            disabled={processing || cart.length === 0 || (!isNonCash && (amountPaid === '' || parseFloat(amountPaid) < grandTotal))} 
                            className="w-full rounded-xl bg-amber-600 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50 transition-colors"
                        >
                            {processing ? 'Memproses...' : 'Bayar & Cetak Struk'}
                        </button>
                    </div>
                </div>
            </div>
        </HendhysLayout>
    );
}
