@extends('layouts.hendhys')
@section('title', 'Distribusi Manual ke Cabang')
@section('page-title', 'Form Distribusi Manual ke Cabang')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md" x-data="manualTransferForm({
                         products: {{ Js::from($products) }}
                     })">

        <div class="flex items-center justify-between">
            <a href="{{ route('hendhys.transfer-to-branch.index') }}"
                class="text-on-surface-variant hover:text-on-surface font-medium text-sm flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar
            </a>
        </div>

        @if($errors->any())
        <div class="bg-error-container border border-error/30 text-on-error-container rounded-xl p-md flex items-start gap-sm mb-md">
            <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
            <div>
                <p class="font-label-lg text-label-lg font-bold mb-xs">Ada kesalahan pada form:</p>
                <ul class="list-disc list-inside space-y-xs font-body-sm text-body-sm">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('hendhys.transfer-to-branch.store') }}" method="POST" id="transferForm">
            @csrf

            <div
                class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden mb-md">
                <div class="p-md border-b border-outline-variant bg-surface-container-low">
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">local_shipping</span>
                        Informasi Pengiriman
                    </h3>
                </div>

                <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">
                    <!-- Cabang Tujuan -->
                    <div>
                        <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">Cabang
                            Tujuan <span class="text-error">*</span></label>
                        <select name="branch_id" required
                            class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-lg text-on-surface px-sm py-sm">
                            <option value="">Pilih Cabang Tujuan...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id') <p class="text-error font-body-sm text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Tanggal Pengiriman -->
                    <div>
                        <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">Tanggal
                            Pengiriman <span class="text-error">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                            class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-lg text-on-surface px-sm py-sm">
                        @error('date') <p class="text-error font-body-sm text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Catatan -->
                    <div class="md:col-span-2">
                        <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">Catatan
                            Pengiriman</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            placeholder="Misal: Dikirim cepat lewat Driver A"
                            class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-lg text-on-surface px-sm py-sm">
                        @error('notes') <p class="text-error font-body-sm text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Rincian Produk -->
            <div
                class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden mb-lg">
                <div
                    class="p-md border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">inventory_2</span>
                        Daftar Produk yang Dikirim
                    </h3>
                    <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-xs px-md py-xs bg-primary-container text-on-primary-container font-label-sm text-[12px] font-bold rounded-full hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[16px]">add</span> Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto"
                     :class="items.length >= 10 ? 'overflow-y-auto' : 'overflow-y-visible'"
                     :style="items.length >= 10 ? 'max-height: 620px;' : ''">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th
                                    class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-1/3">
                                    Produk</th>
                                <th
                                    class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-32">
                                    Stok Pusat</th>
                                <th
                                    class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold text-right w-40">
                                    Kuantitas Kirim</th>
                                <th
                                    class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-32">
                                    Satuan</th>
                                <th class="px-xs py-sm w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container" id="items-container">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="hover:bg-surface-container transition-colors items-start">
                                    <!-- Hidden inputs for form submission -->
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${index}][unit_id]`" :value="item.unit_id">

                                    <td class="px-md py-sm align-top align-middle">
                                        <div wire:ignore>
                                            <select x-model="item.product_id" @change="updateItemDetails(index)" required
                                                x-init="$nextTick(() => { 
                                                        let ts = new TomSelect($el, {
                                                            create: false,
                                                            placeholder: 'Pilih Produk...',
                                                            onChange: function(value) {
                                                                item.product_id = value;
                                                                $el.dispatchEvent(new Event('change'));
                                                            }
                                                        });
                                                    })"
                                                class="w-full border-b border-outline-variant focus:border-primary focus:ring-0 bg-transparent text-sm py-1 max-w-[300px]">
                                                <option value="">Pilih Produk...</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>

                                    <td class="px-md py-sm align-middle">
                                        <div class="text-sm font-bold text-on-surface-variant"
                                            x-text="item.max_stock > 0 ? item.max_stock : '-'"></div>
                                    </td>

                                    <td class="px-md py-sm align-middle">
                                        <input type="number" step="0.01" min="0.01" :max="item.max_stock"
                                            :name="`items[${index}][quantity]`"
                                            x-model="item.quantity" required
                                            class="w-full text-right text-sm border border-outline-variant rounded-md focus:border-primary focus:ring-0 bg-surface-container-lowest font-bold text-on-surface"
                                            :disabled="!item.product_id">
                                    </td>

                                    <td class="px-md py-sm align-middle">
                                        <span class="text-sm text-on-surface-variant font-bold"
                                            x-text="item.unit_code"></span>
                                    </td>

                                    <td class="px-xs py-sm text-center align-middle">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-error hover:bg-error-container p-1 rounded-full transition-colors flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="5" class="p-xl text-center text-on-surface-variant text-sm">
                                    Belum ada item yang ditambahkan. Klik <strong>Tambah Baris</strong>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-md border-t border-outline-variant bg-surface-container-low flex justify-end gap-3">
                    <a href="{{ route('hendhys.transfer-to-branch.index') }}"
                        class="px-5 py-2.5 text-sm font-medium text-on-surface-variant bg-surface border border-outline-variant rounded-lg hover:bg-surface-container transition-colors shadow-sm">Batal</a>
                    <button type="button" @click="submitForm"
                        class="px-5 py-2.5 text-sm font-bold text-on-primary bg-primary border hover:bg-on-primary-fixed-variant rounded-lg transition-colors shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span> Kirim ke Cabang
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control {
            border-radius: 0.5rem;
            background: var(--color-surface-container-lowest);
            border-color: var(--color-outline-variant);
        }

        .ts-control.focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 1px var(--color-primary);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('manualTransferForm', ({ products }) => ({
                products: products,
                items: [],
                idCounter: 0,

                init() {
                    this.addItem();
                },

                addItem() {
                    this.items.push({
                        id: this.idCounter++,
                        product_id: '',
                        unit_id: '',
                        unit_code: '-',
                        max_stock: 0,
                        quantity: ''
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                updateItemDetails(index) {
                    const item = this.items[index];
                    if (item.product_id) {
                        const product = this.products.find(p => p.id == item.product_id);
                        if (product) {
                            item.unit_id = product.unit_id;
                            item.unit_code = product.unit ? product.unit.abbreviation : '-';
                            item.max_stock = parseFloat(product.current_stock);
                        }
                    } else {
                        item.unit_id = '';
                        item.unit_code = '-';
                        item.max_stock = 0;
                        item.quantity = '';
                    }
                },

                submitForm() {
                    if (this.items.length === 0) {
                        alert('Tambahkan minimal satu item untuk dikirim!');
                        return;
                    }

                    const form = document.getElementById('transferForm');
                    if (!form.reportValidity()) return;

                    let selected = new Set();
                    for (let item of this.items) {
                        if (!item.product_id) {
                            alert('Silakan pilih produk pada semua baris yang aktif.');
                            return;
                        }
                        if (selected.has(item.product_id)) {
                            alert('Produk tidak boleh duplikat di beberapa baris.');
                            return;
                        }
                        selected.add(item.product_id);

                        if (!item.quantity || parseFloat(item.quantity) <= 0) {
                            alert('Tentukan kuantitas kirim pada semua baris yang aktif.');
                            return;
                        }
                        if (parseFloat(item.quantity) > item.max_stock) {
                            alert(`Stok produk tidak mencukupi!\nMaksimal stok: ${item.max_stock}`);
                            return;
                        }
                    }

                    if (confirm('Kirim distribusi manual ini ke cabang? Stok Pusat otomatis terpotong saat ini juga.')) {
                        form.submit();
                    }
                }
            }));
        });
    </script>
@endpush