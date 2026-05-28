@extends('layouts.jihans')
@section('title', 'Buat Request Barang')
@section('page-title', 'Form Request Bahan Baku ke Gudang')

@section('content')
<div class="max-w-4xl mx-auto" x-data="transferRequestForm()">
    <div class="mb-6">
        <a href="{{ route('jihans.transfer-requests.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Daftar Request
        </a>
    </div>

    <form action="{{ route('jihans.transfer-requests.store') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @csrf
        
        <div class="p-6 border-b border-gray-100 bg-orange-50/50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Request <span class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                    @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Misal: Urgen untuk produksi besok"
                           class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="flex justify-between items-end mb-4">
                <h3 class="font-bold text-gray-800">Daftar Item Barang yang Direquest</h3>
                <button type="button" @click="addItem()" class="bg-orange-100 text-orange-700 hover:bg-orange-200 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 border-y border-gray-200">
                        <tr>
                            <th class="px-4 py-3 font-medium w-1/2">Pilih Barang (Bahan Baku / Lainnya) <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 font-medium">Qty Request <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 font-medium">Satuan <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 font-medium text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <select x-model="item.product_id" 
                                            :name="'items['+index+'][product_id]'" 
                                            @change="updateUnit(index)" 
                                            required 
                                            x-init="$nextTick(() => { 
                                                let ts = new TomSelect($el, {
                                                    create: false,
                                                    sortField: {field: 'text', direction: 'asc'},
                                                    placeholder: '-- Pilih Barang --',
                                                    onChange: function(value) {
                                                        item.product_id = value;
                                                        $el.dispatchEvent(new Event('change'));
                                                    }
                                                });
                                            })"
                                            class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-unit="{{ $product->unit_id }}">
                                                {{ $product->name }} ({{ $product->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" step="1" min="1" x-model="item.quantity" :name="'items['+index+'][quantity]'" @input="item.quantity = Math.floor(item.quantity)" required
                                           class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm" placeholder="0">
                                </td>
                                <td class="px-4 py-3">
                                    <select x-model="item.unit_id" :name="'items['+index+'][unit_id]'" required class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm bg-gray-50">
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">
                                                {{ $unit->abbreviation }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Hapus Baris">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @error('items') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50/50">
            <a href="{{ route('jihans.transfer-requests.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</a>
            <button type="submit" class="px-5 py-2.5 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Kirim Request
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('transferRequestForm', () => ({
            items: [
                { id: Date.now(), product_id: '', quantity: '', unit_id: '' }
            ],
            
            addItem() {
                this.items.push({
                    id: Date.now(),
                    product_id: '',
                    quantity: '',
                    unit_id: ''
                });
            },
            
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            
            updateUnit(index) {
                const selectElement = document.querySelector(`select[name="items[${index}][product_id]"]`);
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const unitId = selectedOption.getAttribute('data-unit');
                
                if (unitId) {
                    this.items[index].unit_id = unitId;
                }
            }
        }));
    });
</script>
@endsection
