@extends($layout ?? 'layouts.hendhys')
@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', 'Master Data — ' . (isset($product) ? 'Edit Produk' : 'Tambah Produk'))

@push('styles')
    <style>
        .ts-wrapper { margin-bottom: 0 !important; }
        .ts-control {
            background-color: #f8fafc !important; /* slate-50 */
            border: 2px solid #f1f5f9 !important; /* slate-100 */
            border-radius: 1rem !important; /* 2xl */
            padding: 0.875rem 1.25rem !important;
            font-size: 0.875rem !important;
            box-shadow: none !important;
            transition: all 0.3s !important;
        }
        .ts-control.focus {
            background-color: #fff !important;
            border-color: #d97706 !important; /* amber-600 */
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.1) !important;
        }
        .ts-dropdown {
            border-radius: 1rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #e2e8f0 !important;
            padding: 0.5rem !important;
        }
        .ts-dropdown .option {
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
        }
        .ts-dropdown .active { background-color: #fef3c7 !important; color: #d97706 !important; }
    </style>
@endpush

@section('content')
    <div class="max-w-5xl mx-auto">
        <form method="POST"
            action="{{ isset($product) ? route(($routePrefix ?? 'master.') . 'products.update', $product) : route(($routePrefix ?? 'master.') . 'products.store') }}"
            class="space-y-8" enctype="multipart/form-data">
            @csrf
            @if(isset($product)) @method('PUT') @endif
            
            @php
                $scope = $currentScope ?? 'gudang';
                $isNew = !isset($product);
                $defGudang  = old('visible_gudang',  $isNew ? ($scope === 'gudang')                        : (bool)$product->visible_gudang);
                $defJihans  = old('visible_jihans',  $isNew ? in_array($scope, ['gudang','jihans'])        : (bool)$product->visible_jihans);
                $defHendhys = old('visible_hendhys', $isNew ? in_array($scope, ['gudang','hendhys'])       : (bool)$product->visible_hendhys);
            @endphp

            {{-- Main Form Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 font-headline uppercase tracking-widest">Informasi Dasar</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-tighter">Identitas dan kategori produk</p>
                </div>
                
                <div class="p-10 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Nama Produk --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nama Produk <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                                placeholder="Masukkan nama produk..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-bold text-slate-900">
                            @error('name') <p class="text-rose-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Barcode --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Barcode</label>
                            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                                placeholder="Scan barcode produk..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-mono text-sm">
                        </div>

                        {{-- Lokasi Rak --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Lokasi Rak</label>
                            <input type="text" name="rack" value="{{ old('rack', $product->rack ?? '') }}"
                                placeholder="Contoh: A-01"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Kategori --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Kategori <span class="text-rose-500">*</span></label>
                            <select name="category_id" class="select-creatable w-full">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="text-rose-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Satuan --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Satuan <span class="text-rose-500">*</span></label>
                            <select name="unit_id" class="select-creatable w-full">
                                <option value="">Pilih Satuan</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id ?? '') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                @endforeach
                            </select>
                            @error('unit_id') <p class="text-rose-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Brand --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Brand</label>
                            <select name="brand_id" class="select-creatable w-full">
                                <option value="">— Tanpa Brand —</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Image Upload --}}
                        <div x-data="{
                                preview: '{{ isset($product) && $product->image ? Storage::url($product->image) : '' }}',
                                fileName: '{{ isset($product) && $product->image ? basename($product->image) : '' }}',
                                handleFile(file) {
                                    if (!file || !file.type.startsWith('image/')) return;
                                    this.fileName = file.name;
                                    const reader = new FileReader();
                                    reader.onload = e => this.preview = e.target.result;
                                    reader.readAsDataURL(file);
                                },
                                clear() { this.preview = ''; this.fileName = ''; document.getElementById('imageInput').value = ''; }
                            }">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Gambar Produk</label>
                            <div class="relative group">
                                <input id="imageInput" type="file" name="image" accept="image/*" class="hidden"
                                    @change="handleFile($event.target.files[0])">
                                <div @click="document.getElementById('imageInput').click()" 
                                     class="w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-100 hover:border-amber-300 transition-all">
                                    <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                        <template x-if="preview">
                                            <img :src="preview" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!preview">
                                            <span class="material-symbols-outlined text-slate-300 text-[28px]">image</span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-black text-slate-700 truncate" x-text="preview ? fileName : 'Pilih Gambar'"></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Maks 2MB • JPG, PNG, WEBP</p>
                                    </div>
                                    <template x-if="preview">
                                        <button type="button" @click.stop="clear()" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center hover:bg-rose-100 transition-all">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 font-headline uppercase tracking-widest">Harga & Stok</h3>
                        <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-tighter">Konfigurasi nilai jual dan batas stok</p>
                    </div>
                </div>
                <div class="p-10">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">HPP (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="hpp" value="{{ old('hpp', $product->hpp ?? 0) }}" min="0" step="0.01" required
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="text-xs font-black text-amber-500 uppercase tracking-widest ml-1 mb-2 block">Harga Jual (Rp) <span class="text-rose-500">*</span></label>
                            <input type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" min="0" step="0.01" required
                                class="bg-amber-50 border-2 border-amber-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-black text-amber-600">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Stok Minimum <span class="text-rose-500">*</span></label>
                            <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min ?? 0) }}" min="0" step="1" required
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>
                    </div>

                    <div class="mt-12">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Harga Bertingkat (Tiering)</h4>
                                <p class="text-[11px] font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">Berikan diskon otomatis untuk pembelian grosir</p>
                            </div>
                            <button type="button" id="addTierBtn"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10">
                                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                Tambah Tier
                            </button>
                        </div>

                        <div id="tierContainer" class="space-y-3">
                            @php
                                $existingTiers = old('tiered_prices', isset($product) ? $product->tieredPrices->map(fn($t) => ['min_qty' => $t->min_qty, 'price' => $t->price])->toArray() : []);
                            @endphp
                            @forelse($existingTiers as $i => $tier)
                                <div class="tier-row flex flex-col sm:flex-row items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                                    <div class="flex-1 w-full">
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase tracking-widest">Min. Beli</span>
                                            <input type="number" name="tiered_prices[{{ $i }}][min_qty]" value="{{ $tier['min_qty'] }}" min="1" placeholder="cth: 50"
                                                class="w-full pl-24 pr-4 py-3 bg-white border-2 border-slate-100 rounded-xl focus:border-amber-500 outline-none transition-all font-bold text-slate-900 text-sm">
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full">
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase tracking-widest">Harga Rp</span>
                                            <input type="number" name="tiered_prices[{{ $i }}][price]" value="{{ $tier['price'] }}" min="0" placeholder="cth: 142000"
                                                class="w-full pl-24 pr-4 py-3 bg-white border-2 border-slate-100 rounded-xl focus:border-amber-500 outline-none transition-all font-bold text-amber-600 text-sm">
                                        </div>
                                    </div>
                                    <button type="button" onclick="this.closest('.tier-row').remove(); reindexTiers()"
                                        class="w-11 h-11 flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 transition-all shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            @empty
                                <div id="emptyTierMsg" class="py-10 text-center bg-slate-50 rounded-[2rem] border-2 border-dashed border-slate-200">
                                    <span class="material-symbols-outlined text-slate-300 text-[32px] mb-2">trending_down</span>
                                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Belum ada harga bertingkat</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Configuration Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 font-headline uppercase tracking-widest">Pajak & Konfigurasi</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-tighter">Pengaturan sistem dan visibilitas produk</p>
                </div>
                <div class="p-10 space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Tipe PPN</label>
                            <select name="ppn_type" class="select2 w-full">
                                <option value="none"  {{ old('ppn_type', $product->ppn_type ?? 'none') === 'none'    ? 'selected' : '' }}>Tanpa PPN</option>
                                <option value="include" {{ old('ppn_type', $product->ppn_type ?? '') === 'include' ? 'selected' : '' }}>Include PPN</option>
                                <option value="exclude" {{ old('ppn_type', $product->ppn_type ?? '') === 'exclude' ? 'selected' : '' }}>Exclude PPN</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Rate PPN (%)</label>
                            <input type="number" name="ppn_rate" value="{{ old('ppn_rate', $product->ppn_rate ?? 11) }}" min="0" max="100" step="0.01"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Status Produk</label>
                            <select name="status" class="select2 w-full">
                                <option value="active" {{ old('status', $product->status ?? 'active') === 'active' ? 'selected' : '' }}>Aktif (Dijual)</option>
                                <option value="discontinued" {{ old('status', $product->status ?? '') === 'discontinued' ? 'selected' : '' }}>Discontinue (Sembunyi)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Form visibility & source properties --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-slate-100 pt-10">
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Tipe Produk <span class="text-rose-500">*</span></label>
                            <select name="product_type" class="select2 w-full">
                                <option value="INV" {{ old('product_type', $product->product_type ?? 'INV') === 'INV' ? 'selected' : '' }}>Inventory (Stok Dicatat)</option>
                                <option value="NON" {{ old('product_type', $product->product_type ?? '') === 'NON' ? 'selected' : '' }}>Non-Inventory (Jasa/Biaya)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Sumber Produk <span class="text-rose-500">*</span></label>
                            <select name="source_type" class="select2 w-full">
                                <option value="purchased" {{ old('source_type', $product->source_type ?? 'purchased') === 'purchased' ? 'selected' : '' }}>Dibeli (Melalui PO Supplier)</option>
                                <option value="produced"  {{ old('source_type', $product->source_type ?? '') === 'produced' ? 'selected' : '' }}>Diproduksi Sendiri (Internal)</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-10">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-6 block">Tampilkan di Entitas</label>
                        <div class="flex flex-wrap gap-6">
                            @foreach([
                                ['visible_gudang',  'Gudang Tempua',   'warehouse',  $defGudang],
                                ['visible_jihans',  "Jihan's Food",    'storefront', $defJihans],
                                ['visible_hendhys', 'Hendhys Brownies','cake',       $defHendhys],
                            ] as [$fieldName, $label, $icon, $checked])
                                <label x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
                                    :class="on ? 'border-amber-600 bg-amber-50 text-amber-600' : 'border-slate-100 bg-slate-50 text-slate-400 hover:border-slate-200'"
                                    class="flex-1 min-w-[200px] flex flex-col items-center justify-center p-6 rounded-[2rem] border-2 cursor-pointer transition-all select-none">
                                    <input type="checkbox" name="{{ $fieldName }}" value="1" x-model="on" class="hidden">
                                    <span class="material-symbols-outlined text-[32px] mb-3" :class="on ? 'fill' : ''">{{ $icon }}</span>
                                    <span class="text-xs font-black uppercase tracking-widest">{{ $label }}</span>
                                    <div class="mt-4 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all" :class="on ? 'bg-amber-600 border-amber-600' : 'border-slate-200'">
                                        <span x-show="on" class="material-symbols-outlined text-white text-[16px] font-black">check</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-10">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Catatan Internal</label>
                        <textarea name="notes" placeholder="Tambahkan catatan khusus untuk produk ini..." rows="3"
                            class="bg-slate-50 border-2 border-slate-100 rounded-3xl px-6 py-5 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all outline-none w-full font-medium text-slate-700">{{ old('notes', $product->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center gap-4 pt-4 pb-12">
                <button type="submit"
                    class="flex-1 px-8 py-4 bg-amber-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-amber-700 transition-all shadow-xl shadow-amber-600/20 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">{{ isset($product) ? 'save' : 'add_circle' }}</span>
                    {{ isset($product) ? 'Simpan Perubahan' : 'Daftarkan Produk Baru' }}
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.index') }}"
                    class="px-10 py-4 bg-white border-2 border-slate-200 text-slate-500 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-50 transition-all flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Batal
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll('.select2').forEach((el) => {
                    new TomSelect(el, { create: false, sortField: { field: "text", direction: "asc" }, maxOptions: null });
                });
                
                document.querySelectorAll('.select-creatable').forEach((el) => {
                    new TomSelect(el, { create: true, sortField: { field: "text", direction: "asc" }, maxOptions: null, createOnBlur: true });
                });

                document.getElementById('addTierBtn').addEventListener('click', function () {
                    const emptyMsg = document.getElementById('emptyTierMsg');
                    if (emptyMsg) emptyMsg.remove();

                    const container = document.getElementById('tierContainer');
                    const index = container.querySelectorAll('.tier-row').length;
                    const row = document.createElement('div');
                    row.className = 'tier-row flex flex-col sm:flex-row items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200';
                    row.innerHTML = `
                        <div class="flex-1 w-full">
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase tracking-widest">Min. Beli</span>
                                <input type="number" name="tiered_prices[${index}][min_qty]" min="1" placeholder="cth: 50"
                                    class="w-full pl-24 pr-4 py-3 bg-white border-2 border-slate-100 rounded-xl focus:border-amber-500 outline-none transition-all font-bold text-slate-900 text-sm">
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase tracking-widest">Harga Rp</span>
                                <input type="number" name="tiered_prices[${index}][price]" min="0" placeholder="cth: 142000"
                                    class="w-full pl-24 pr-4 py-3 bg-white border-2 border-slate-100 rounded-xl focus:border-amber-500 outline-none transition-all font-bold text-amber-600 text-sm">
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.tier-row').remove(); reindexTiers()"
                            class="w-11 h-11 flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 transition-all shrink-0">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>`;
                    container.appendChild(row);
                    row.querySelector('input').focus();
                });
            });

            function reindexTiers() {
                document.querySelectorAll('.tier-row').forEach((row, i) => {
                    row.querySelectorAll('input').forEach(input => {
                        input.name = input.name.replace(/\[\d+\]/, `[${i}]`);
                    });
                });
                if (document.querySelectorAll('.tier-row').length === 0) {
                    const container = document.getElementById('tierContainer');
                    container.innerHTML = `<div id="emptyTierMsg" class="py-10 text-center bg-slate-50 rounded-[2rem] border-2 border-dashed border-slate-200">
                        <span class="material-symbols-outlined text-slate-300 text-[32px] mb-2">trending_down</span>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Belum ada harga bertingkat</p>
                    </div>`;
                }
            }
        </script>
    @endpush
@endsection
