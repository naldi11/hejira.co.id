@extends('layouts.jihans')
@section('title', 'Input Produksi Tortilla')
@section('page-title', 'Input Produksi Harian')

@section('content')
    <div class="w-full flex flex-col overflow-y-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('jihans.productions.index') }}"
                class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Data Produksi
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full flex-grow">
            <div class="p-6 border-b border-gray-100 bg-orange-50/50">
                <h2 class="text-lg font-bold text-gray-800">Catat Hasil Produksi Baru</h2>
                <p class="text-sm text-gray-500 mt-1">Stok produk jadi akan otomatis bertambah ke Inventory Jihan's setelah
                    dicatat.</p>
            </div>

            <form action="{{ route('jihans.productions.store') }}" method="POST" class="p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Produksi <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                            class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                        @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Produk Jadi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produk Tortilla <span
                                class="text-red-500">*</span></label>
                        <select name="product_id" required
                            class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                            <option value="">-- Pilih Produk Jadi --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-unit="{{ $product->unit_id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Ukuran --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ukuran <span
                                class="text-red-500">*</span></label>
                        <select name="size" required
                            class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm">
                            <option value="sedang" {{ old('size') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                            <option value="kecil" {{ old('size') === 'kecil' ? 'selected' : '' }}>Kecil</option>
                            <option value="besar" {{ old('size') === 'besar' ? 'selected' : '' }}>Besar</option>
                        </select>
                        @error('size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Kuantitas & Satuan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kuantitas Hasil (Qty) <span
                                class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="number" step="1" min="1" name="quantity_produced"
                                value="{{ old('quantity_produced') }}" required
                                class="flex-1 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm"
                                placeholder="0">

                            <select name="unit_id" required
                                class="w-32 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm bg-gray-50">
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->abbreviation }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('quantity_produced') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan (Opsional)</label>
                    <textarea name="notes" rows="3"
                        class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 shadow-sm text-sm"
                        placeholder="Misal: Batch pagi, mesin 2...">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-100 gap-3">
                    <a href="{{ route('jihans.productions.index') }}"
                        class="px-5 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Batal</a>
                    <button type="submit"
                        class="px-5 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors shadow-sm">Simpan
                        Hasil Produksi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.querySelector('select[name="product_id"]');
            const unitSelect = document.querySelector('select[name="unit_id"]');

            productSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const unitId = selectedOption.getAttribute('data-unit');

                if (unitId) {
                    unitSelect.value = unitId;
                }
            });
        });
    </script>
@endsection