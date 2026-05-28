@extends('layouts.gudang')
@section('title', ($po->id ? 'Edit' : 'Buat') . ' Purchase Order')
@section('page-title', $po->id ? 'Edit PO ' . $po->po_number : 'Buat Purchase Order Baru')

@php
    $formattedDetails = [];
    if ($po->id) {
        $formattedDetails = $po->details->map(fn($d) => [
            'product_id' => $d->product_id,
            'quantity'   => (int) $d->quantity_ordered,
            'unit_id'    => $d->unit_id,
            'price'      => (float) $d->price,
            'notes'      => $d->notes ?? '',
        ])->toArray();
    }
@endphp

@section('content')
<div x-data="poForm()" class="space-y-6">

    {{-- Back Button --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('gudang.po.index') }}"
           class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 font-bold transition-colors group text-sm">
            <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Kembali ke Daftar PO
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
    <div class="flex gap-3 bg-red-50 border border-red-200 rounded-2xl p-5">
        <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">error</span>
        <div>
            <p class="text-sm font-bold text-red-700 mb-1">Perbaiki kesalahan berikut:</p>
            <ul class="text-sm text-red-600 list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ $po->id ? route('gudang.po.update', $po) : route('gudang.po.store') }}"
          method="POST" class="space-y-6">
        @csrf
        @if($po->id) @method('PUT') @endif

        {{-- ═══ BAGIAN 1: Header PO ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Informasi Pesanan</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Supplier --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        Supplier / Vendor <span class="text-red-500">*</span>
                    </label>
                    <select id="supplier_select" name="supplier_id" required>
                        <option value="">Pilih supplier...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id', $po->supplier_id) == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal PO --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        Tanggal PO <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" required
                           value="{{ old('date', $po->date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none">
                </div>

                {{-- Catatan --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Instruksi pengiriman, termin pembayaran, dsb (opsional)..."
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none resize-none">{{ old('notes', $po->notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══ BAGIAN 2: Daftar Item ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Daftar Item Pesanan</h3>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Tambah Item
                </button>
            </div>

            {{-- Empty State --}}
            <div x-show="items.length === 0" class="py-16 text-center">
                <span class="material-symbols-outlined text-slate-200 text-[56px] block mb-3">shopping_cart</span>
                <p class="text-slate-400 font-bold text-sm">Belum ada item. Klik "Tambah Item" untuk mulai.</p>
            </div>

            {{-- Item Table --}}
            <div x-show="items.length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3 text-left" style="min-width:260px">Produk</th>
                            <th class="px-4 py-3 text-center" style="min-width:130px">Satuan</th>
                            <th class="px-4 py-3 text-center" style="min-width:100px">Qty</th>
                            <th class="px-4 py-3 text-right" style="min-width:160px">Harga Satuan (Rp)</th>
                            <th class="px-6 py-3 text-right" style="min-width:140px">Subtotal</th>
                            <th class="px-3 py-3 text-center" style="min-width:48px"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 transition-colors">

                                {{-- Produk --}}
                                <td class="px-6 py-3">
                                    <select :id="'product_'+index"
                                            :name="`items[${index}][product_id]`"
                                            required
                                            x-init="$nextTick(() => {
                                                let sel = $('#product_'+index);
                                                sel.select2({
                                                    placeholder: 'Pilih produk...',
                                                    width: '100%',
                                                    minimumResultsForSearch: 5
                                                }).on('select2:select', e => {
                                                    item.product_id = e.params.data.id;
                                                    onProductChange(item, index);
                                                });
                                                if (item.product_id) sel.val(item.product_id).trigger('change.select2');
                                            })"
                                            class="w-full">
                                        <option value="">Pilih produk...</option>
                                        @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Satuan --}}
                                <td class="px-4 py-3">
                                    <select :id="'unit_'+index"
                                            :name="`items[${index}][unit_id]`"
                                            required
                                            x-init="$nextTick(() => {
                                                let sel = $('#unit_'+index);
                                                sel.select2({
                                                    placeholder: 'Satuan...',
                                                    width: '100%',
                                                    minimumResultsForSearch: Infinity
                                                }).on('select2:select', e => {
                                                    item.unit_id = e.params.data.id;
                                                });
                                                if (item.unit_id) sel.val(item.unit_id).trigger('change.select2');
                                            })"
                                            class="w-full">
                                        <option value="">—</option>
                                        @foreach($units as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Qty --}}
                                <td class="px-4 py-3">
                                    <input type="number" :name="`items[${index}][quantity]`"
                                           x-model.number="item.quantity"
                                           min="1" step="1" required
                                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-center text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none tabular-nums">
                                </td>

                                {{-- Harga --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/10 transition-all">
                                        <span class="px-2.5 text-xs font-bold text-slate-400 border-r border-slate-200 bg-white py-2.5 shrink-0">Rp</span>
                                        <input type="number" :name="`items[${index}][price]`"
                                               x-model.number="item.price"
                                               min="0" step="1" required
                                               class="flex-1 px-3 py-2.5 bg-transparent text-sm font-bold text-right text-slate-900 outline-none tabular-nums">
                                    </div>
                                </td>

                                {{-- Subtotal --}}
                                <td class="px-6 py-3 text-right">
                                    <span class="font-bold text-slate-800 tabular-nums"
                                          x-text="'Rp ' + formatNum(item.quantity * item.price)"></span>
                                </td>

                                {{-- Delete --}}
                                <td class="px-3 py-3 text-center">
                                    <button type="button" @click="removeItem(index)"
                                            class="w-8 h-8 inline-flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Grand Total --}}
            <div x-show="items.length > 0" class="px-6 py-4 bg-indigo-50 border-t-2 border-indigo-200 flex items-center justify-between">
                <div class="flex items-center gap-2 text-indigo-700">
                    <span class="text-xs font-bold uppercase tracking-wider">Total Item:</span>
                    <span class="font-bold" x-text="items.length"></span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Grand Total</span>
                    <span class="text-2xl font-bold text-indigo-700 tabular-nums"
                          x-text="'Rp ' + formatNum(grandTotal())"></span>
                </div>
            </div>
        </div>

        {{-- ═══ TOMBOL SUBMIT ═══ --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('gudang.po.index') }}"
               class="px-6 py-3 text-slate-500 border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-indigo-600 text-white rounded-2xl font-bold text-sm uppercase tracking-wider hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/25 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">save</span>
                {{ $po->id ? 'Simpan Perubahan' : 'Buat Purchase Order' }}
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Init Select2 untuk supplier
    $('#supplier_select').select2({
        placeholder: 'Pilih supplier...',
        width: '100%',
        minimumResultsForSearch: 5
    });
});

function poForm() {
    return {
        items:    @json(old('items', $formattedDetails)),
        products: @json($products),

        init() {
            if (this.items.length === 0) this.addItem();
        },

        addItem() {
            this.items.push({ product_id: '', unit_id: '', quantity: 1, price: 0, notes: '' });
        },

        removeItem(index) {
            // Destroy Select2 sebelum hapus item
            const prodSel = $('#product_' + index);
            const unitSel = $('#unit_' + index);
            if (prodSel.hasClass('select2-hidden-accessible')) prodSel.select2('destroy');
            if (unitSel.hasClass('select2-hidden-accessible')) unitSel.select2('destroy');
            this.items.splice(index, 1);
        },

        onProductChange(item, index) {
            if (!item.product_id) {
                item.unit_id = '';
                item.price   = 0;
                $('#unit_' + index).val('').trigger('change.select2');
                return;
            }
            const p = this.products.find(x => x.id == item.product_id);
            if (!p) return;
            item.unit_id = p.unit_id ?? '';
            item.price   = parseFloat(p.hpp) || 0;
            if (p.unit_id) {
                $('#unit_' + index).val(p.unit_id).trigger('change.select2');
            }
        },

        grandTotal() {
            return this.items.reduce((s, i) => s + (i.quantity * i.price), 0);
        },

        formatNum(n) {
            return new Intl.NumberFormat('id-ID').format(Math.round(n));
        },
    }
}
</script>
@endpush
