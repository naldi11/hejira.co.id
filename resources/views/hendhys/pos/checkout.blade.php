@extends('layouts.hendhys')
@section('title', 'Proses Pembayaran')
@section('page-title', 'Checkout')
@section('wrapper-attributes', 'x-data="checkoutSystem()" x-cloak')

@section('content')
    <!-- Use flex center since exactly matching the modal layout from user provided HTML -->
    <div
        class="h-full w-full flex items-center justify-center p-margin-mobile md:p-margin-desktop bg-surface/50 backdrop-blur-sm z-50">

        <!-- Empty Cart Alert -->
        <div x-show="!cartData || cartData.items.length === 0"
            class="bg-surface-container-lowest rounded-xl shadow-level-3 p-lg text-center max-w-sm">
            <span class="material-symbols-outlined text-error text-[64px] mb-md">remove_shopping_cart</span>
            <h2 class="font-headline-md text-headline-md text-on-surface mb-sm">Keranjang Kosong</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-md">Anda belum memilih produk atau data sesi
                telah berakhir.</p>
            <a href="{{ route('hendhys.pos.index') }}"
                class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg transition-colors hover:bg-on-primary-fixed-variant">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Kembali ke Kasir
            </a>
        </div>

        <!-- Modal Container -->
        <div x-show="cartData && cartData.items.length > 0"
            class="bg-surface-container-lowest rounded-xl shadow-level-3 w-full max-w-4xl max-h-full flex flex-col z-50 overflow-hidden relative">

            <!-- Header -->
            <div class="flex items-center justify-between p-md border-b border-surface-variant">
                <h2 class="font-headline-md text-headline-md text-primary">Checkout</h2>
                <a href="{{ route('hendhys.pos.index') }}"
                    class="p-base rounded-full hover:bg-surface-container transition-colors text-on-surface-variant active:scale-95">
                    <span class="material-symbols-outlined text-headline-md">close</span>
                </a>
            </div>

            <!-- Content Area -->
            <div class="flex flex-col md:flex-row min-h-[60vh] overflow-hidden">
                <!-- Left Side: Payment Details & Input (60%) -->
                <div class="w-full md:w-[60%] p-md overflow-y-auto flex flex-col gap-lg border-r border-surface-variant">

                    <!-- Total Amount Display -->
                    <div
                        class="bg-surface-container p-lg rounded-lg flex flex-col items-center justify-center text-center shadow-level-1">
                        <span
                            class="font-label-lg text-label-lg text-on-surface-variant mb-base uppercase tracking-wider">Total
                            Pembayaran</span>
                        <span class="font-display-lg text-display-lg text-primary"
                            x-text="cartData ? formatCurrency(cartData.grandTotal) : '0'"></span>
                    </div>

                    <!-- Payment Methods -->
                    <div>
                        <h3 class="font-title-lg text-title-lg text-on-surface mb-md">Metode Pembayaran</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-sm">
                            <!-- Tunai -->
                            <label class="cursor-pointer">
                                <input type="radio" value="cash" x-model="paymentMethod" class="peer sr-only">
                                <div class="flex flex-col items-center justify-center p-md rounded-lg border transition-all active:scale-95"
                                    :class="paymentMethod === 'cash' ? 'border-primary bg-secondary-container bg-opacity-10 text-primary shadow-level-2' : 'border-outline-variant bg-surface hover:bg-surface-container-low text-on-surface-variant'">
                                    <span class="material-symbols-outlined mb-base"
                                        :class="paymentMethod === 'cash' ? 'icon-fill' : ''"
                                        style="font-size: 32px;">payments</span>
                                    <span class="font-label-lg text-label-lg">Tunai</span>
                                </div>
                            </label>
                            <!-- QRIS/Transfer -->
                            <label class="cursor-pointer">
                                <input type="radio" value="transfer" x-model="paymentMethod" class="peer sr-only">
                                <div class="flex flex-col items-center justify-center p-md rounded-lg border transition-all active:scale-95"
                                    :class="paymentMethod === 'transfer' ? 'border-primary bg-secondary-container bg-opacity-10 text-primary shadow-level-2' : 'border-outline-variant bg-surface hover:bg-surface-container-low text-on-surface-variant'">
                                    <span class="material-symbols-outlined mb-base"
                                        :class="paymentMethod === 'transfer' ? 'icon-fill' : ''"
                                        style="font-size: 32px;">qr_code_scanner</span>
                                    <span class="font-label-lg text-label-lg">Non-Tunai</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Cash Quick Buttons (Visible when Tunai is selected) -->
                    <div x-show="paymentMethod === 'cash'" class="animate-fade-in space-y-sm">
                        <h4 class="font-label-lg text-label-lg text-on-surface-variant mb-sm">Pilih Nominal Cepat</h4>
                        <div class="flex flex-wrap gap-sm">
                            <button type="button" @click="amountPaid = cartData.grandTotal"
                                class="px-md py-sm rounded-full bg-surface-container border border-outline-variant hover:bg-surface-container-high text-on-surface font-label-lg text-label-lg transition-colors active:scale-95">Uang
                                Pas</button>
                            <button type="button" @click="amountPaid = 50000"
                                class="px-md py-sm rounded-full bg-surface-container border border-outline-variant hover:bg-surface-container-high text-on-surface font-label-lg text-label-lg transition-colors active:scale-95">Rp
                                50.000</button>
                            <button type="button" @click="amountPaid = 100000"
                                class="px-md py-sm rounded-full bg-surface-container border border-outline-variant hover:bg-surface-container-high text-on-surface font-label-lg text-label-lg transition-colors active:scale-95">Rp
                                100.000</button>
                            <button type="button" @click="amountPaid = 200000"
                                class="px-md py-sm rounded-full bg-surface-container border border-outline-variant hover:bg-surface-container-high text-on-surface font-label-lg text-label-lg transition-colors active:scale-95">Rp
                                200.000</button>
                        </div>
                        <div
                            class="mt-md bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-secondary focus-within:border-b-2 transition-all flex items-center px-sm py-xs">
                            <span class="text-on-surface-variant mr-sm font-label-lg">Rp</span>
                            <input type="number" x-model.number="amountPaid"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-lg text-body-lg text-on-surface placeholder-on-surface-variant py-sm px-0"
                                placeholder="Masukkan Nominal Lainnya" />
                        </div>

                        <!-- Kembalian Display -->
                        <div class="flex justify-between items-center p-sm rounded-lg"
                            :class="amountPaid >= (cartData ? cartData.grandTotal : 0) ? 'bg-primary-fixed border border-primary-fixed-dim' : 'bg-error-container bg-opacity-20 border border-error-container'">
                            <span class="font-body-md text-body-md text-on-surface-variant">Kembalian:</span>
                            <span class="font-headline-md text-headline-md"
                                :class="amountPaid >= (cartData ? cartData.grandTotal : 0) ? 'text-primary' : 'text-error'"
                                x-text="cartData ? formatCurrency(Math.max(0, amountPaid - cartData.grandTotal)) : '0'"></span>
                        </div>
                    </div>

                    <!-- Transfer Settings (Visible when Transfer is selected) -->
                    <div x-show="paymentMethod === 'transfer'" class="animate-fade-in space-y-md">
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Bank /
                                E-Wallet</label>
                            <div
                                class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-secondary focus-within:border-b-2 transition-all">
                                <input type="text" x-model="bankName" placeholder="Cth: BCA, Mandiri, GoPay"
                                    class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">No. Referensi
                                (Opsional)</label>
                            <div
                                class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-secondary focus-within:border-b-2 transition-all">
                                <input type="text" x-model="refNumber" placeholder="Cth: INV-XXXXXXXX"
                                    class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm" />
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Side: Order Summary & Promo (40%) -->
                <div class="w-full md:w-[40%] bg-surface-container-lowest flex flex-col justify-between">

                    <div
                        class="p-md flex flex-col h-full border-b border-surface-variant md:border-b-0 overflow-y-auto custom-scrollbar">
                        <h3 class="font-title-lg text-title-lg text-on-surface mb-sm pb-sm border-b border-surface-variant">
                            Ringkasan Pesanan</h3>

                        <!-- Order Items list (scrollable if many) -->
                        <div class="flex-1 space-y-sm my-xs overflow-y-auto p-1 custom-scrollbar">
                            <template x-if="cartData">
                                <template x-for="(item, index) in cartData.items" :key="index">
                                    <div class="flex justify-between items-start pt-1">
                                        <div class="pr-2">
                                            <p class="font-label-lg text-label-lg text-on-surface leading-tight"
                                                x-text="item.name"></p>
                                            <p class="text-on-surface-variant font-label-sm text-[11px]"
                                                x-text="item.qty + ' x ' + formatCurrency(item.price)"></p>
                                        </div>
                                        <p class="font-label-lg text-label-lg text-on-surface text-right shrink-0"
                                            x-text="formatCurrency(item.total)"></p>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <!-- Receipt Summary -->
                        <div class="flex-shrink-0 flex flex-col gap-sm pt-sm border-t border-surface-variant">
                            <div class="flex justify-between font-body-md text-body-md text-on-surface-variant">
                                <span x-text="'Subtotal (' + (cartData ? cartData.items.length : 0) + ' Item)'"></span>
                                <span x-text="cartData ? formatCurrency(cartData.subtotal) : '0'"></span>
                            </div>
                            <div class="flex justify-between font-body-md text-body-md text-error"
                                x-show="cartData && cartData.discount > 0">
                                <span>Diskon</span>
                                <span x-text="cartData ? '- ' + formatCurrency(cartData.discount) : '0'"></span>
                            </div>
                            <div class="flex justify-between font-body-md text-body-md text-on-surface-variant pb-sm border-b border-outline-variant"
                                x-show="cartData && (cartData.taxAmount > 0 || cartData.ppnType === 'include')">
                                <span>Pajak (<span
                                        x-text="cartData ? (cartData.ppnType === 'include' ? 'Inc.' : 'Exc.') : ''"></span>)</span>
                                <span
                                    x-text="cartData ? (cartData.ppnType === 'exclude' ? '+ ' : '') + formatCurrency(cartData.taxAmount) : '0'"></span>
                            </div>
                            <div class="flex justify-between items-center mt-sm">
                                <span class="font-title-lg text-title-lg text-on-surface">Total Akhir</span>
                                <span class="font-headline-md text-headline-md text-primary"
                                    x-text="cartData ? formatCurrency(cartData.grandTotal) : '0'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="p-md bg-surface-container-lowest shrink-0 shadow-[0_-4px_16px_rgba(0,0,0,0.02)]">
                        <button @click="processCheckout"
                            :disabled="isLoading || (paymentMethod === 'cash' && amountPaid < (cartData?cartData.grandTotal:0))"
                            class="w-full bg-primary text-on-primary font-label-lg text-label-lg py-md rounded-lg shadow-level-2 hover:bg-on-primary-fixed-variant transition-all active:scale-[0.98] flex items-center justify-center gap-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isLoading" class="material-symbols-outlined icon-fill">check_circle</span>
                            <span x-show="!isLoading">Selesaikan Pesanan & Cetak</span>
                            <span x-show="isLoading" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkoutSystem', () => ({
                cartData: null,
                paymentMethod: 'cash',
                amountPaid: 0,
                bankName: '',
                refNumber: '',
                isLoading: false,

                init() {
                    const savedData = localStorage.getItem('hendhys_pos_cart');
                    if (savedData) {
                        try {
                            let data = JSON.parse(savedData);
                            if (data && data.items) {
                                data.items = data.items.map(item => {
                                    if (item.total === undefined || item.total === null) {
                                        item.total = Number(item.price) * Number(item.qty);
                                    }
                                    return item;
                                });
                            }
                            this.cartData = data;
                            this.amountPaid = this.cartData.grandTotal;
                        } catch (e) {
                            this.cartData = null;
                        }
                    }

                    this.$watch('paymentMethod', value => {
                        if (value === 'cash' && this.cartData && this.amountPaid < this.cartData.grandTotal) {
                            this.amountPaid = this.cartData.grandTotal;
                        }
                    });
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
                },

                async processCheckout() {
                    if (!this.cartData) return;

                    if (this.paymentMethod === 'cash' && this.amountPaid < this.cartData.grandTotal) {
                        alert('Uang pembayaran kurang!');
                        return;
                    }

                    this.isLoading = true;

                    const payload = {
                        customer_type: 'retail',
                        customer_id: null,
                        customer_name: this.cartData.customerName || '',
                        customer_phone: this.cartData.customerPhone || '',
                        subtotal: this.cartData.subtotal,
                        discount_amount: this.cartData.discount,
                        ppn_type: this.cartData.ppnType,
                        tax_amount: this.cartData.taxAmount,
                        other_costs: 0,
                        grand_total: this.cartData.grandTotal,
                        payment_method: this.paymentMethod,
                        amount_paid: this.amountPaid,
                        bank_name: this.paymentMethod === 'transfer' ? this.bankName : null,
                        reference_number: this.paymentMethod === 'transfer' ? this.refNumber : null,
                        items: this.cartData.items.map(item => ({
                            product_id: item.product_id,
                            quantity: item.qty,
                            price: item.price,
                            discount: 0,
                            total: item.total
                        }))
                    };

                    try {
                        const response = await fetch('{{ route("hendhys.pos.store") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify(payload)
                        });
                        const result = await response.json();
                        if (result.success) {
                            localStorage.removeItem('hendhys_pos_cart');
                            window.location.href = result.redirect;
                        } else {
                            alert(result.error || 'Terjadi kesalahan sistem.');
                        }
                    } catch (error) {
                        alert('Terjadi kesalahan jaringan.');
                    } finally {
                        this.isLoading = false;
                    }
                }
            }));
        });
    </script>
@endsection