@extends('layouts.hendhys')
@section('title', 'Buat Request ke Pusat')
@section('page-title', 'Form Pengajuan Stok ke Pusat')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md" x-data="branchRequestForm()">

        <div class="flex items-center justify-between">
            <a href="{{ route('hendhys.branch-requests.index') }}"
                class="text-on-surface-variant hover:text-on-surface font-medium text-sm flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar
            </a>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden mb-lg">
            <form action="{{ route('hendhys.branch-requests.store') }}" method="POST" id="requestForm">
                @csrf

                <div class="p-md border-b border-outline-variant bg-surface-container-low">
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">feed</span>
                        Informasi Request
                    </h3>
                </div>

                <div class="p-md bg-surface-container-lowest">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">Tanggal
                                Request <span class="text-error">*</span></label>
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-lg text-on-surface px-sm py-sm">
                            @error('date') <p class="text-error font-body-sm text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">Catatan
                                Tambahan</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Misal: Stok Bolu habis"
                                class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-lg text-on-surface px-sm py-sm">
                            @error('notes') <p class="text-error font-body-sm text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest border-t border-outline-variant">
                    <div
                        class="p-md border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
                        <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">inventory_2</span>
                            Daftar Produk Diminta
                        </h3>
                        <button type="button" @click="addItem"
                            class="inline-flex items-center gap-xs px-md py-xs bg-primary-container text-on-primary-container font-label-sm text-[12px] font-bold rounded-full hover:opacity-90 transition-opacity">
                            <span class="material-symbols-outlined text-[16px]">add</span> Tambah Baris
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-outline-variant">
                                    <th
                                        class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-1/3">
                                        Produk Bakery</th>
                                    <th
                                        class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-40 text-center">
                                        Kuantitas</th>
                                    <th
                                        class="px-md py-sm font-label-md text-label-md text-on-surface-variant font-semibold w-40">
                                        Satuan</th>
                                    <th class="px-xs py-sm w-16 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container">
                                <template x-for="(item, index) in items" :key="item.id">
                                    <tr class="hover:bg-surface-container transition-colors items-start">
                                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                        <input type="hidden" :name="`items[${index}][unit_id]`" :value="item.unit_id">

                                        <td class="px-md py-sm align-top align-middle">
                                            <div wire:ignore>
                                                <select x-model="item.product_id" @change="updateItemDetails(index)"
                                                    required x-init="$nextTick(() => { 
                                                        let ts = new TomSelect($el, {
                                                            create: false,
                                                            placeholder: '-- Pilih Produk --',
                                                            onChange: function(value) {
                                                                item.product_id = value;
                                                                $el.dispatchEvent(new Event('change'));
                                                            }
                                                        });
                                                    })"
                                                    class="w-full border-b border-outline-variant focus:border-primary focus:ring-0 bg-transparent text-sm py-1 max-w-[300px]">
                                                    <option value="">-- Pilih Produk --</option>
                                                    @foreach($products as $p)
                                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>

                                        <td class="px-md py-sm align-middle text-center">
                                            <input type="number" step="1" min="1" :name="`items[${index}][quantity]`"
                                                x-model.number="item.qty" required placeholder="1"
                                                @change="item.qty = Math.max(1, Math.round(item.qty || 1))"
                                                class="w-[100px] text-center text-sm border border-outline-variant rounded-md focus:border-primary focus:ring-0 bg-surface-container-lowest font-bold text-on-surface mx-auto block"
                                                :disabled="!item.product_id">
                                        </td>

                                        <td class="px-md py-sm align-middle">
                                            <span class="text-sm text-on-surface-variant font-bold"
                                                x-text="item.unit_code"></span>
                                        </td>

                                        <td class="px-xs py-sm text-center align-middle">
                                            <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                                class="text-error hover:bg-error-container p-1 rounded-full transition-colors flex items-center justify-center">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-md border-t border-outline-variant bg-surface-container-low flex justify-end gap-3">
                    <a href="{{ route('hendhys.branch-requests.index') }}"
                        class="px-5 py-2.5 text-sm font-medium text-on-surface-variant bg-surface border border-outline-variant rounded-lg hover:bg-surface-container transition-colors shadow-sm">Batal</a>
                    <button type="button" @click="submitForm"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-on-primary-fixed-variant rounded-lg transition-colors shadow-sm">
                        Ajukan ke Pusat
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
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
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('branchRequestForm', () => ({
                    products: {{ Js::from($products->load('unit')) }},
                    items: [{ id: Date.now(), product_id: '', qty: '', unit_id: '', unit_code: '-' }],

                    addItem() {
                        this.items.push({ id: Date.now(), product_id: '', qty: '', unit_id: '', unit_code: '-' });
                    },

                    removeItem(index) {
                        if (this.items.length > 1) {
                            this.items.splice(index, 1);
                        }
                    },

                    updateItemDetails(index) {
                        const item = this.items[index];
                        if (item.product_id) {
                            const product = this.products.find(p => p.id == item.product_id);
                            if (product) {
                                item.unit_id = product.unit_id;
                                item.unit_code = product.unit ? product.unit.abbreviation : '-';
                            }
                        } else {
                            item.unit_id = '';
                            item.unit_code = '-';
                        }
                    },

                    submitForm() {
                        const form = document.getElementById('requestForm');
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

                            if (!item.qty || parseFloat(item.qty) <= 0) {
                                alert('Tentukan kuantitas kirim pada semua baris yang aktif.');
                                return;
                            }
                        }

                        form.submit();
                    }
                }))
            })
        </script>
    @endpush
@endsection