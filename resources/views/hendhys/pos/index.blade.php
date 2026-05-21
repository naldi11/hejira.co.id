@extends('layouts.hendhys')
@section('title', 'Point of Sales')
@section('page-title', 'Artisan Bakery POS')

@section('content')

    {{-- Data Bridge --}}
    <script>const products = @json($products);</script>

    {{-- Alpine POS System Root Wrapper --}}
    <div class="flex flex-1 min-w-0 h-full w-full overflow-hidden relative" x-data="posSystem()" x-init="initPos()">

        {{-- ======== KIRI: Product Grid ======== --}}
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">

            {{-- Search & Category Bar --}}
            <div class="shrink-0 px-md pt-md pb-sm border-b border-outline-variant bg-surface">
                <div class="flex items-center gap-md">
                    {{-- Search --}}
                    <div class="relative flex-1 max-w-sm">
                        <span
                            class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                        <input x-model="search"
                            class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md rounded-t-lg transition-colors text-on-surface placeholder-on-surface-variant"
                            placeholder="Cari produk..." type="text" />
                    </div>
                    {{-- Cart Toggle (semua layar) --}}
                    <button
                        class="flex items-center gap-xs px-sm py-sm rounded-lg font-label-lg shadow-sm active:scale-95 shrink-0 border transition-colors"
                        :class="cartOpen
                            ? 'bg-primary text-on-primary border-primary'
                            : 'bg-surface-container border-outline-variant text-on-surface hover:bg-surface-container-high'"
                        @click="cartOpen = !cartOpen"
                        :title="cartOpen ? 'Sembunyikan Pesanan' : 'Tampilkan Pesanan'">
                        <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                        <span x-show="cart.length > 0" x-text="cart.length"
                            class="rounded-full px-1.5 py-0 text-xs font-bold leading-none"
                            :class="cartOpen ? 'bg-on-primary text-primary' : 'bg-primary text-on-primary'"></span>
                    </button>
                </div>
                {{-- Category Pills --}}
                <div class="flex gap-sm overflow-x-auto pb-xs scrollbar-hide mt-sm">
                    <template x-for="category in categories" :key="category.id">
                        <button @click="selectedCategory = category.id"
                            class="px-md py-xs font-label-lg text-label-lg rounded-full whitespace-nowrap transition-all border active:scale-95"
                            :class="selectedCategory === category.id
                                    ? 'bg-secondary-container text-on-secondary-container border-secondary-container'
                                    : 'bg-surface-container text-on-surface-variant border-outline-variant hover:bg-surface-container-high'"
                            x-text="category.name">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Products Grid (Scrollable) --}}
            <div class="flex-1 overflow-y-auto p-md custom-scrollbar">
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-md">

                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="group relative border rounded-xl overflow-hidden flex flex-col cursor-pointer transition-all duration-200"
                            :class="{
                                 'border-primary bg-primary-fixed shadow-md ring-2 ring-primary/30': isProductInCart(product.id),
                                 'border-outline-variant bg-surface-container-lowest hover:shadow-lg hover:border-outline': !isProductInCart(product.id) && getDisplayStock(product.id) > 0,
                                 'border-outline-variant bg-surface-container-lowest opacity-60': getDisplayStock(product.id) <= 0
                             }" @click="getDisplayStock(product.id) > 0 ? addToCart(product) : null">

                            {{-- Image --}}
                            <div class="relative aspect-[4/3] bg-surface-container-low overflow-hidden">
                                <template x-if="product.image">
                                    <img :src="'/storage/' + product.image" :alt="product.name"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                </template>
                                <template x-if="!product.image">
                                    <div class="w-full h-full flex items-center justify-center bg-surface-container-high">
                                        <span class="material-symbols-outlined text-outline-variant"
                                            style="font-size: 48px">cake</span>
                                    </div>
                                </template>

                                {{-- In Cart Check --}}
                                <div x-show="isProductInCart(product.id)"
                                    class="absolute top-2 right-2 bg-primary text-on-primary w-7 h-7 rounded-full flex items-center justify-center shadow z-10">
                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                </div>

                                {{-- Out of Stock Overlay --}}
                                <div x-show="getDisplayStock(product.id) <= 0"
                                    class="absolute inset-0 bg-on-surface/30 flex items-center justify-center z-10">
                                    <span
                                        class="bg-on-surface/80 text-surface px-3 py-1 rounded text-xs font-bold uppercase tracking-wider shadow">Habis</span>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="p-sm flex flex-col flex-1">
                                <h3 class="font-label-lg text-label-lg font-bold truncate mb-xs"
                                    :class="isProductInCart(product.id) ? 'text-on-primary-fixed-variant' : 'text-on-surface'"
                                    x-text="product.name"></h3>
                                <div class="flex justify-between items-center mt-auto">
                                    <div>
                                        <span class="font-label-lg text-label-lg font-bold"
                                            :class="isProductInCart(product.id) ? 'text-primary-container' : 'text-primary'"
                                            x-text="formatCurrency(product.selling_price)"></span>
                                        <span class="block text-[10px] font-medium uppercase tracking-wider"
                                            :class="getDisplayStock(product.id) <= 0 ? 'text-error' : 'text-on-surface-variant/70'"
                                            x-text="'Stok: ' + getDisplayStock(product.id)"></span>
                                    </div>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                                        :class="{
                                             'bg-primary text-on-primary': isProductInCart(product.id),
                                             'bg-primary-container text-on-primary-container group-hover:bg-primary group-hover:text-on-primary': !isProductInCart(product.id) && getDisplayStock(product.id) > 0,
                                             'bg-surface-container text-outline cursor-not-allowed': getDisplayStock(product.id) <= 0
                                         }">
                                        <span class="material-symbols-outlined text-[18px]"
                                            x-text="getDisplayStock(product.id) <= 0 ? 'block' : 'add'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Empty State --}}
                    <template x-if="filteredProducts.length === 0">
                        <div
                            class="col-span-full flex flex-col items-center justify-center py-xl text-center text-on-surface-variant">
                            <span class="material-symbols-outlined opacity-40 mb-sm"
                                style="font-size:56px">search_off</span>
                            <p class="font-label-lg text-label-lg font-medium">Tidak ada produk ditemukan.</p>
                            <button @click="search = ''; selectedCategory = 'ALL'"
                                class="mt-sm px-md py-xs rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors text-sm font-medium">Reset
                                Pencarian</button>
                        </div>
                    </template>

                </div>
            </div>
        </div>

        {{-- ======== KANAN: Cart Sidebar ======== --}}
        <aside
            class="shrink-0 flex flex-col h-full border-l border-outline-variant bg-surface-container-lowest transition-all duration-300 ease-in-out overflow-hidden absolute md:relative right-0 top-0 bottom-0 z-20 shadow-[-8px_0_24px_rgba(0,0,0,0.04)]"
            :style="cartOpen ? 'width: 340px; opacity: 1;' : 'width: 0; opacity: 0; border-left-width:0;'">
            <div class="flex flex-col h-full" style="width: 340px;">

                {{-- Cart Header --}}
                <div
                    class="shrink-0 h-[69px] flex items-center justify-between px-md border-b border-outline-variant bg-surface-container-low">
                    <h2 class="font-label-lg text-label-lg font-bold text-on-surface flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary text-[20px] icon-fill">shopping_bag</span>
                        Pesanan Saat Ini
                    </h2>
                    <div class="flex items-center gap-xs">
                        <button @click="clearCart" x-show="cart.length > 0"
                            class="text-error hover:bg-error-container p-xs rounded-full transition-colors"
                            title="Kosongkan">
                            <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                        </button>
                        <button @click="cartOpen = false"
                            class="p-xs rounded-full hover:bg-surface-container text-on-surface-variant transition-colors"
                            title="Sembunyikan">
                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                        </button>
                    </div>
                </div>

                {{-- Customer Info: Manual Input + Autocomplete --}}
                <div class="shrink-0 px-sm pt-sm pb-xs bg-surface border-b border-outline-variant space-y-xs" @click.outside="customerSuggestions = []">
                    {{-- Nama Pelanggan --}}
                    <div class="relative">
                        <div class="flex items-center bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all px-xs">
                            <span class="material-symbols-outlined text-outline text-[16px] shrink-0 mr-xs">person</span>
                            <input
                                type="text"
                                x-model="customerName"
                                @input.debounce.400ms="searchCustomers()"
                                @keydown.escape="customerSuggestions = []"
                                @focus="customerName.length >= 2 && searchCustomers()"
                                placeholder="Nama Pelanggan (opsional)"
                                class="w-full bg-transparent border-none focus:ring-0 text-[12px] font-medium text-on-surface placeholder-on-surface-variant py-sm px-0 outline-none"
                                autocomplete="off"
                            />
                            <button x-show="customerName" @click="customerName = ''; customerPhone = ''; customerSuggestions = []"
                                class="shrink-0 text-outline hover:text-on-surface transition-colors">
                                <span class="material-symbols-outlined text-[14px]">close</span>
                            </button>
                        </div>
                        {{-- Autocomplete Dropdown --}}
                        <div x-show="customerSuggestions.length > 0"
                             class="absolute left-0 right-0 top-full z-50 bg-surface-container-lowest border border-outline-variant rounded-b-lg shadow-lg overflow-hidden">
                            <template x-for="(s, i) in customerSuggestions" :key="i">
                                <button
                                    @click="selectCustomer(s)"
                                    class="w-full flex items-center gap-sm px-sm py-xs hover:bg-surface-container text-left transition-colors border-b border-outline-variant/50 last:border-0">
                                    <span class="material-symbols-outlined text-[14px] text-on-surface-variant shrink-0">history</span>
                                    <div class="min-w-0">
                                        <p class="font-label-sm text-label-sm font-bold text-on-surface truncate" x-text="s.customer_name"></p>
                                        <p class="text-[10px] text-on-surface-variant" x-text="s.customer_phone || 'Tanpa nomor telp'"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                    {{-- Nomor Telp --}}
                    <div class="flex items-center bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all px-xs mb-xs">
                        <span class="material-symbols-outlined text-outline text-[16px] shrink-0 mr-xs">call</span>
                        <input
                            type="tel"
                            x-model="customerPhone"
                            placeholder="Nomor Telp (opsional)"
                            class="w-full bg-transparent border-none focus:ring-0 text-[12px] font-medium text-on-surface placeholder-on-surface-variant py-sm px-0 outline-none"
                            autocomplete="off"
                        />
                    </div>
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-sm">
                    <template x-if="cart.length === 0">
                        <div
                            class="h-full flex flex-col items-center justify-center text-center text-on-surface-variant opacity-60 py-xl">
                            <span class="material-symbols-outlined mb-sm" style="font-size:56px">shopping_cart</span>
                            <p class="font-label-lg text-label-lg">Keranjang kosong</p>
                        </div>
                    </template>
                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="py-sm border-b border-outline-variant/50 last:border-0">
                            {{-- Baris 1: Nama + Tombol Hapus --}}
                            <div class="flex items-start justify-between gap-xs mb-xs">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-label-lg text-label-lg font-bold text-on-surface truncate leading-tight"
                                        x-text="item.name"></h4>
                                    <span class="font-label-sm text-label-sm text-on-surface-variant"
                                        x-text="formatCurrency(getItemPrice(item))"></span>
                                </div>
                                <button @click="removeFromCart(index)"
                                    class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-on-surface-variant hover:bg-error-container hover:text-error transition-colors active:scale-95 mt-0.5"
                                    title="Hapus">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                            {{-- Baris 2: Qty Controls + Total --}}
                            <div class="flex items-center justify-between gap-sm">
                                <div class="flex items-center bg-surface-container rounded-lg border border-outline-variant overflow-hidden shrink-0">
                                    <button @click="updateQty(index, -1)"
                                        class="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:bg-error-container hover:text-error active:scale-95 transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">remove</span>
                                    </button>
                                    <input type="number" x-model.number="item.qty" @change="validateQty(index)"
                                        class="w-10 text-center bg-transparent border-none border-x border-outline-variant p-0 h-8 focus:ring-0 text-on-surface outline-none font-bold text-sm"
                                        min="1" :max="item.max_stock">
                                    <button @click="updateQty(index, 1)"
                                        class="w-8 h-8 flex items-center justify-center text-primary hover:bg-primary-container active:scale-95 transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                    </button>
                                </div>
                                <div class="font-bold text-primary text-sm text-right"
                                    x-text="formatCurrency(getItemTotal(item))"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Totals & Checkout --}}
                <div class="shrink-0 p-md bg-surface-container-low border-t border-outline-variant">
                    <div class="flex justify-between items-center mb-xs text-sm">
                        <span class="text-on-surface-variant">Subtotal</span>
                        <span class="font-medium text-on-surface" x-text="formatCurrency(subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center mb-xs text-sm">
                        <span class="text-on-surface-variant flex items-center gap-xs">
                            Diskon
                            <button @click="discount = parseInt(prompt('Diskon (Rp):', discount) || 0)"
                                class="text-primary hover:bg-primary-container p-0.5 rounded" title="Edit">
                                <span class="material-symbols-outlined text-[14px]">edit</span>
                            </button>
                        </span>
                        <span class="text-error font-medium" x-text="'- ' + formatCurrency(discount)"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm mb-md">
                        <span class="text-on-surface-variant flex items-center gap-xs">
                            Pajak
                            <select x-model="ppnType"
                                class="text-[10px] font-bold py-0 h-5 pl-1 pr-5 border border-outline-variant bg-surface rounded-md focus:ring-0 text-gray-700">
                                <option value="none">Non PPN</option>
                                <option value="include">Inc PPN</option>
                                <option value="exclude">Exc PPN</option>
                            </select>
                        </span>
                        <span class="font-medium text-on-surface" x-text="formatCurrency(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between items-center border-t border-outline-variant pt-sm mb-md">
                        <span class="font-bold text-on-surface text-base">Total</span>
                        <span class="font-bold text-primary text-xl" x-text="formatCurrency(grandTotal)"></span>
                    </div>
                    <div class="flex gap-sm">
                        <button @click="holdTransaction" :disabled="cart.length === 0 || isLoading"
                            class="w-12 h-12 flex-shrink-0 bg-surface border border-outline-variant text-on-surface hover:bg-surface-container rounded-xl flex items-center justify-center disabled:opacity-50">
                            <span class="material-symbols-outlined">pause_circle</span>
                        </button>
                        <button @click="goToCheckout" :disabled="cart.length === 0 || isLoading"
                            class="flex-1 h-12 bg-primary text-on-primary rounded-xl font-bold text-sm flex justify-center items-center gap-xs shadow-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all disabled:opacity-50">
                            <span class="material-symbols-outlined icon-fill">payments</span>
                            Bayar Sekarang
                        </button>
                    </div>
                </div>

            </div>
        </aside>

    </div>

    {{-- Alpine JS Logic --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posSystem', () => ({
                products: products,
                search: '',
                cart: [],
                heldQty: {},
                cartOpen: window.innerWidth >= 768,
                customerName: '',
                customerPhone: '',
                customerSuggestions: [],
                discount: 0,
                ppnType: 'none',
                isLoading: false,
                categories: [{ id: 'ALL', name: 'Semua Produk' }],
                selectedCategory: 'ALL',

                async initPos() {
                    const uniqueJenis = [...new Set(products.map(p => p.jenis).filter(j => j))];
                    uniqueJenis.forEach(j => this.categories.push({ id: j, name: j }));
                    await this.fetchHeldQty();
                    this.loadResumeCart();
                    window.addEventListener('resize', () => {
                        // On mobile resize to desktop, keep cartOpen state as-is
                        // On resize to mobile, keep last chosen state
                    });
                },

                async fetchHeldQty() {
                    try {
                        const res = await fetch('{{ route("hendhys.pos.held-stock") }}');
                        this.heldQty = await res.json();
                    } catch (e) {
                        this.heldQty = {};
                    }
                },

                getDisplayStock(productId) {
                    const product = this.products.find(p => p.id === productId);
                    if (!product) return 0;
                    const dbStock = Number(product.current_stock);
                    const cartQty = this.cart.filter(i => i.product_id === productId)
                                             .reduce((s, i) => s + i.qty, 0);
                    const held = Number(this.heldQty[productId] || 0);
                    return Math.max(0, dbStock - cartQty - held);
                },

                loadResumeCart() {
                    const resumeData = localStorage.getItem('hendhys_resume_cart');
                    if (resumeData) {
                        try {
                            const data = JSON.parse(resumeData);
                            this.cart = data.items || [];
                            this.customerName = data.customerName || '';
                            this.customerPhone = data.customerPhone || '';
                            if (this.cart.length > 0 && window.innerWidth >= 768) this.cartOpen = true;
                            localStorage.removeItem('hendhys_resume_cart');
                        } catch (e) { }
                    }
                },

                get filteredProducts() {
                    let filtered = this.products;
                    if (this.selectedCategory !== 'ALL') filtered = filtered.filter(p => p.jenis === this.selectedCategory);
                    if (this.search !== '') {
                        const s = this.search.toLowerCase();
                        filtered = filtered.filter(p => p.name.toLowerCase().includes(s) || (p.code && p.code.toLowerCase().includes(s)));
                    }
                    return filtered;
                },

                isProductInCart(productId) {
                    return this.cart.some(item => item.product_id === productId);
                },

                addToCart(product) {
                    const available = this.getDisplayStock(product.id);
                    if (available <= 0) return;
                    const existing = this.cart.find(item => item.product_id === product.id);
                    if (existing) {
                        if (available > 0) existing.qty++;
                    } else {
                        const maxAllowed = Number(product.current_stock) - Number(this.heldQty[product.id] || 0);
                        this.cart.unshift({
                            id: Date.now(),
                            product_id: product.id,
                            name: product.name,
                            price: product.selling_price,
                            price_agen: product.price_agen || 0,
                            unit_code: (product.unit && product.unit.abbreviation) ? product.unit.abbreviation : 'pcs',
                            image: product.image,
                            qty: 1,
                            max_stock: maxAllowed
                        });
                        if (window.innerWidth < 768) this.cartOpen = true;
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    if (this.cart.length === 0) this.discount = 0;
                },

                updateQty(index, change) {
                    const item = this.cart[index];
                    const newQty = item.qty + change;
                    if (newQty > 0 && newQty <= item.max_stock) item.qty = newQty;
                },

                validateQty(index) {
                    const item = this.cart[index];
                    if (item.qty <= 0) item.qty = 1;
                    else if (item.qty > item.max_stock) item.qty = item.max_stock;
                },

                clearCart() {
                    if (confirm('Kosongkan semua pesanan?')) { this.cart = []; this.discount = 0; }
                },

                getItemPrice(item) {
                    return Number(item.price);
                },

                getItemTotal(item) {
                    return this.getItemPrice(item) * item.qty;
                },

                get subtotal() {
                    return this.cart.reduce((t, item) => t + this.getItemTotal(item), 0);
                },

                get taxAmount() {
                    let base = Math.max(0, this.subtotal - this.discount);
                    if (this.ppnType === 'exclude') return base * 0.11;
                    if (this.ppnType === 'include') return base - (base / 1.11);
                    return 0;
                },

                get grandTotal() {
                    let t = Math.max(0, this.subtotal - this.discount);
                    if (this.ppnType === 'exclude') t += this.taxAmount;
                    return Math.round(t);
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
                },

                getCustomerName() {
                    return this.customerName || 'Guest';
                },

                async searchCustomers() {
                    if (this.customerName.length < 2) {
                        this.customerSuggestions = [];
                        return;
                    }
                    try {
                        const res = await fetch(`{{ route("hendhys.pos.customer-search") }}?q=` + encodeURIComponent(this.customerName));
                        this.customerSuggestions = await res.json();
                    } catch (e) {
                        this.customerSuggestions = [];
                    }
                },

                selectCustomer(suggestion) {
                    this.customerName = suggestion.customer_name;
                    this.customerPhone = suggestion.customer_phone || '';
                    this.customerSuggestions = [];
                },

                async holdTransaction() {
                    if (this.cart.length === 0) return;
                    this.isLoading = true;
                    const notes = prompt('Referensi Transaksi (Opsional):', '');
                    const payload = {
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        customer_type: 'retail',
                        customer_id: null,
                        notes,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            quantity: i.qty,
                            price: this.getItemPrice(i),
                            discount: 0,
                            total: this.getItemTotal(i)
                        }))
                    };
                    try {
                        const res = await fetch('{{ route("hendhys.pending.store") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            // Update held qty locally so stock stays reduced on card
                            this.cart.forEach(i => {
                                this.heldQty[i.product_id] = (this.heldQty[i.product_id] || 0) + i.qty;
                            });
                            this.cart = [];
                            this.discount = 0;
                            alert(data.message);
                        } else alert(data.message);
                    } catch (e) { alert('Gagal menyimpan.'); }
                    finally { this.isLoading = false; }
                },

                goToCheckout() {
                    if (this.cart.length === 0) return;
                    localStorage.setItem('hendhys_pos_cart', JSON.stringify({
                        items: this.cart.map(i => ({ ...i, price: this.getItemPrice(i), total: this.getItemTotal(i) })),
                        subtotal: this.subtotal, discount: this.discount, ppnType: this.ppnType,
                        taxAmount: this.taxAmount, grandTotal: this.grandTotal,
                        customerName: this.customerName, customerPhone: this.customerPhone
                    }));
                    window.location.href = '{{ route("hendhys.pos.checkout") }}';
                }
            }));
        });
    </script>

@endsection