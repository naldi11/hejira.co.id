@extends($layout ?? 'layouts.jihans')
@section('title', 'Daftar Produk')
@section('page-title', 'Master Data — Produk')

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-headline">Daftar Produk</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">{{ $products->total() }} produk terdaftar dalam sistem</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route(($routePrefix ?? 'master.') . 'products.template') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Template
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">upload_file</span>
                Import
            </button>
            <a href="{{ route(($routePrefix ?? 'master.') . 'products.create') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Produk
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[280px] relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode produk..."
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all outline-none text-sm">
            </div>
            
            <div class="min-w-[180px]">
                <select name="visibility"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all outline-none text-sm appearance-none cursor-pointer">
                    <option value="">Semua Visibilitas</option>
                    <option value="gudang"  {{ request('visibility') === 'gudang'  ? 'selected' : '' }}>Ada di Gudang</option>
                    <option value="jihans"  {{ request('visibility') === 'jihans'  ? 'selected' : '' }}>Ada di Jihan's</option>
                    <option value="hendhys" {{ request('visibility') === 'hendhys' ? 'selected' : '' }}>Ada di Hendhys</option>
                </select>
            </div>

            <div class="min-w-[180px]">
                <select name="status"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all outline-none text-sm appearance-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinue</option>
                </select>
            </div>

            <button type="submit"
                class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Filter
            </button>

            @if(request()->hasAny(['search', 'visibility', 'status']))
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.index') }}"
                    class="px-6 py-3 bg-rose-50 text-rose-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kode</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Info Produk</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kategori</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">HPP</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Harga Jual</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-bold text-slate-400 px-2 py-1 bg-slate-100 rounded-lg">{{ $product->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 flex-shrink-0 flex items-center justify-center border border-slate-200 group-hover:border-orange-200 transition-colors">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-slate-300 text-[24px]">inventory_2</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900">{{ $product->name }}</p>
                                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                                            {{ $product->unit->abbreviation ?? '-' }} • {{ $product->brand->name ?? 'Tanpa Brand' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl">{{ $product->category->name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-slate-500">Rp{{ number_format($product->hpp, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-black text-orange-600">Rp{{ number_format($product->selling_price, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($product->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Discontinue
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                        @click="$dispatch('open-product-detail', {
                                            code: '{{ $product->code }}',
                                            barcode: '{{ $product->barcode ?? '-' }}',
                                            name: '{{ addslashes($product->name) }}',
                                            category: '{{ addslashes($product->category->name ?? '-') }}',
                                            brand: '{{ addslashes($product->brand->name ?? 'Tanpa Brand') }}',
                                            unit: '{{ addslashes($product->unit->name ?? '-') }}',
                                            unit_abbr: '{{ $product->unit->abbreviation ?? '' }}',
                                            rack: '{{ $product->rack ?? '-' }}',
                                            jenis: '{{ ucwords(str_replace('_', ' ', $product->jenis)) }}',
                                            hpp: '{{ number_format($product->hpp, 0, ',', '.') }}',
                                            price: '{{ number_format($product->selling_price, 0, ',', '.') }}',
                                            stock_min: '{{ $product->stock_min }}',
                                            ppn_type: '{{ $product->ppn_type }}',
                                            ppn_rate: '{{ $product->ppn_rate }}',
                                            product_type: '{{ $product->product_type }}',
                                            entity_scope: '{{ $product->entity_scope }}',
                                            status: '{{ $product->status }}',
                                            notes: '{{ addslashes($product->notes ?? '') }}',
                                            image: '{{ $product->image ? asset('storage/' . $product->image) : '' }}',
                                            tiers: {{ $product->tieredPrices->map(fn($t) => ['min_qty' => (int)$t->min_qty, 'price' => (int)$t->price])->values()->toJson() }},
                                            visible_gudang: {{ $product->visible_gudang ? 'true' : 'false' }},
                                            visible_jihans: {{ $product->visible_jihans ? 'true' : 'false' }},
                                            visible_hendhys: {{ $product->visible_hendhys ? 'true' : 'false' }}
                                        })"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </button>
                                    <a href="{{ route(($routePrefix ?? 'master.') . 'products.edit', $product) }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'products.destroy', $product) }}"
                                        onsubmit="return confirm('Hapus produk {{ $product->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-all border border-slate-200">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center bg-slate-50/30">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-3xl flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-slate-300">inventory_2</span>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak ada data produk</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Product Detail Modal --}}
    <div x-data="{
            open: false,
            product: {},
            fmt(n) { return Number(n).toLocaleString('id-ID'); },
            ppnLabel() {
                const map = { none: 'Tanpa PPN', include: 'Include PPN', exclude: 'Exclude PPN' };
                return (map[this.product.ppn_type] ?? '-') + (this.product.ppn_type !== 'none' ? ' ' + this.product.ppn_rate + '%' : '');
            },
            init() {
                window.addEventListener('open-product-detail', (e) => {
                    this.product = e.detail;
                    this.open = true;
                });
            }
        }"
        x-show="open"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
        x-cloak>
        
        <div x-show="open" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            
            <div class="p-8 bg-orange-700 text-white shrink-0">
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 rounded-3xl bg-white/10 flex items-center justify-center overflow-hidden border border-white/20">
                        <template x-if="product.image">
                            <img :src="product.image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!product.image">
                            <span class="material-symbols-outlined text-[32px] text-white/50">inventory_2</span>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-orange-500 text-[10px] font-black uppercase tracking-widest rounded-lg" x-text="product.code"></span>
                            <span class="px-3 py-1 bg-white/10 text-[10px] font-black uppercase tracking-widest rounded-lg" x-text="product.barcode !== '-' ? 'Barcode: ' + product.barcode : 'Tanpa Barcode'"></span>
                        </div>
                        <h3 class="text-2xl font-black font-headline truncate" x-text="product.name"></h3>
                        <p class="text-orange-200 text-xs font-bold uppercase tracking-[0.2em] mt-1" x-text="product.category"></p>
                    </div>
                    <button @click="open = false" class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 transition-colors flex items-center justify-center">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Informasi Umum</label>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500">Brand</span>
                                    <span class="text-xs font-black text-slate-900" x-text="product.brand"></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500">Satuan</span>
                                    <span class="text-xs font-black text-slate-900" x-text="product.unit"></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500">Lokasi Rak</span>
                                    <span class="text-xs font-black text-slate-900" x-text="product.rack"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Harga & Stok</label>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500">HPP</span>
                                    <span class="text-xs font-black text-slate-900" x-text="'Rp' + product.hpp"></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-2xl border border-orange-100 text-orange-600">
                                    <span class="text-xs font-bold">Harga Jual</span>
                                    <span class="text-xs font-black" x-text="'Rp' + product.price"></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500">Stok Minimum</span>
                                    <span class="text-xs font-black text-slate-900" x-text="product.stock_min"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <template x-if="product.tiers && product.tiers.length > 0">
                    <div class="mb-8">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4">Tingkatan Harga (Tiering)</label>
                        <div class="bg-slate-50 rounded-3xl border border-slate-200 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-slate-100 border-b border-slate-200">
                                    <tr>
                                        <th class="px-5 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wider">Minimal Qty</th>
                                        <th class="px-5 py-3 text-right text-[10px] font-black text-slate-500 uppercase tracking-wider">Harga Satuan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="tier in product.tiers" :key="tier.min_qty">
                                        <tr>
                                            <td class="px-5 py-3 text-sm font-bold text-slate-700">
                                                <span x-text="'≥ ' + tier.min_qty"></span>
                                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter ml-1" x-text="product.unit_abbr"></span>
                                            </td>
                                            <td class="px-5 py-3 text-right text-sm font-black text-orange-600" x-text="'Rp' + fmt(tier.price)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <span class="material-symbols-outlined text-slate-400 mb-2">warehouse</span>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Gudang</p>
                        <span class="material-symbols-outlined text-[18px]" :class="product.visible_gudang ? 'text-emerald-500' : 'text-slate-300'" x-text="product.visible_gudang ? 'check_circle' : 'cancel'"></span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <span class="material-symbols-outlined text-slate-400 mb-2">storefront</span>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Jihan's</p>
                        <span class="material-symbols-outlined text-[18px]" :class="product.visible_jihans ? 'text-emerald-500' : 'text-slate-300'" x-text="product.visible_jihans ? 'check_circle' : 'cancel'"></span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <span class="material-symbols-outlined text-slate-400 mb-2">cake</span>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Hendhys</p>
                        <span class="material-symbols-outlined text-[18px]" :class="product.visible_hendhys ? 'text-emerald-500' : 'text-slate-300'" x-text="product.visible_hendhys ? 'check_circle' : 'cancel'"></span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <button @click="open = false" class="px-8 py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-100 transition-all">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="p-8">
                <div class="w-16 h-16 bg-orange-50 text-orange-600 rounded-3xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-[32px]">upload_file</span>
                </div>
                <h3 class="text-xl font-black text-slate-900 font-headline mb-2">Import Data Produk</h3>
                <p class="text-sm text-slate-500 mb-8 leading-relaxed">Pilih file Excel (.xlsx) yang sesuai dengan format template kami untuk mengunggah produk secara massal.</p>
                
                <form action="{{ route(($routePrefix ?? 'master.') . 'products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-8">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Pilih File</label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required 
                            class="w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl px-6 py-10 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-slate-900 file:text-white hover:bg-slate-100 transition-all cursor-pointer">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" 
                            class="flex-1 px-6 py-4 bg-white border-2 border-slate-200 text-slate-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all">Batal</button>
                        <button type="submit" 
                            class="flex-1 px-6 py-4 bg-orange-600 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">Import Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
