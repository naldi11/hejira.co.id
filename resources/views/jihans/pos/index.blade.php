@extends('layouts.jihans')
@section('title', 'POS Kasir')
@section('page-title', 'Point of Sales (POS)')

@section('content')
<div class="h-[calc(100vh-140px)] flex flex-col md:flex-row gap-6" x-data="posSystem()">
    {{-- Kiri: Produk & Pencarian --}}
    <div class="w-full md:w-7/12 lg:w-2/3 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
        {{-- Search Bar --}}
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex gap-3">
            <div class="relative flex-1">
                <input type="text" x-model="searchQuery" placeholder="Cari nama produk atau kode..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button @click="openPendingModal()" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors border border-yellow-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Load Pending
            </button>
        </div>

        {{-- Product Grid --}}
        <div class="flex-1 p-4 overflow-y-auto custom-scrollbar bg-gray-50/30">
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)" 
                         class="bg-white border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-orange-500 hover:shadow-md transition-all group flex flex-col h-full relative overflow-hidden">
                        {{-- Stock Badge --}}
                        <div class="absolute top-0 right-0 bg-orange-100 text-orange-800 text-[10px] font-bold px-2 py-1 rounded-bl-lg border-b border-l border-orange-200">
                            Stok: <span x-text="product.current_stock"></span>
                        </div>
                        
                        <div class="w-12 h-12 bg-orange-50 rounded-lg border border-orange-100 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <span class="text-xl">📦</span>
                        </div>
                        <h3 class="font-medium text-gray-800 text-sm leading-tight flex-1" x-text="product.name"></h3>
                        <p class="text-xs text-gray-500 font-mono mt-1 mb-2" x-text="product.code"></p>
                        
                        <div class="mt-auto pt-3 border-t border-gray-100">
                            <p class="font-bold text-orange-600 text-sm" x-text="formatCurrency(product.selling_price)"></p>
                        </div>
                    </div>
                </template>
                
                <div x-show="filteredProducts.length === 0" class="col-span-full py-12 text-center text-gray-500">
                    Produk tidak ditemukan atau stok kosong.
                </div>
            </div>
        </div>
    </div>

    {{-- Kanan: Cart & Checkout --}}
    <div class="w-full md:w-5/12 lg:w-1/3 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden relative">
        {{-- Loader overlay saat proses --}}
        <div x-show="isProcessing" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex flex-col items-center justify-center">
            <svg class="animate-spin w-10 h-10 text-orange-600 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <p class="font-medium text-gray-800">Memproses Transaksi...</p>
        </div>

        {{-- Customer Info --}}
        <div class="p-4 border-b border-gray-100 bg-gray-50 space-y-3">
            <div>
                <select x-model="customerType" class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm font-medium">
                    <option value="retail">Pelanggan Retail (Umum)</option>
                    <option value="agen">Pelanggan B2B / Agen</option>
                </select>
            </div>
            <div x-show="customerType === 'agen'">
                <select x-model="customerId" class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                    <option value="">-- Pilih Data Agen --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>
            <div x-show="customerType === 'retail'">
                <input type="text" x-model="customerName" placeholder="Nama Pelanggan (Opsional)" class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
            </div>
        </div>

        {{-- Cart Items --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            <template x-if="cart.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                    <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p>Keranjang kosong.</p>
                    <p class="text-xs mt-1">Pilih produk di sebelah kiri.</p>
                </div>
            </template>

            <ul class="divide-y divide-gray-100">
                <template x-for="(item, index) in cart" :key="index">
                    <li class="p-4 hover:bg-gray-50 group">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-medium text-gray-800 text-sm leading-tight flex-1" x-text="item.product_name"></h4>
                            <button @click="removeFromCart(index)" class="text-gray-400 hover:text-red-500 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-2">
                                <button @click="updateQuantity(index, -1)" class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-orange-100 hover:text-orange-700 font-bold">-</button>
                                <input type="number" x-model.number="item.quantity" @change="validateQuantity(index)" class="w-12 h-6 text-center text-sm border-none bg-transparent p-0 font-medium focus:ring-0">
                                <button @click="updateQuantity(index, 1)" class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-orange-100 hover:text-orange-700 font-bold">+</button>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500"><span x-text="formatCurrency(item.price)"></span> / <span x-text="item.unit_name"></span></p>
                                <p class="font-bold text-gray-900 text-sm" x-text="formatCurrency(item.total)"></p>
                            </div>
                        </div>
                        {{-- Opsi Diskon Per Item --}}
                        <div class="mt-2 pt-2 border-t border-dashed border-gray-200">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-semibold uppercase text-gray-500">Diskon (Rp)</label>
                                <input type="number" x-model.number="item.discount" @change="recalculateTotals()" class="w-20 h-6 text-right text-xs border-gray-300 rounded focus:ring-orange-500 focus:border-orange-500 px-1 py-0 shadow-sm" placeholder="0">
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Calculation Summary --}}
        <div class="p-4 border-t border-gray-200 bg-white space-y-2 text-sm shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span class="font-medium text-gray-800" x-text="formatCurrency(subtotal)"></span>
            </div>
            
            {{-- PPN Options --}}
            <div class="flex justify-between items-center text-gray-600 py-1">
                <div class="flex items-center gap-2">
                    <span>PPN (11%)</span>
                    <select x-model="ppnType" @change="recalculateTotals()" class="h-6 py-0 pl-1 pr-6 text-xs border-gray-300 rounded focus:ring-orange-500">
                        <option value="none">Tanpa PPN</option>
                        <option value="exclude">PPN Exclude (+)</option>
                        <option value="include">PPN Include (Dalam harga)</option>
                    </select>
                </div>
                <span class="font-medium text-gray-800" x-text="ppnType === 'exclude' ? formatCurrency(taxAmount) : (ppnType === 'include' ? 'Inc.' : 'Rp 0')"></span>
            </div>

            {{-- Biaya Lain --}}
            <div class="flex justify-between items-center text-gray-600">
                <span>Biaya Lain/Ongkir</span>
                <input type="number" x-model.number="otherCosts" @change="recalculateTotals()" class="w-24 h-6 text-right text-xs border-gray-300 rounded focus:ring-orange-500 px-1 py-0" placeholder="0">
            </div>

            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-100 mt-2">
                <span>Total</span>
                <span class="text-orange-600" x-text="formatCurrency(grandTotal)"></span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-4 border-t border-gray-100 bg-gray-50 flex gap-2">
            <button @click="holdTransaction()" :disabled="cart.length === 0" class="flex-1 bg-white border border-yellow-400 text-yellow-700 py-3 rounded-xl font-bold hover:bg-yellow-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm text-sm">
                HOLD (Pending)
            </button>
            <button @click="openPaymentModal()" :disabled="cart.length === 0" class="flex-[2] bg-orange-600 text-white py-3 rounded-xl font-bold hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm text-sm">
                BAYAR LUNAS
            </button>
        </div>
    </div>

    {{-- Payment Modal --}}
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showPaymentModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden" x-transition.scale.origin.bottom>
            <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Penyelesaian Pembayaran</h3>
                <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-5">
                <div class="text-center bg-orange-50 p-4 rounded-xl border border-orange-100">
                    <p class="text-sm font-semibold text-orange-800 uppercase tracking-wider mb-1">Total Tagihan</p>
                    <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(grandTotal)"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" x-model="paymentMethod" value="cash" class="peer sr-only">
                        <div class="p-3 border-2 rounded-xl text-center peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:bg-gray-50 transition-all">
                            <span class="block text-2xl mb-1">💵</span>
                            <span class="font-semibold text-gray-700 text-sm">Tunai (Cash)</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" x-model="paymentMethod" value="transfer" class="peer sr-only">
                        <div class="p-3 border-2 rounded-xl text-center peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:bg-gray-50 transition-all">
                            <span class="block text-2xl mb-1">🏦</span>
                            <span class="font-semibold text-gray-700 text-sm">Transfer Bank</span>
                        </div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Diterima (Rp)</label>
                    <input type="number" x-model.number="amountPaid" class="w-full text-lg font-bold rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm py-3">
                    
                    {{-- Uang Kembalian --}}
                    <div class="mt-2 text-right">
                        <p class="text-sm text-gray-500">Kembalian: 
                            <span class="font-bold text-green-600 ml-1" x-text="amountPaid > grandTotal ? formatCurrency(amountPaid - grandTotal) : 'Rp 0'"></span>
                        </p>
                    </div>
                </div>

                <div x-show="paymentMethod === 'transfer'" class="space-y-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bank Tujuan</label>
                        <input type="text" x-model="bankName" placeholder="Misal: BCA / Mandiri" class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">No. Referensi / Mutasi</label>
                        <input type="text" x-model="referenceNumber" class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea x-model="notes" rows="2" class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500"></textarea>
                </div>
            </div>

            <div class="p-5 border-t border-gray-100 bg-gray-50">
                <button @click="processTransaction()" :disabled="amountPaid < grandTotal || isProcessing" class="w-full bg-gray-900 text-white py-3.5 rounded-xl font-bold hover:bg-black transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    PROSES & CETAK STRUK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('posSystem', () => ({
            products: @json($products),
            searchQuery: '',
            cart: [],
            
            customerType: 'retail',
            customerId: '',
            customerName: '',
            
            subtotal: 0,
            discountAmount: 0, // total diskon item
            ppnType: 'none',
            taxAmount: 0,
            otherCosts: 0,
            grandTotal: 0,

            // Payment
            showPaymentModal: false,
            paymentMethod: 'cash',
            amountPaid: 0,
            bankName: '',
            referenceNumber: '',
            notes: '',
            
            isProcessing: false,

            get filteredProducts() {
                if (this.searchQuery === '') return this.products;
                const query = this.searchQuery.toLowerCase();
                return this.products.filter(p => 
                    p.name.toLowerCase().includes(query) || 
                    (p.code && p.code.toLowerCase().includes(query))
                );
            },

            addToCart(product) {
                // Check if already in cart
                const existingIndex = this.cart.findIndex(item => item.product_id === product.id);
                
                if (existingIndex > -1) {
                    if (this.cart[existingIndex].quantity < product.current_stock) {
                        this.cart[existingIndex].quantity++;
                        this.validateQuantity(existingIndex);
                    } else {
                        alert('Stok tidak mencukupi!');
                    }
                } else {
                    this.cart.push({
                        product_id: product.id,
                        product_name: product.name,
                        product_code: product.code,
                        price: parseFloat(product.selling_price) || 0,
                        quantity: 1,
                        unit_name: product.unit ? product.unit.abbreviation : '',
                        max_stock: parseFloat(product.current_stock),
                        discount: 0,
                        total: parseFloat(product.selling_price) || 0
                    });
                }
                
                this.recalculateTotals();
            },

            updateQuantity(index, delta) {
                const item = this.cart[index];
                const newQty = parseFloat(item.quantity) + delta;
                
                if (newQty > 0 && newQty <= item.max_stock) {
                    item.quantity = newQty;
                    this.validateQuantity(index);
                } else if (newQty > item.max_stock) {
                    alert('Stok maksimum: ' + item.max_stock);
                    item.quantity = item.max_stock;
                    this.validateQuantity(index);
                }
            },

            validateQuantity(index) {
                const item = this.cart[index];
                if (item.quantity <= 0) item.quantity = 1;
                if (item.quantity > item.max_stock) item.quantity = item.max_stock;
                
                if (item.discount > (item.price * item.quantity)) {
                    item.discount = 0;
                }
                
                item.total = (item.quantity * item.price) - item.discount;
                this.recalculateTotals();
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
                this.recalculateTotals();
            },

            recalculateTotals() {
                this.subtotal = 0;
                this.discountAmount = 0;
                
                this.cart.forEach(item => {
                    item.total = (item.quantity * item.price) - parseFloat(item.discount || 0);
                    this.subtotal += item.total;
                    this.discountAmount += parseFloat(item.discount || 0);
                });

                let baseTaxAmount = this.subtotal;
                
                if (this.ppnType === 'exclude') {
                    this.taxAmount = baseTaxAmount * 0.11;
                } else {
                    this.taxAmount = 0;
                }

                this.grandTotal = this.subtotal + this.taxAmount + parseFloat(this.otherCosts || 0);
                
                // Set default payment amount if cash
                if(this.paymentMethod === 'cash' && !this.showPaymentModal) {
                     this.amountPaid = this.grandTotal;
                }
            },

            openPaymentModal() {
                if (this.cart.length === 0) return;
                if (this.customerType === 'agen' && !this.customerId) {
                    alert('Silakan pilih data Agen terlebih dahulu.');
                    return;
                }
                this.amountPaid = this.grandTotal;
                this.showPaymentModal = true;
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
            },

            async processTransaction() {
                if (this.amountPaid < this.grandTotal) {
                    alert('Uang pembayaran kurang dari total tagihan.');
                    return;
                }

                this.isProcessing = true;

                try {
                    const response = await fetch('{{ route("jihans.pos.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            customer_id: this.customerId,
                            customer_name: this.customerName,
                            customer_type: this.customerType,
                            ppn_type: this.ppnType,
                            ppn_rate: 11,
                            subtotal: this.subtotal,
                            discount_amount: this.discountAmount,
                            tax_amount: this.taxAmount,
                            other_costs: this.otherCosts,
                            grand_total: this.grandTotal,
                            payment_method: this.paymentMethod,
                            amount_paid: this.amountPaid,
                            bank_name: this.bankName,
                            reference_number: this.referenceNumber,
                            notes: this.notes,
                            items: this.cart.map(i => ({
                                product_id: i.product_id,
                                quantity: i.quantity,
                                price: i.price,
                                discount: i.discount,
                                total: i.total
                            }))
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Redirect to receipt
                        window.location.href = data.redirect;
                    } else {
                        throw new Error(data.error || 'Terjadi kesalahan saat memproses transaksi.');
                    }

                } catch (error) {
                    alert(error.message);
                    this.isProcessing = false;
                    this.showPaymentModal = false;
                }
            },

            async holdTransaction() {
                if (this.cart.length === 0) return;
                
                if (this.customerType === 'agen' && !this.customerId) {
                    alert('Silakan pilih data Agen terlebih dahulu.');
                    return;
                }

                if (!confirm('Simpan transaksi ini sebagai pending?')) return;

                this.isProcessing = true;

                try {
                    const response = await fetch('{{ route("jihans.pending.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            customer_id: this.customerId,
                            customer_name: this.customerName,
                            customer_type: this.customerType,
                            notes: this.notes,
                            items: this.cart.map(i => ({
                                product_id: i.product_id,
                                quantity: i.quantity,
                                price: i.price,
                                discount: i.discount,
                                total: i.total
                            }))
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        alert(data.message);
                        // Reset form
                        this.cart = [];
                        this.recalculateTotals();
                        this.customerName = '';
                        this.notes = '';
                        this.isProcessing = false;
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan.');
                    }
                } catch (error) {
                    alert(error.message);
                    this.isProcessing = false;
                }
            },
            
            openPendingModal() {
                window.location.href = '{{ route("jihans.pending.index") }}';
            }
        }));
    });
</script>
@endsection
