@extends('layouts.gudang')
@section('title', isset($po) ? 'Edit PO' : 'Buat Purchase Order')
@section('page-title', 'Gudang — ' . (isset($po) ? 'Edit PO '.$po->po_number : 'Buat Purchase Order'))

@section('content')
<div class="mt-4 max-w-4xl"
     x-data="{
        items: {{ isset($po) ? $po->details->map(fn($d)=>['product_id'=>$d->product_id,'product_name'=>$d->product->name,'quantity'=>$d->quantity_ordered,'unit_id'=>$d->unit_id,'unit_name'=>$d->unit->abbreviation,'price'=>$d->price,'total'=>$d->total,'notes'=>$d->notes??''])->toJson() : '[]' }},
        products: {{ $products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'unit_id'=>$p->unit_id,'unit_name'=>$p->unit->abbreviation,'hpp'=>$p->hpp])->toJson() }},
        addItem() {
            this.items.push({ product_id:'', product_name:'', quantity:1, unit_id:'', unit_name:'', price:0, total:0, notes:'' });
        },
        removeItem(i) { this.items.splice(i,1); },
        onProductChange(i, productId) {
            const p = this.products.find(x=>x.id==productId);
            if (p) {
                this.items[i].product_name = p.name;
                this.items[i].unit_id = p.unit_id;
                this.items[i].unit_name = p.unit_name;
                this.items[i].price = p.hpp;
                this.calcTotal(i);
            }
        },
        calcTotal(i) {
            this.items[i].total = this.items[i].quantity * this.items[i].price;
        },
        grandTotal() { return this.items.reduce((s,i)=>s+(parseFloat(i.total)||0),0); }
     }">

<form method="POST" action="{{ isset($po) ? route('gudang.po.update', $po) : route('gudang.po.store') }}"
      class="space-y-4">
    @csrf
    @if(isset($po)) @method('PUT') @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informasi PO</p>
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier <span class="text-red-500">*</span></label>
                <select name="supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('supplier_id') border-red-400 @enderror">
                    <option value="">Pilih Supplier</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplier_id', $po->supplier_id??'') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal PO <span class="text-red-500">*</span></label>
                <input type="date" name="date" value="{{ old('date', isset($po) ? $po->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Tiba</label>
                <input type="date" name="expected_date" value="{{ old('expected_date', isset($po) && $po->expected_date ? $po->expected_date->format('Y-m-d') : '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes', $po->notes??'') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Item Produk</p>
            <button type="button" @click="addItem()"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Item
            </button>
        </div>

        @error('items') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 border-b border-gray-100">
                        <th class="pb-2 text-left w-64">Produk</th>
                        <th class="pb-2 text-right w-24">Qty</th>
                        <th class="pb-2 text-left w-20">Satuan</th>
                        <th class="pb-2 text-right w-32">Harga/Unit</th>
                        <th class="pb-2 text-right w-32">Total</th>
                        <th class="pb-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr class="border-b border-gray-50">
                            <td class="py-1.5 pr-2">
                                <select :name="`items[${i}][product_id]`" x-model="item.product_id"
                                        @change="onProductChange(i, item.product_id)" required
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                    <option value="">Pilih produk</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name" :selected="p.id == item.product_id"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="py-1.5 px-2">
                                <input type="number" :name="`items[${i}][quantity]`" x-model="item.quantity"
                                       @input="calcTotal(i)" min="0.001" step="0.001" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </td>
                            <td class="py-1.5 px-2">
                                <input type="hidden" :name="`items[${i}][unit_id]`" x-model="item.unit_id">
                                <span x-text="item.unit_name || '-'" class="text-xs text-gray-500 font-mono"></span>
                            </td>
                            <td class="py-1.5 px-2">
                                <input type="number" :name="`items[${i}][price]`" x-model="item.price"
                                       @input="calcTotal(i)" min="0" step="0.01" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </td>
                            <td class="py-1.5 px-2 text-right text-xs font-medium text-gray-700">
                                <span x-text="'Rp '+(parseFloat(item.total)||0).toLocaleString('id-ID')"></span>
                            </td>
                            <td class="py-1.5 pl-2">
                                <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="6" class="py-4 text-center text-gray-400 text-xs">Klik "Tambah Item" untuk menambah produk.</td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-gray-200">
                    <tr>
                        <td colspan="4" class="pt-2 text-right text-sm font-semibold text-gray-700">Grand Total:</td>
                        <td class="pt-2 pr-2 text-right font-bold text-gray-900">
                            <span x-text="'Rp '+grandTotal().toLocaleString('id-ID')"></span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
            {{ isset($po) ? 'Simpan Perubahan' : 'Buat Purchase Order' }}
        </button>
        <a href="{{ route('gudang.po.index') }}" class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
    </div>
</form>
</div>
@endsection
