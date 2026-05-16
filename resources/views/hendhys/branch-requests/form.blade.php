@extends('layouts.hendhys')
@section('title', 'Buat Request ke Pusat')
@section('page-title', 'Form Pengajuan Stok ke Pusat')

@section('content')
<div class="max-w-4xl mx-auto" x-data="branchRequestForm()">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('hendhys.branch-requests.store') }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-gray-100 bg-[#faf7f5]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Request <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Misal: Stok Bolu habis"
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Produk Jadi</h3>
                    <button type="button" @click="addItem" class="text-sm bg-amber-50 text-[#d97706] hover:bg-amber-100 px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto overflow-visible">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="pb-3 pt-2 px-2 font-medium">Produk Bakery</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-32">Kuantitas</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-32">Satuan</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-3 px-2">
                                        <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                            <option value="">-- Pilih Produk --</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2">
                                        <input type="number" step="0.01" min="0.01" :name="`items[${index}][quantity]`" x-model="item.qty" required placeholder="0.00"
                                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                    </td>
                                    <td class="py-3 px-2">
                                        <select :name="`items[${index}][unit_id]`" x-model="item.unit_id" required
                                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                            @foreach($units as $u)
                                                <option value="{{ $u->id }}">{{ $u->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <a href="{{ route('hendhys.branch-requests.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-[#d97706] hover:bg-[#b45309] rounded-lg transition-colors shadow-sm">
                    Ajukan ke Pusat
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('branchRequestForm', () => ({
        items: [{ id: Date.now(), product_id: '', qty: '', unit_id: '' }],
        
        addItem() {
            this.items.push({ id: Date.now(), product_id: '', qty: '', unit_id: '' });
        },
        
        removeItem(index) {
            if(this.items.length > 1) {
                this.items.splice(index, 1);
            }
        }
    }))
})
</script>
@endsection
