@extends('layouts.hendhys')
@section('title', 'Point of Sales')
@section('page-title', 'Hendhys POS - Mesin Kasir')

@section('content')
<div class="h-[calc(100vh-10rem)]" x-data="posSystem()">
    <div class="flex flex-col lg:flex-row h-full gap-6">
        
        {{-- Kiri: Daftar Produk --}}
        <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-[#faf7f5]">
                <input type="text" x-model="search" placeholder="Cari Produk Bakery..." 
                       class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-300 focus:ring-[#d97706] focus:border-[#d97706] shadow-sm text-sm">
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto custom-scrollbar bg-gray-50/50">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button @click="addToCart(product)" 
                                class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:border-[#d97706] hover:shadow-md transition-all text-left group relative overflow-hidden h-32 flex flex-col justify-between">
                            <div class="absolute top-0 left-0 w-full h-1 bg-amber-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm line-clamp-2 leading-tight" x-text="product.name"></h3>
                                <p class="text-xs text-gray-400 mt-1" x-text="product.code"></p>
                            </div>
                            <div class="flex items-end justify-between mt-2">
                                <div>
                                    <p class="text-xs font-semibold text-[#d97706] mb-0.5">Sisa: <span x-text="Number(product.current_stock)"></span></p>
                                    <p class="font-black text-gray-900" x-text="formatCurrency(product.price)"></p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Kanan: Keranjang & Pembayaran --}}
        <div class="w-full lg:w-[400px] flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative">
            <div class="p-4 border-b border-gray-100 bg-[#3a2310] text-white flex justify-between items-center">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#d97706]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Keranjang
                </h2>
                <button @click="clearCart" x-show="cart.length > 0" class="text-xs text-amber-200 hover:text-white underline">Kosongkan</button>
            </div>

            {{-- Setting Transaksi --}}
            <div class="p-4 border-b border-gray-100 bg-amber-50/50 space-y-3 shrink-0">
                <select x-model="customerType" class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white font-medium">
                    <option value="retail">Customer Retail (Umum)</option>
                    <option value="agen">Customer Agen (Grosir)</option>
                </select>
                <div class="flex gap-2">
                    <select x-model="customerId" class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        <option value="">Pilih Customer (Opsional)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
                <template x-if="cart.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <p class="text-sm">Pilih produk di sebelah kiri untuk menambah ke keranjang</p>
                    </div>
                </template>
                
                <div class="space-y-2">
                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-sm flex flex-col gap-2 relative group hover:border-[#d97706]/50 transition-colors">
                            <button @click="removeFromCart(index)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            
                            <div class="pr-6">
                                <h4 class="font-bold text-gray-800 text-sm leading-tight" x-text="item.name"></h4>
                                <div class="flex gap-2 mt-1 text-xs">
                                    <span class="text-gray-500" x-text="formatCurrency(getItemPrice(item)) + '/' + item.unit_code"></span>
                                    <span x-show="customerType === 'agen' && item.price_agen > 0" class="text-[#d97706] font-semibold">Harga Agen</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-1">
                                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden h-8 bg-gray-50">
                                    <button @click="updateQty(index, -1)" class="w-8 h-full flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-colors">-</button>
                                    <input type="number" x-model.number="item.qty" @change="validateQty(index)" class="w-12 h-full text-center text-sm font-bold border-none p-0 bg-transparent focus:ring-0">
                                    <button @click="updateQty(index, 1)" class="w-8 h-full flex items-center justify-center text-[#d97706] font-bold hover:bg-amber-100 transition-colors">+</button>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900" x-text="formatCurrency(getItemTotal(item))"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Summary & Checkout --}}
            <div class="bg-gray-50 border-t border-gray-200 p-4 shrink-0">
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium" x-text="formatCurrency(subtotal)"></span>
                    </div>
                    
                    <div class="flex justify-between items-center text-gray-600">
                        <span class="flex items-center gap-1">
                            Diskon
                            <button @click="showDiscountModal = true" class="text-[#d97706] hover:text-[#b45309]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </span>
                        <span class="font-medium text-red-500" x-text="'- ' + formatCurrency(discount)"></span>
                    </div>

                    <div class="flex justify-between items-center text-gray-600">
                        <span class="flex items-center gap-1">
                            PPN
                            <select x-model="ppnType" class="text-xs py-0.5 px-1 border-gray-300 rounded ml-2 focus:ring-[#d97706] focus:border-[#d97706]">
                                <option value="non_ppn">Non PPN</option>
                                <option value="include">Include PPN 12%</option>
                                <option value="exclude">Exclude PPN 12%</option>
                            </select>
                        </span>
                        <span class="font-medium" x-text="ppnType === 'exclude' ? '+ ' + formatCurrency(taxAmount) : formatCurrency(taxAmount)"></span>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-3 pb-4 flex justify-between items-end">
                    <span class="text-gray-800 font-bold">TOTAL</span>
                    <span class="text-3xl font-black text-[#d97706] leading-none" x-text="formatCurrency(grandTotal)"></span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button @click="holdTransaction" :disabled="cart.length === 0 || isLoading" 
                            class="bg-yellow-100 text-yellow-800 py-3 rounded-xl font-bold hover:bg-yellow-200 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        HOLD
                    </button>
                    <button @click="showPaymentModal = true" :disabled="cart.length === 0 || isLoading" 
                            class="bg-[#3a2310] text-white py-3 rounded-xl font-bold hover:bg-black disabled:opacity-50 transition-colors shadow-md">
                        BAYAR
                    </button>
                </div>
            </div>

            {{-- Modal Pembayaran --}}
            <div x-show="showPaymentModal" style="display: none;" class="absolute inset-0 z-50 bg-white flex flex-col h-full">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-[#faf7f5]">
                    <h3 class="font-bold text-gray-800 text-lg">Pembayaran</h3>
                    <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6 flex-1 overflow-y-auto bg-white">
                    <div class="text-center mb-8">
                        <p class="text-gray-500 font-medium mb-1">Total Tagihan</p>
                        <p class="text-4xl font-black text-[#d97706]" x-text="formatCurrency(grandTotal)"></p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Metode Pembayaran</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="peer sr-only">
                                    <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-[#d97706] peer-checked:bg-amber-50 text-gray-600 peer-checked:text-[#d97706] font-bold transition-all">
                                        CASH
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod" class="peer sr-only">
                                    <div class="text-center p-3 rounded-lg border-2 border-gray-200 peer-checked:border-[#d97706] peer-checked:bg-amber-50 text-gray-600 peer-checked:text-[#d97706] font-bold transition-all">
                                        TRANSFER
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah Diterima</label>
                            <input type="number" x-model.number="amountPaid" 
                                   class="w-full text-2xl font-bold text-right py-3 px-4 border-gray-300 rounded-xl focus:ring-[#d97706] focus:border-[#d97706] shadow-inner bg-gray-50">
                            
                            <div class="grid grid-cols-4 gap-2 mt-3" x-show="paymentMethod === 'cash'">
                                <button type="button" @click="amountPaid = grandTotal" class="py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium">Pas</button>
                                <button type="button" @click="amountPaid = 50000" class="py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium">50k</button>
                                <button type="button" @click="amountPaid = 100000" class="py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium">100k</button>
                                <button type="button" @click="amountPaid = 200000" class="py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium">200k</button>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'transfer'" class="space-y-4 border-t border-gray-100 pt-4" style="display: none;">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Nama Bank</label>
                                <input type="text" x-model="bankName" class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">No. Referensi (Opsional)</label>
                                <input type="text" x-model="refNumber" class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 text-center" x-show="paymentMethod === 'cash'">
                            <p class="text-sm text-gray-500 font-medium mb-1">Kembalian</p>
                            <p class="text-2xl font-bold" :class="amountPaid >= grandTotal ? 'text-green-600' : 'text-red-500'" 
                               x-text="formatCurrency(Math.max(0, amountPaid - grandTotal))"></p>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50 shrink-0">
                    <button @click="processCheckout" 
                            :disabled="isLoading || (paymentMethod === 'cash' && amountPaid < grandTotal) || (paymentMethod === 'transfer' && amountPaid <= 0)" 
                            class="w-full bg-[#d97706] text-white py-4 rounded-xl font-bold text-lg hover:bg-[#b45309] disabled:opacity-50 disabled:cursor-not-allowed shadow-lg flex justify-center items-center gap-2 transition-all">
                        <span x-show="!isLoading">SELESAIKAN TRANSAKSI</span>
                        <span x-show="isLoading">Memproses...</span>
                    </button>
                </div>
            </div>

            {{-- Modal Diskon --}}
            <div x-show="showDiscountModal" style="display: none;" class="absolute inset-0 z-40 bg-black/50 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden" @click.away="showDiscountModal = false">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-[#faf7f5]">
                        <h3 class="font-bold text-gray-800">Potongan Harga Tambahan</h3>
                        <button @click="showDiscountModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-4">
                        <label class="block text-sm text-gray-600 mb-2">Nominal Diskon (Rp)</label>
                        <input type="number" x-model.number="discount" min="0" class="w-full text-lg border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                        <button @click="showDiscountModal = false" class="w-full mt-4 bg-[#d97706] text-white py-2 rounded-lg font-bold">Terapkan</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const products = @json($products);
</script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('posSystem', () => ({
        products: products,
        search: '',
        cart: [],
        
        customerType: 'retail',
        customerId: '',
        
        discount: 0,
        ppnType: 'non_ppn', // non_ppn, include, exclude
        otherCosts: 0,
        
        showPaymentModal: false,
        showDiscountModal: false,
        paymentMethod: 'cash',
        amountPaid: 0,
        bankName: '',
        refNumber: '',
        
        isLoading: false,

        get filteredProducts() {
            if (this.search === '') {
                return this.products;
            }
            return this.products.filter(product => {
                return product.name.toLowerCase().includes(this.search.toLowerCase()) || 
                       product.code.toLowerCase().includes(this.search.toLowerCase());
            });
        },

        addToCart(product) {
            const existing = this.cart.find(item => item.product_id === product.id);
            if (existing) {
                if (existing.qty < product.current_stock) {
                    existing.qty++;
                } else {
                    alert('Stok tidak mencukupi!');
                }
            } else {
                this.cart.unshift({
                    id: Date.now(),
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    price_agen: product.price_agen,
                    unit_id: product.unit_id,
                    unit_code: product.unit.code,
                    qty: 1,
                    max_stock: product.current_stock
                });
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            if(this.cart.length === 0) {
                this.discount = 0;
            }
        },

        updateQty(index, change) {
            const item = this.cart[index];
            const newQty = item.qty + change;
            if (newQty > 0 && newQty <= item.max_stock) {
                item.qty = newQty;
            } else if (newQty > item.max_stock) {
                alert('Stok tidak mencukupi!');
            }
        },

        validateQty(index) {
            const item = this.cart[index];
            if (item.qty <= 0) {
                item.qty = 1;
            } else if (item.qty > item.max_stock) {
                item.qty = item.max_stock;
                alert('Melebihi maksimal stok!');
            }
        },

        clearCart() {
            if(confirm('Kosongkan keranjang?')) {
                this.cart = [];
                this.discount = 0;
            }
        },

        getItemPrice(item) {
            return this.customerType === 'agen' && item.price_agen > 0 ? Number(item.price_agen) : Number(item.price);
        },

        getItemTotal(item) {
            return this.getItemPrice(item) * item.qty;
        },

        get subtotal() {
            return this.cart.reduce((total, item) => total + this.getItemTotal(item), 0);
        },

        get taxAmount() {
            let base = this.subtotal - this.discount;
            if (base < 0) base = 0;

            if (this.ppnType === 'exclude') {
                return base * 0.12; // PPN 12%
            } else if (this.ppnType === 'include') {
                return base - (base / 1.12);
            }
            return 0;
        },

        get grandTotal() {
            let total = this.subtotal - this.discount;
            if (total < 0) total = 0;

            if (this.ppnType === 'exclude') {
                total += this.taxAmount;
            }
            return Math.round(total);
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
        },

        async holdTransaction() {
            if(this.cart.length === 0) return;
            
            this.isLoading = true;
            
            const payload = {
                customer_type: this.customerType,
                customer_id: this.customerId,
                customer_name: this.customerId ? this.getCustomerName() : 'Umum',
                notes: prompt('Masukkan keterangan (misal: Nama / Meja):', ''),
                items: this.cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.qty,
                    price: this.getItemPrice(item),
                    discount: 0,
                    total: this.getItemTotal(item)
                }))
            };

            try {
                const response = await fetch('{{ route("hendhys.pending.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if(result.success) {
                    alert(result.message);
                    this.cart = [];
                    this.discount = 0;
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                this.isLoading = false;
            }
        },

        async processCheckout() {
            if(this.paymentMethod === 'cash' && this.amountPaid < this.grandTotal) {
                alert('Uang pembayaran kurang!');
                return;
            }

            this.isLoading = true;

            const payload = {
                customer_type: this.customerType,
                customer_id: this.customerId,
                customer_name: this.customerId ? this.getCustomerName() : 'Customer Umum',
                subtotal: this.subtotal,
                discount_amount: this.discount,
                ppn_type: this.ppnType,
                tax_amount: this.taxAmount,
                other_costs: this.otherCosts,
                grand_total: this.grandTotal,
                payment_method: this.paymentMethod,
                amount_paid: this.amountPaid,
                bank_name: this.paymentMethod === 'transfer' ? this.bankName : null,
                reference_number: this.paymentMethod === 'transfer' ? this.refNumber : null,
                items: this.cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.qty,
                    price: this.getItemPrice(item),
                    discount: 0,
                    total: this.getItemTotal(item)
                }))
            };

            try {
                const response = await fetch('{{ route("hendhys.pos.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if(result.success) {
                    window.location.href = result.redirect;
                } else {
                    alert(result.error || 'Terjadi kesalahan sistem.');
                    this.isLoading = false;
                }
            } catch (error) {
                alert('Terjadi kesalahan jaringan.');
                this.isLoading = false;
            }
        },

        getCustomerName() {
            // Bisa mencari dari element select options text jika ingin
            return 'Customer Terdaftar';
        },
        
        init() {
            this.$watch('paymentMethod', value => {
                if(value === 'cash' && this.amountPaid < this.grandTotal) {
                    this.amountPaid = this.grandTotal;
                }
            });
            this.$watch('showPaymentModal', value => {
                if(value && this.amountPaid === 0) {
                    this.amountPaid = this.grandTotal;
                }
            });
        }
    }));
});
</script>
@endsection
