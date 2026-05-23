@extends('layouts.gudang')
@section('title', 'Terima Barang / Buat GRN')
@section('page-title', 'Gudang — Terima Barang / Buat GRN')

@section('content')
<div class="mt-4 max-w-5xl"
     x-data="{
        items: {{ isset($po) ? $po->details->map(fn($d)=>['product_id'=>$d->product_id,'product_name'=>$d->product->name,'quantity_ordered'=>$d->quantity_ordered,'quantity_received_so_far'=>$d->quantity_received,'quantity'=>max(0,$d->quantity_ordered - $d->quantity_received),'unit_id'=>$d->unit_id,'unit_name'=>$d->unit->abbreviation,'hpp_price'=>$d->price,'total'=>0,'notes'=>''])->toJson() : '[]' }},
        products: {{ $products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'unit_id'=>$p->unit_id,'unit_name'=>$p->unit->abbreviation,'hpp'=>$p->hpp])->toJson() }},
        addItem() {
            this.items.push({ product_id:'', product_name:'', quantity_ordered:0, quantity_received_so_far:0, quantity:1, unit_id:'', unit_name:'', hpp_price:0, total:0, notes:'' });
        },
        removeItem(i) { this.items.splice(i,1); },
        onProductChange(i, productId) {
            const p = this.products.find(x=>x.id==productId);
            if (p) {
                this.items[i].product_name = p.name;
                this.items[i].unit_id = p.unit_id;
                this.items[i].unit_name = p.unit_name;
                this.items[i].hpp_price = p.hpp;
                this.calcTotal(i);
            }
        },
        calcTotal(i) {
            this.items[i].total = this.items[i].quantity * this.items[i].hpp_price;
        },
        grandTotal() { return this.items.reduce((s,i)=>s+(parseFloat(i.total)||0),0); },
        init() {
            this.items.forEach((item, i) => this.calcTotal(i));
        }
     }">

<form method="POST" action="{{ route('gudang.receiving.store') }}" class="space-y-4">
    @csrf
    
    @if(isset($po))
        <input type="hidden" name="po_id" value="{{ $po->id }}">
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex justify-between items-center mb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Informasi Penerimaan</p>
            @if(isset($po))
            <span class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded font-medium border border-indigo-100">Berdasarkan PO: {{ $po->po_number }}</span>
            @else
            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded font-medium border border-gray-200">Tanpa PO (Penerimaan Langsung)</span>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                @if(isset($po))
                    <input type="hidden" name="supplier_id" value="{{ $po->supplier_id }}">
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-700">{{ $po->supplier->name }}</div>
                @else
                    <select name="supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('supplier_id') border-red-400 @enderror">
                        <option value="">Pilih Supplier</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Terima <span class="text-red-500">*</span></label>
                <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div class="col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Catatan penerimaan atau nomor surat jalan supplier..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Item Produk yang Diterima</p>
            @if(!isset($po))
            <button type="button" @click="addItem()"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Item
            </button>
            @endif
        </div>

        @error('items') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 border-b border-gray-100">
                        <th class="pb-2 text-left w-64">Produk</th>
                        @if(isset($po))
                        <th class="pb-2 text-center w-24">Order / Sisa</th>
                        @endif
                        <th class="pb-2 text-center w-24">Qty Diterima</th>
                        <th class="pb-2 text-left w-20">Satuan</th>
                        <th class="pb-2 text-right w-32">Harga Beli</th>
                        <th class="pb-2 text-right w-32">Total</th>
                        @if(!isset($po))
                        <th class="pb-2 w-8"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-2">
                                @if(isset($po))
                                    <input type="hidden" :name="`items[${i}][product_id]`" x-model="item.product_id">
                                    <div class="text-sm font-medium text-gray-800" x-text="item.product_name"></div>
                                @else
                                    <select :name="`items[${i}][product_id]`" x-model="item.product_id"
                                            @change="onProductChange(i, item.product_id)" required
                                            class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                        <option value="">Pilih produk</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name" :selected="p.id == item.product_id"></option>
                                        </template>
                                    </select>
                                @endif
                            </td>
                            @if(isset($po))
                            <td class="py-2 px-2 text-center">
                                <span class="text-xs text-gray-500" x-text="`${item.quantity_ordered} / ${Math.max(0, item.quantity_ordered - item.quantity_received_so_far)}`"></span>
                            </td>
                            @endif
                            <td class="py-2 px-2">
                                <input type="number" :name="`items[${i}][quantity]`" x-model="item.quantity"
                                       @input="item.quantity = Math.floor(item.quantity); calcTotal(i)" min="1" step="1" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </td>
                            <td class="py-2 px-2">
                                <input type="hidden" :name="`items[${i}][unit_id]`" x-model="item.unit_id">
                                <span x-text="item.unit_name || '-'" class="text-xs text-gray-500 font-mono"></span>
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" :name="`items[${i}][hpp_price]`" x-model="item.hpp_price"
                                       @input="calcTotal(i)" min="0" step="0.01" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </td>
                            <td class="py-2 px-2 text-right text-xs font-medium text-gray-700">
                                <span x-text="'Rp '+(parseFloat(item.total)||0).toLocaleString('id-ID')"></span>
                            </td>
                            @if(!isset($po))
                            <td class="py-2 pl-2">
                                <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                            @endif
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="{{ isset($po) ? '6' : '6' }}" class="py-4 text-center text-gray-400 text-xs">Belum ada item produk.</td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-gray-200">
                    <tr>
                        <td colspan="{{ isset($po) ? '4' : '3' }}" class="pt-3 text-right text-sm font-semibold text-gray-700">Estimasi Tagihan:</td>
                        <td class="pt-3 pr-2 text-right font-bold text-gray-900">
                            <span x-text="'Rp '+grandTotal().toLocaleString('id-ID')"></span>
                        </td>
                        @if(!isset($po))<td></td>@endif
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-4">* Menerima barang akan otomatis menambah stok Gudang Utama dan mengupdate Harga Pokok (HPP) produk dengan Harga Beli yang diinput.</p>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
            Simpan Penerimaan
        </button>
        <a href="{{ route('gudang.receiving.index') }}" class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
    </div>
</form>
</div>
@endsection
