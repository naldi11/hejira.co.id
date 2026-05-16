@extends('layouts.gudang')
@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', 'Master Data — ' . (isset($product) ? 'Edit Produk' : 'Tambah Produk'))

@section('content')
<div class="max-w-3xl mt-4">
    <form method="POST" action="{{ isset($product) ? route('master.products.update', $product) : route('master.products.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @if(isset($product)) @method('PUT') @endif

        {{-- Informasi Dasar --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informasi Dasar</p>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('name') border-red-400 @enderror">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('barcode') border-red-400 @enderror">
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Rak</label>
                    <input type="text" name="rack" value="{{ old('rack', $product->rack ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('category_id') border-red-400 @enderror">
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Satuan <span class="text-red-500">*</span></label>
                    <select name="unit_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('unit_id') border-red-400 @enderror">
                        <option value="">Pilih Satuan</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id ?? '') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <select name="brand_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <option value="">— Tanpa Brand —</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis <span class="text-red-500">*</span></label>
                    <select name="jenis" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('jenis') border-red-400 @enderror">
                        <option value="">Pilih Jenis</option>
                        @foreach(['frozen','tortilla','bakery','bahan_baku','aksesoris','minuman','snack','selai','property','lainnya'] as $j)
                        <option value="{{ $j }}" {{ old('jenis', $product->jenis ?? '') === $j ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$j)) }}</option>
                        @endforeach
                    </select>
                    @error('jenis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Harga & Stok --}}
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Harga & Stok</p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HPP (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="hpp" value="{{ old('hpp', $product->hpp ?? 0) }}" min="0" step="0.01" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" min="0" step="0.01" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stok Minimum <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min ?? 0) }}" min="0" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Pajak & Konfigurasi --}}
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Pajak & Konfigurasi</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe PPN</label>
                    <select name="ppn_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                        @foreach(['none' => 'Tanpa PPN', 'include' => 'Include PPN', 'exclude' => 'Exclude PPN'] as $val => $label)
                        <option value="{{ $val }}" {{ old('ppn_type', $product->ppn_type ?? 'none') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rate PPN (%)</label>
                    <input type="number" name="ppn_rate" value="{{ old('ppn_rate', $product->ppn_rate ?? 11) }}" min="0" max="100" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Produk</label>
                    <select name="product_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                        <option value="INV" {{ old('product_type', $product->product_type ?? 'INV') === 'INV' ? 'selected' : '' }}>INV — Inventory Tracked</option>
                        <option value="NON" {{ old('product_type', $product->product_type ?? '') === 'NON' ? 'selected' : '' }}>NON — Non-Inventory</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scope Entitas</label>
                    <select name="entity_scope" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                        @foreach(['all' => 'Semua Entitas', 'gudang' => 'Gudang Only', 'jihans' => "Jihan's Only", 'hendhys' => 'Hendhys Only'] as $val => $label)
                        <option value="{{ $val }}" {{ old('entity_scope', $product->entity_scope ?? 'all') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
                        <option value="active"       {{ old('status', $product->status ?? 'active') === 'active'       ? 'selected' : '' }}>Aktif</option>
                        <option value="discontinued" {{ old('status', $product->status ?? '') === 'discontinued' ? 'selected' : '' }}>Discontinue</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes', $product->notes ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                {{ isset($product) ? 'Simpan Perubahan' : 'Tambah Produk' }}
            </button>
            <a href="{{ route('master.products.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
