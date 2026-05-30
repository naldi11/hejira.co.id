@extends('layouts.hendhys')
@section('title', 'Buat Return ke Gudang')
@section('page-title', 'Form Pengembalian Barang ke Gudang Utama')

@section('content')
<div class="max-w-4xl mx-auto" x-data="returnForm()">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('hendhys.returns-to-gudang.store') }}" method="POST">
            @csrf
            
            <div class="p-6 border-b border-gray-100 bg-[#faf7f5]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Return <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Alasan Tambahan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Opsional"
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Barang yang Diretur</h3>
                    <button type="button" @click="addItem" class="text-sm bg-amber-50 text-[#d97706] hover:bg-amber-100 px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto overflow-visible">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="pb-3 pt-2 px-2 font-medium">Bahan Baku / Produk Jadi</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-32">Kondisi</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-24">Jumlah</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-32">Satuan</th>
                                <th class="pb-3 pt-2 px-2 font-medium w-48">Catatan Detail</th>
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
                                                <option value="{{ $p->id }}">{{ $p->name }} (Stok: {{ (int)$p->current_stock }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2">
                                        <select :name="`items[${index}][condition]`" x-model="item.condition" required
                                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                            <option value="Bagus (Bisa Dipakai)">Bagus</option>
                                            <option value="Rusak / Cacat">Rusak / Cacat</option>
                                            <option value="Kadaluwarsa / Basi">Kadaluwarsa / Basi</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </td>
                                    <td class="py-3 px-2">
                                        <input type="number" step="0.001" min="0.001" :name="`items[${index}][quantity]`" x-model.number="item.qty" required placeholder="0"
                                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                    </td>
                                    <td class="py-3 px-2">
                                        <select :name="`items[${index}][unit_id]`" x-model="item.unit_id" required
                                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                                            <option value="">-- Satuan --</option>
                                            @foreach($units as $u)
                                                <option value="{{ $u->id }}">{{ $u->abbreviation }} — {{ $u->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-3 px-2">
                                        <input type="text" :name="`items[${index}][notes]`" x-model="item.notes" placeholder="Catatan item (opsional)"
                                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
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

                @if($errors->any())
                    <div class="mt-4 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-200">
                        Pastikan semua baris item terisi dengan benar dan stok mencukupi.
                    </div>
                @endif
            </div>

            <div class="p-6 border-t border-gray-100 bg-amber-50 flex justify-between items-center gap-3">
                <p class="text-sm text-amber-700 font-medium">Stok Pusat akan otomatis dikurangi setelah return ini disubmit.</p>
                <div class="flex gap-2">
                    <a href="{{ route('hendhys.returns-to-gudang.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</a>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-[#d97706] hover:bg-[#b45309] rounded-lg transition-colors shadow-sm">
                        Kirim Return ke Gudang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('returnForm', () => ({
        items: [{ id: Date.now(), product_id: '', condition: 'Bagus (Bisa Dipakai)', qty: '', unit_id: '', notes: '' }],
        
        addItem() {
            this.items.push({ id: Date.now(), product_id: '', condition: 'Bagus (Bisa Dipakai)', qty: '', unit_id: '', notes: '' });
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
