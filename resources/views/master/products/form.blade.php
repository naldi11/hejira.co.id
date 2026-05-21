@extends($layout ?? 'layouts.gudang')
@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', 'Master Data — ' . (isset($product) ? 'Edit Produk' : 'Tambah Produk'))

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-wrapper {
            margin-bottom: 0 !important;
        }

        .ts-control {
            border-radius: 0.5rem;
            border-color: #dac2b6;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            box-shadow: none;
            min-height: 42px;
            display: flex;
            align-items: center;
            background-color: #fbf9f8;
        }

        .ts-control.focus {
            border-color: #6c2f00;
            box-shadow: 0 0 0 1px #6c2f00;
        }

        .ts-dropdown {
            border-radius: 0.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: #dac2b6;
            font-size: 0.875rem;
        }

        .ts-dropdown .option.selected {
            background-color: #ffdbc9;
            color: #6c2f00;
        }

        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
            background-color: #f5f3f3;
            color: #1b1c1c;
        }
    </style>
@endpush

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

        @if ($errors->any())
            <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg shadow-sm border border-error/20">
                <div class="flex items-start gap-sm">
                    <span class="material-symbols-outlined text-error mt-[2px]">error</span>
                    <div>
                        <h4 class="font-bold text-sm mb-xs">Terdapat beberapa kesalahan:</h4>
                        <ul class="list-disc pl-md text-sm text-on-error-container/90 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif


        <form method="POST"
            action="{{ isset($product) ? route(($routePrefix ?? 'master.') . 'products.update', $product) : route(($routePrefix ?? 'master.') . 'products.store') }}"
            class="space-y-lg" enctype="multipart/form-data">
            @csrf
            @if(isset($product)) @method('PUT') @endif

            {{-- Section: Informasi Dasar --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                        Informasi Dasar</h3>
                </div>
                <div class="p-md grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-md">
                    {{-- Nama Produk (full width) --}}
                    <div class="md:col-span-2 xl:col-span-3">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Produk <span
                                class="text-error">*</span></label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                                placeholder="cth: Roti Kelapa Manis"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                        @error('name') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p> @enderror
                    </div>

                    {{-- Barcode --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Barcode</label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}"
                                placeholder="Scan atau ketik barcode"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none font-mono">
                        </div>
                    </div>

                    {{-- Lokasi Rak --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Lokasi Rak</label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="rack" value="{{ old('rack', $product->rack ?? '') }}"
                                placeholder="cth: A-01"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Kategori <span
                                class="text-error">*</span></label>
                        <select name="category_id"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest @error('category_id') border-error @enderror">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Satuan --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Satuan <span
                                class="text-error">*</span></label>
                        <select name="unit_id"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest @error('unit_id') border-error @enderror">
                            <option value="">Pilih Satuan</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id ?? '') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                            @endforeach
                        </select>
                        @error('unit_id') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Brand --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Brand</label>
                        <select name="brand_id"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="">— Tanpa Brand —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>


                    {{-- Gambar Produk - Drag & Drop --}}
                    <div class="md:col-span-2 xl:col-span-1" x-data="{
                                                isDragging: false,
                                                preview: '{{ isset($product) && $product->image ? Storage::url($product->image) : '' }}',
                                                fileName: '{{ isset($product) && $product->image ? basename($product->image) : '' }}',
                                                handleFile(file) {
                                                    if (!file || !file.type.startsWith('image/')) return;
                                                    this.fileName = file.name;
                                                    const reader = new FileReader();
                                                    reader.onload = e => this.preview = e.target.result;
                                                    reader.readAsDataURL(file);
                                                    const dt = new DataTransfer();
                                                    dt.items.add(file);
                                                    document.getElementById('imageInput').files = dt.files;
                                                },
                                                clear() { this.preview = ''; this.fileName = ''; document.getElementById('imageInput').value = ''; }
                                            }">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Gambar Produk</label>
                        <input id="imageInput" type="file" name="image" accept="image/*" class="hidden"
                            @change="handleFile($event.target.files[0])">
                        <div class="border-2 rounded-xl transition-all duration-200 cursor-pointer"
                            :class="isDragging ? 'border-primary bg-primary-fixed/20' : 'border-dashed border-outline-variant hover:border-primary hover:bg-surface-container-low'"
                            @click="document.getElementById('imageInput').click()" @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])">
                            <template x-if="!preview">
                                <div class="flex flex-col items-center justify-center py-lg gap-sm text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[48px] text-outline">image</span>
                                    <p class="font-label-lg text-label-lg font-medium">Klik atau seret gambar</p>
                                    <p class="font-label-sm text-label-sm opacity-60">PNG, JPG, WEBP Â· Maks 2MB</p>
                                </div>
                            </template>
                            <template x-if="preview">
                                <div class="flex items-center gap-md p-md">
                                    <img :src="preview"
                                        class="w-20 h-20 rounded-lg object-cover border border-outline-variant shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-label-lg text-label-lg font-bold text-on-surface truncate"
                                            x-text="fileName"></p>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant mt-xs">Klik untuk
                                            ganti</p>
                                    </div>
                                    <button type="button" @click.stop="clear()"
                                        class="p-xs rounded-full hover:bg-error-container text-error transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                        @error('image') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Section: Harga & Stok --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                        Harga &amp; Stok</h3>
                </div>
                <div class="p-md grid grid-cols-1 sm:grid-cols-3 gap-md">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">HPP (Rp) <span
                                class="text-error">*</span></label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="number" name="hpp" value="{{ old('hpp', $product->hpp ?? 0) }}" min="0" step="0.01"
                                required
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Harga Jual (Rp) <span
                                class="text-error">*</span></label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="number" name="selling_price"
                                value="{{ old('selling_price', $product->selling_price ?? 0) }}" min="0" step="0.01"
                                required
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Stok Minimum <span
                                class="text-error">*</span></label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min ?? 0) }}"
                                min="0" required
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section: Pajak & Konfigurasi --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                        Pajak &amp; Konfigurasi</h3>
                </div>
                <div class="p-md grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-md">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tipe PPN</label>
                        <select name="ppn_type"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            @foreach(['none' => 'Tanpa PPN', 'include' => 'Include PPN', 'exclude' => 'Exclude PPN'] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('ppn_type', $product->ppn_type ?? 'none') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Rate PPN (%)</label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="number" name="ppn_rate" value="{{ old('ppn_rate', $product->ppn_rate ?? 11) }}"
                                min="0" max="100" step="0.01"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tipe Produk</label>
                        <select name="product_type"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="INV" {{ old('product_type', $product->product_type ?? 'INV') === 'INV' ? 'selected' : '' }}>INV — Inventory Tracked</option>
                            <option value="NON" {{ old('product_type', $product->product_type ?? '') === 'NON' ? 'selected' : '' }}>NON — Non-Inventory</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Status</label>
                        <select name="status"
                            class="select2 w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest">
                            <option value="active" {{ old('status', $product->status ?? 'active') === 'active' ? 'selected' : '' }}>Dijual</option>
                            <option value="discontinued" {{ old('status', $product->status ?? '') === 'discontinued' ? 'selected' : '' }}>Tidak Dijual</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 xl:col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Catatan</label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="notes" value="{{ old('notes', $product->notes ?? '') }}"
                                placeholder="Catatan tambahan (opsional)"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-md pb-lg">
                <button type="submit"
                    class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all">
                    <span class="material-symbols-outlined text-[18px]">{{ isset($product) ? 'save' : 'add' }}</span>
                    {{ isset($product) ? 'Simpan Perubahan' : 'Tambah Produk' }}
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.index') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors ">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Batal
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll('.select2').forEach((el) => {
                    new TomSelect(el, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        maxOptions: null
                    });
                });
            });
        </script>
    @endpush
@endsection
