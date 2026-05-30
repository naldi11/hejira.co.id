@extends('layouts.jihans')
@section('title', 'POS Kasir Desktop')
@section('page-title', 'Point of Sales (Mode iPOS Profesional)')

@section('content')
    <style>
        /* Styling khusus menyerupai iPOS Desktop */
        .ipos-window {
            background-color: #f0f0f0;
            border: 2px solid #ccc;
            border-top-color: #fff;
            border-left-color: #fff;
            border-bottom-color: #999;
            border-right-color: #999;
        }

        .ipos-table th {
            background: linear-gradient(to bottom, #f9f9f9, #e0e0e0);
            border: 1px solid #ccc;
            font-size: 12px;
            color: #333;
            padding: 4px 8px;
            text-align: left;
        }

        .ipos-table td {
            border: 1px solid #ccc;
            background-color: #fff;
            font-size: 13px;
            padding: 5px 8px;
            height: 29px;
        }

        .ipos-table tbody tr.active-row td {
            background-color: #0078d7;
            color: #fff;
        }

        .ipos-input {
            border: 1px solid #999;
            border-top-color: #666;
            border-left-color: #666;
            padding: 2px 6px;
            font-size: 13px;
            width: 100%;
            background-color: #fff;
        }

        .ipos-input:focus {
            outline: none;
            background-color: #ffffcc;
        }

        .ipos-button {
            background: linear-gradient(to bottom, #f0f0f0, #d4d4d4);
            border: 1px solid #999;
            border-radius: 3px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            height: 52px;
        }

        .ipos-button:hover {
            background: linear-gradient(to bottom, #e8e8e8, #c4c4c4);
        }

        .ipos-button:active {
            background: #c4c4c4;
            border-top-color: #666;
            border-left-color: #666;
        }

        /* TomSelect iPOS Retro Styling */
        .ts-wrapper.ipos-select-ts {
            padding: 0 !important;
            border: none !important;
            width: 100% !important;
        }
        .ts-wrapper.ipos-select-ts .ts-control {
            border: 1px solid #999 !important;
            border-top-color: #666 !important;
            border-left-color: #666 !important;
            padding: 4px 6px !important;
            font-size: 13px !important;
            background-color: #fff !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        .ts-wrapper.ipos-select-ts .ts-control input {
            font-size: 13px !important;
        }
        .ts-wrapper.ipos-select-ts.focus .ts-control {
            background-color: #ffffcc !important;
            outline: none !important;
        }
        .ts-dropdown {
            font-size: 13px !important;
            border-radius: 0 !important;
            border: 1px solid #999 !important;
        }
    </style>

    <div class="w-full ipos-window p-2 flex flex-col" style="height: calc(100vh - 90px); min-height: 700px;"
        x-data="posSystem()" @keydown.window="handleGlobalKeydown($event)">

        {{-- Top Section --}}
        <div class="flex gap-4 mb-2">
            {{-- Left Form --}}
            <div class="w-1/2 bg-white border border-gray-400 p-3 grid grid-cols-[100px_1fr] gap-y-2 items-center">
                <label class="text-xs font-semibold">No. Transaksi</label>
                <input type="text" class="ipos-input bg-gray-200" value="[AUTO]" disabled>

                <label class="text-xs font-semibold">Tanggal</label>
                <input type="date" x-model="transactionDate" class="ipos-input">

                <label class="text-xs font-semibold">Pelanggan</label>
                <div class="flex gap-1 w-full" id="customer-select-wrapper">
                    <select id="customer-select" x-ref="customerSelect" class="ipos-select-ts w-full">
                        <option value="">-- Pelanggan Umum / Ketik Manual --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' | ' . $c->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="text-xs font-semibold" x-show="!customerId">Nama Manual</label>
                <input type="text" x-model="customerName" x-show="!customerId" placeholder="Ketik nama pelanggan manual..." class="ipos-input">

                <label class="text-xs font-semibold">Keterangan</label>
                <input type="text" x-model="notes" class="ipos-input">
            </div>

            {{-- Right Total Display --}}
            <div class="w-1/2 flex flex-col items-end justify-end p-4 bg-black border-[3px] border-gray-600 rounded">
                <span class="text-green-500 font-bold text-lg mb-1">TOTAL</span>
                <span class="text-green-500 font-mono font-bold text-6xl tracking-wider leading-none"
                    x-text="formatCurrency(grandTotal)"></span>
            </div>
        </div>

        {{-- Middle Section: Table --}}
        <div class="bg-white border border-gray-400 overflow-auto relative" style="flex: 1 1 0; min-height: 310px;">
            <table class="w-full ipos-table whitespace-nowrap">
                <thead>
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th class="w-24">Kode Item</th>
                        <th class="w-32">Barcode</th>
                        <th>Keterangan</th>
                        <th class="w-20 text-center">Jml</th>
                        <th class="w-20 text-center">Satuan</th>
                        <th class="w-28 text-right">Harga</th>
                        <th class="w-24 text-right">Potongan</th>
                        <th class="w-32 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in cart" :key="index">
                        <tr :class="{'active-row': selectedCartIndex === index}" @click="selectedCartIndex = index">
                            <td class="text-center" x-text="index + 1"></td>
                            <td x-text="item.product_code"></td>
                            <td x-text="item.barcode"></td>
                            <td x-text="item.product_name"></td>
                            <td class="text-center p-0">
                                <input type="number" x-model.number="item.quantity" @change="validateQuantity(index)"
                                    @focus="selectedCartIndex = index"
                                    class="w-full text-center outline-none bg-transparent" min="1" step="1"
                                    :id="'cart-qty-' + index"
                                    @keydown="handleCartInputKeydown($event, index, 'qty')">
                            </td>
                            <td class="text-center" x-text="item.unit_name"></td>
                            <td class="text-right p-0">
                                <input type="number" x-model.number="item.price" @change="validateQuantity(index)"
                                    @focus="selectedCartIndex = index" class="w-full text-right outline-none bg-transparent"
                                    min="0"
                                    :id="'cart-price-' + index"
                                    @keydown="handleCartInputKeydown($event, index, 'price')">
                            </td>
                            <td class="text-right p-0">
                                <input type="number" x-model.number="item.discount" @change="validateQuantity(index)"
                                    @focus="selectedCartIndex = index" class="w-full text-right outline-none bg-transparent"
                                    min="0"
                                    :id="'cart-discount-' + index"
                                    @keydown="handleCartInputKeydown($event, index, 'discount')">
                            </td>
                            <td class="text-right font-bold" x-text="formatCurrency(item.total)"></td>
                        </tr>
                    </template>
                    <tr x-show="cart.length === 0">
                        <td colspan="9" class="text-center text-gray-400 py-10">
                            Tekan <b class="text-gray-600">[Ins]</b> atau tombol <b>[Tambah]</b> untuk mencari item
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Bottom Section --}}
        <div class="mt-2 mb-6 pb-2 flex gap-4">
            {{-- Summaries --}}
            <div class="w-1/3 bg-white border border-gray-400 p-2 grid grid-cols-[100px_1fr] gap-y-1 items-center">
                <label class="text-xs font-semibold">Sub Total</label>
                <input type="text" class="ipos-input bg-gray-200 text-right font-bold" :value="formatCurrency(subtotal)"
                    disabled>

                <label class="text-xs font-semibold">Diskon Item</label>
                <input type="text" class="ipos-input bg-gray-200 text-right text-red-600"
                    :value="formatCurrency(discountAmount)" disabled title="Total diskon per item dari kolom Potongan">

                <label class="text-xs font-semibold">Pot. Tambahan</label>
                <input type="number" x-model.number="extraDiscount" @change="recalculateTotals()" min="0" step="1"
                    class="ipos-input text-right text-red-600 font-bold" placeholder="0">

                <label class="text-xs font-semibold">Pajak (PPN)</label>
                <select x-model="ppnType" @change="recalculateTotals()" class="ipos-input">
                    <option value="none">Tanpa PPN</option>
                    <option value="include">Include PPN (harga sdh termasuk)</option>
                    <option value="exclude">Exclude PPN (+11% dari subtotal)</option>
                </select>
                <label class="text-xs font-semibold text-gray-500" x-show="ppnType === 'include'">Info PPN</label>
                <span class="text-xs text-gray-500" x-show="ppnType === 'include'"
                    x-text="'PPN = ' + formatCurrency(subtotal - (subtotal / 1.11))"></span>
                <label class="text-xs font-semibold" x-show="ppnType === 'exclude'">Nilai PPN</label>
                <input type="text" x-show="ppnType === 'exclude'" class="ipos-input bg-gray-200 text-right"
                    :value="formatCurrency(taxAmount)" disabled>

                <label class="text-xs font-semibold text-lg">Total Akhir</label>
                <input type="text" class="ipos-input bg-yellow-100 text-right font-bold text-lg"
                    :value="formatCurrency(grandTotal)" disabled>
            </div>

            {{-- Actions --}}
            <div class="w-2/3 flex flex-wrap gap-2 items-end justify-end">
                <button class="ipos-button" @click="openSearchModal()">
                    <span class="font-bold">Tambah [Ins]</span>
                </button>
                <button class="ipos-button" @click="removeFromCart(selectedCartIndex)" :disabled="cart.length === 0 || selectedCartIndex === null">
                    <span class="font-bold text-red-600">Hapus [Del]</span>
                </button>
                <button class="ipos-button" @click="holdTransaction()" :disabled="cart.length === 0">
                    <span class="font-bold text-blue-600">Pending [F5]</span>
                </button>
                <a href="{{ route('jihans.pending.index') }}" class="ipos-button text-center no-underline">
                    <span class="font-bold text-orange-600">Daftar<br>Pending [F6]</span>
                </a>
                <div class="w-px h-12 bg-gray-400 mx-2"></div>
                <button class="ipos-button bg-green-100" @click="openPaymentModal()" :disabled="cart.length === 0">
                    <span class="font-bold text-green-700 text-lg">BAYAR [END]</span>
                </button>
            </div>
        </div>

        {{-- MODAL SEARCH ITEM --}}
        <div x-show="showSearchModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display: none;">
            <div class="bg-white rounded border border-gray-500 shadow-2xl w-[850px] flex flex-col"
                @click.away="closeSearchModal()">
                <div class="bg-blue-800 text-white px-3 py-1.5 flex justify-between items-center">
                    <span class="font-bold text-sm">Daftar Item
                        <span x-show="selectedProducts.length > 0"
                            class="ml-2 bg-yellow-400 text-blue-900 text-xs font-bold px-2 py-0.5 rounded-full"
                            x-text="selectedProducts.length + ' dipilih'"></span>
                    </span>
                    <button @click="closeSearchModal()" class="text-white hover:text-red-300 font-bold">X</button>
                </div>
                <div class="p-3 bg-gray-100 border-b border-gray-300 flex gap-2 items-center">
                    <input type="text" x-model="searchQuery" x-ref="searchInput" @keydown="handleSearchKeydown($event)"
                        placeholder="Ketik nama item atau barcode lalu tekan [Enter] atau panah [Bawah]"
                        class="ipos-input flex-1 py-1.5 px-3">
                    <span class="text-xs text-gray-500 whitespace-nowrap">Spasi = centang, Enter = tambah</span>
                </div>
                <div class="h-[400px] overflow-auto bg-white">
                    <table class="w-full ipos-table whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="w-8 text-center">
                                    <input type="checkbox" @change="toggleSelectAll($event.target.checked)" class="w-3 h-3">
                                </th>
                                <th>Kode Item</th>
                                <th>Barcode</th>
                                <th>Nama Item</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(product, index) in filteredProducts" :key="product.id">
                                <tr :class="{'active-row': selectedSearchIndex === index, 'bg-blue-50': isProductSelected(product.id)}"
                                    @click="selectedSearchIndex = index; toggleProductSelect(product)"
                                    @dblclick="selectMultipleItems()">
                                    <td class="text-center p-1">
                                        <input type="checkbox" :checked="isProductSelected(product.id)"
                                            @click.stop="toggleProductSelect(product)" class="w-3 h-3">
                                    </td>
                                    <td x-text="product.code"></td>
                                    <td x-text="product.barcode"></td>
                                    <td x-text="product.name"></td>
                                    <td class="text-center" x-text="Math.round(parseFloat(product.current_stock || 0))"></td>
                                    <td class="text-center" x-text="product.unit ? product.unit.abbreviation : ''"></td>
                                    <td class="text-right" x-text="formatCurrency(product.selling_price)"></td>
                                </tr>
                            </template>
                            <tr x-show="filteredProducts.length === 0">
                                <td colspan="7" class="text-center py-8 text-gray-500">Data tidak ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-2 bg-gray-200 border-t border-gray-400 flex justify-between items-center">
                    <span class="text-xs text-gray-600">
                        Klik = sorot &nbsp;|&nbsp; Spasi/Centang = pilih &nbsp;|&nbsp; Enter = tambah ke keranjang
                        &nbsp;|&nbsp; Dblclick = langsung tambah 1 item
                    </span>
                    <div class="flex gap-2">
                        <button @click="selectMultipleItems()"
                            :disabled="selectedProducts.length === 0"
                            :class="selectedProducts.length > 0 ? 'bg-blue-200 hover:bg-blue-300' : 'opacity-50 cursor-not-allowed'"
                            class="ipos-button inline-flex flex-row gap-1">
                        <span class="font-bold">Pilih</span>
                        <span x-show="selectedProducts.length > 0" x-text="'(' + selectedProducts.length + ')'" class="font-bold"></span>
                    </button>
                    <button @click="closeSearchModal()" class="ipos-button inline-flex flex-row gap-1">
                        <span class="font-bold text-red-600">Batal</span>
                    </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL PAYMENT --}}
        <div x-show="showPaymentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display: none;">
            <div class="bg-[#f0f0f0] border-2 border-gray-400 rounded shadow-2xl w-[400px] flex flex-col ipos-window"
                @click.away="showPaymentModal = false">
                <div class="bg-green-700 text-white px-3 py-1.5 flex justify-between items-center cursor-move">
                    <span class="font-bold text-sm">Pembayaran Tunai</span>
                    <button @click="showPaymentModal = false" class="text-white hover:text-red-300 font-bold">X</button>
                </div>
                <div class="p-4 flex flex-col gap-3">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Total Tagihan</label>
                        <input type="text" class="ipos-input text-right text-2xl font-bold text-red-600 bg-white py-2"
                            :value="formatCurrency(grandTotal)" disabled>
                    </div>

                    <div>
                        <label class="text-xs font-semibold block mb-1.5">Nominal Cepat (Quick Cash)</label>
                        <div class="grid grid-cols-3 gap-1 mb-1">
                            <button type="button" @click="amountPaid = grandTotal"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">Uang Pas</button>
                            <button type="button" @click="amountPaid = 10000"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">10.000</button>
                            <button type="button" @click="amountPaid = 20000"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">20.000</button>
                        </div>
                        <div class="grid grid-cols-3 gap-1">
                            <button type="button" @click="amountPaid = 50000"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">50.000</button>
                            <button type="button" @click="amountPaid = 100000"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">100.000</button>
                            <button type="button" @click="amountPaid = 200000"
                                class="bg-gray-200 hover:bg-gray-300 text-xs font-bold py-2 border border-gray-400 rounded">200.000</button>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold block mb-1">Nominal Diterima [F8]</label>
                        <input type="number" x-model.number="amountPaid" x-ref="amountInput"
                            @keydown.enter="processTransaction()"
                            class="ipos-input text-right text-2xl font-bold text-blue-600 py-2">
                    </div>
                    <div class="text-right py-1">
                        <span class="text-xs font-bold text-gray-600">Kembali: </span>
                        <span class="text-2xl font-bold text-green-600"
                            x-text="amountPaid > grandTotal ? formatCurrency(amountPaid - grandTotal) : 'Rp 0'"></span>
                    </div>
                </div>
                <div class="p-2 bg-gray-200 border-t border-gray-400 text-right flex justify-end gap-2">
                    <button @click="showPaymentModal = false" class="ipos-button bg-gray-300 hover:bg-gray-400">
                        <span class="font-bold text-red-600">Batal [Esc]</span>
                    </button>
                    <button @click="processTransaction()" :disabled="isProcessing"
                        class="ipos-button bg-green-200 hover:bg-green-300">
                        <span x-show="!isProcessing" class="font-bold text-green-700">Simpan & Cetak [Enter]</span>
                        <span x-show="isProcessing" class="font-bold">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posSystem', () => ({
                products: @json($products),
                customers: @json($customers),
                cart: [],
                customerType: 'Pelanggan Retail',
                customerId: '',
                customerName: '',
                notes: '',

                subtotal: 0,
                discountAmount: 0,
                extraDiscount: 0,
                ppnType: 'none',
                taxAmount: 0,
                otherCosts: 0,
                grandTotal: 0,
                transactionDate: '{{ date('Y-m-d') }}',

                selectedCartIndex: null,

                // Search Modal
                showSearchModal: false,
                searchQuery: '',
                selectedSearchIndex: 0,
                selectedProducts: [],  // multi-select

                // Payment Modal
                showPaymentModal: false,
                paymentMethods: @json($paymentMethods),
                paymentMethodId: '',
                selectedPaymentMethod: null,
                amountPaid: 0,
                referenceNumber: '',
                isProcessing: false,

                 init() {
                    // Inisialisasi TomSelect untuk Pelanggan Jihans
                    this.$nextTick(() => {
                        const selectEl = document.getElementById('customer-select');
                        if (selectEl) {
                            const ts = new TomSelect(selectEl, {
                                create: false,
                                placeholder: "-- Pelanggan Umum / Ketik Manual --",
                                allowEmptyOption: true,
                                sortField: {
                                    field: "text",
                                    direction: "asc"
                                },
                                onChange: (value) => {
                                    this.customerId = value;
                                    const client = this.customers.find(c => c.id == value);
                                    this.customerName = client ? client.name : '';
                                }
                            });

                            // Jika ada perubahan customerId dari luar (misal resume pending)
                            this.$watch('customerId', (newVal) => {
                                if (ts.getValue() !== newVal) {
                                    ts.setValue(newVal);
                                }
                            });
                        }
                    });

                    if (this.paymentMethods.length > 0) {
                        const cashMethod = this.paymentMethods.find(pm => 
                            (!pm.bank_name && !pm.account_number) || 
                            pm.name.toLowerCase().includes('tunai') || 
                            pm.name.toLowerCase().includes('cash')
                        );
                        this.paymentMethodId = cashMethod ? cashMethod.id : this.paymentMethods[0].id;
                        this.updatePaymentMethodInfo();
                    }

                    const resumeData = localStorage.getItem('jihans_resume_cart');
                    if (resumeData) {
                        try {
                            const data = JSON.parse(resumeData);
                            this.cart = data.items || [];
                            this.customerType = data.customerType || 'Pelanggan Retail';
                            this.customerId = data.customerId || '';
                            this.notes = data.notes || '';
                            this.recalculateTotals();
                            localStorage.removeItem('jihans_resume_cart');
                        } catch (e) { }
                    }
                },

                updatePaymentMethodInfo() {
                    this.selectedPaymentMethod = this.paymentMethods.find(pm => pm.id == this.paymentMethodId);
                },

                // Tipe unik dari master pelanggan (dinamis dari data)
                get customerTypes() {
                    const types = [...new Set(this.customers.map(c => c.type))];
                    return types.map(t => ({
                        value: t,
                        label: t
                    }));
                },

                // Pelanggan difilter berdasarkan tipe yang dipilih
                get filteredCustomers() {
                    if (!this.customerType) return [];
                    return this.customers.filter(c => c.type === this.customerType);
                },

                get filteredProducts() {
                    if (this.searchQuery === '') return this.products;
                    const query = this.searchQuery.toLowerCase();
                    return this.products.filter(p =>
                        p.name.toLowerCase().includes(query) ||
                        (p.code && p.code.toLowerCase().includes(query)) ||
                        (p.barcode && p.barcode.toLowerCase().includes(query))
                    );
                },

                handleGlobalKeydown(e) {
                    // Ignore if in inputs that are not body unless specific keys
                    const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);

                    if (e.key === 'Insert') {
                        e.preventDefault();
                        this.openSearchModal();
                    } else if (e.key === 'Delete' && !this.showSearchModal && !this.showPaymentModal && this.selectedCartIndex !== null) {
                        e.preventDefault();
                        this.removeFromCart(this.selectedCartIndex);
                    } else if (e.key === 'End' && !this.showSearchModal && !this.showPaymentModal) {
                        e.preventDefault();
                        this.openPaymentModal();
                    } else if (e.key === 'F5') {
                        e.preventDefault();
                        this.holdTransaction();
                    } else if (e.key === 'F6') {
                        e.preventDefault();
                        window.location.href = "{{ route('jihans.pending.index') }}";
                    } else if (e.key === 'F8') {
                        e.preventDefault();
                        if (this.showPaymentModal) {
                            setTimeout(() => this.$refs.amountInput.focus(), 100);
                        } else {
                            this.openPaymentModal();
                        }
                    }
                },

                openSearchModal() {
                    this.showSearchModal = true;
                    this.searchQuery = '';
                    this.selectedSearchIndex = 0;
                    this.selectedProducts = [];
                    setTimeout(() => {
                        this.$refs.searchInput.focus();
                    }, 100);
                },

                closeSearchModal() {
                    this.showSearchModal = false;
                },

                 handleSearchKeydown(e) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (this.selectedSearchIndex < this.filteredProducts.length - 1) {
                            this.selectedSearchIndex++;
                            this.scrollToActiveRow();
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (this.selectedSearchIndex > 0) {
                            this.selectedSearchIndex--;
                            this.scrollToActiveRow();
                        }
                    } else if (e.key === ' ') {
                        // Spasi = toggle centang produk yang disorot
                        const product = this.filteredProducts[this.selectedSearchIndex];
                        if (product) { e.preventDefault(); this.toggleProductSelect(product); }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.selectedProducts.length > 0) {
                            this.selectMultipleItems();
                        } else {
                            const product = this.filteredProducts[this.selectedSearchIndex];
                            if (product) { this.selectItem(product); }
                        }
                    }
                },

                handleCartInputKeydown(e, index, field) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        const nextIndex = index + 1;
                        if (nextIndex < this.cart.length) {
                            this.selectedCartIndex = nextIndex;
                            this.focusCartInput(nextIndex, field);
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        const prevIndex = index - 1;
                        if (prevIndex >= 0) {
                            this.selectedCartIndex = prevIndex;
                            this.focusCartInput(prevIndex, field);
                        }
                    } else if (e.key === 'ArrowRight') {
                        if (field === 'qty') {
                            e.preventDefault();
                            this.focusCartInput(index, 'price');
                        } else if (field === 'price') {
                            e.preventDefault();
                            this.focusCartInput(index, 'discount');
                        }
                    } else if (e.key === 'ArrowLeft') {
                        if (field === 'discount') {
                            e.preventDefault();
                            this.focusCartInput(index, 'price');
                        } else if (field === 'price') {
                            e.preventDefault();
                            this.focusCartInput(index, 'qty');
                        }
                    }
                },

                focusCartInput(index, field) {
                    this.$nextTick(() => {
                        const el = document.getElementById(`cart-${field}-${index}`);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                    });
                },

                isProductSelected(productId) {
                    return this.selectedProducts.some(p => p.id === productId);
                },

                toggleProductSelect(product) {
                    const idx = this.selectedProducts.findIndex(p => p.id === product.id);
                    if (idx > -1) {
                        this.selectedProducts.splice(idx, 1);
                    } else {
                        this.selectedProducts.push(product);
                    }
                },

                toggleSelectAll(checked) {
                    if (checked) {
                        this.selectedProducts = [...this.filteredProducts];
                    } else {
                        this.selectedProducts = [];
                    }
                },

                selectMultipleItems() {
                    if (this.selectedProducts.length === 0) return;
                    this.selectedProducts.forEach(product => this.selectItem(product));
                    this.selectedProducts = [];
                    this.closeSearchModal();
                },

                scrollToActiveRow() {
                    // Simplistic scroll mechanism can be added here
                },

                selectItem(product) {
                    if (Number(product.current_stock) <= 0) {
                        alert('Stok produk ini masih kosong!');
                        return;
                    }

                    const existingIndex = this.cart.findIndex(item => item.product_id === product.id);
                    if (existingIndex > -1) {
                        this.cart[existingIndex].quantity++;
                        this.validateQuantity(existingIndex);
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            product_name: product.name,
                            product_code: product.code,
                            barcode: product.barcode,
                            price: parseFloat(product.selling_price) || 0,
                            quantity: 1,
                            unit_name: product.unit ? product.unit.abbreviation : '',
                            max_stock: parseFloat(product.current_stock),
                            discount: 0,
                            total: parseFloat(product.selling_price) || 0
                        });
                    }
                    this.recalculateTotals();
                    this.closeSearchModal();
                },

                
                getTieredPrice(productId, quantity) {
                    const product = this.products.find(p => p.id === productId);
                    if (!product) return 0;
                    let price = parseFloat(product.selling_price) || 0;
                    if (product.tiered_prices && product.tiered_prices.length > 0) {
                        // tiered_prices sudah di-order DESC (tertinggi ke terendah)
                        for (let i = 0; i < product.tiered_prices.length; i++) {
                            if (quantity >= parseFloat(product.tiered_prices[i].min_qty)) {
                                price = parseFloat(product.tiered_prices[i].price);
                                break; // ketemu yang tertinggi yang memenuhi
                            }
                        }
                    }
                    return price;
                },

                validateQuantity(index) {
                    const item = this.cart[index];
                    // Paksa selalu bilangan bulat, minimal 1
                    item.quantity = Math.max(1, Math.round(parseFloat(item.quantity) || 1));

                    // Tiered Pricing Auto Adjust
                    item.price = this.getTieredPrice(item.product_id, item.quantity);

                    if (item.discount > (item.price * item.quantity)) {
                        item.discount = 0;
                    }

                    item.total = (item.quantity * item.price) - item.discount;
                    this.recalculateTotals();
                },


                removeFromCart(index) {
                    if (index === null || index < 0) return;
                    this.cart.splice(index, 1);
                    this.selectedCartIndex = null;
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

                    const afterItemDiscount = this.subtotal - parseFloat(this.extraDiscount || 0);

                    if (this.ppnType === 'exclude') {
                        this.taxAmount = afterItemDiscount * 0.11;
                    } else {
                        this.taxAmount = 0;
                    }

                    // Include PPN: harga sudah termasuk PPN, grand total tidak berubah
                    this.grandTotal = Math.max(0, afterItemDiscount + this.taxAmount + parseFloat(this.otherCosts || 0));

                    if (this.paymentMethod === 'cash' && !this.showPaymentModal) {
                        this.amountPaid = this.grandTotal;
                    }
                },

                openPaymentModal() {
                    if (this.cart.length === 0) return;
                    if (this.customerType === 'Pelanggan Agen' && !this.customerId) {
                        alert('Silakan pilih data Agen terlebih dahulu.');
                        return;
                    }
                    this.amountPaid = this.grandTotal;
                    this.showPaymentModal = true;
                    setTimeout(() => {
                        this.$refs.amountInput.focus();
                        this.$refs.amountInput.select();
                    }, 100);
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
                                transaction_date: this.transactionDate,
                                customer_id: this.customerId,
                                customer_name: this.customerName,
                                customer_type: this.customerType,
                                ppn_type: this.ppnType,
                                ppn_rate: 11,
                                subtotal: this.subtotal,
                                discount_amount: this.discountAmount,
                                extra_discount: parseFloat(this.extraDiscount || 0),
                                tax_amount: this.taxAmount,
                                other_costs: this.otherCosts,
                                grand_total: this.grandTotal,
                                payment_method_id: this.paymentMethodId,
                                amount_paid: this.amountPaid,
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

                    if (this.customerType === 'Pelanggan Agen' && !this.customerId) {
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
                }
            }));
        });
    </script>
@endsection