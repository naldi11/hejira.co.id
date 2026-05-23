@extends($layout ?? 'layouts.gudang')
@section('title', 'Produk')
@section('page-title', 'Master Data — Produk')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-background">Daftar Produk</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $products->total() }} produk terdaftar
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-sm self-start sm:self-auto">
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.template') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Template Excel
                </a>
                <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
                    class="inline-flex items-center gap-sm px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-secondary-fixed-dim transition-all">
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    Import Excel
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.create') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tambah Produk
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-sm mb-lg">
            <div class="relative flex-1 min-w-[180px]">
                <span
                    class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode..."
                    class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
            </div>
            
            <select name="visibility"
                class="pl-sm pr-8 py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Visibilitas</option>
                <option value="gudang"  {{ request('visibility') === 'gudang'  ? 'selected' : '' }}>Ada di Gudang</option>
                <option value="jihans"  {{ request('visibility') === 'jihans'  ? 'selected' : '' }}>Ada di Jihan's</option>
                <option value="hendhys" {{ request('visibility') === 'hendhys' ? 'selected' : '' }}>Ada di Hendhys</option>
            </select>
            <select name="status"
                class="pl-sm pr-8 py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinue</option>
            </select>
            <button type="submit"
                class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors ">
                <span class="material-symbols-outlined text-[18px] align-middle">filter_list</span>
                Filter
            </button>
            @if(request()->hasAny(['search', 'visibility', 'status']))
                <a href="{{ route(($routePrefix ?? 'master.') . 'products.index') }}"
                    class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors  flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                    Reset
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Kode
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                                Produk</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                Kategori</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                Jenis</th>
                            
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                HPP</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Harga Jual</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">
                                Status</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($products as $product)
                            <tr class="hover:bg-surface-container-lowest/80 transition-colors group">
                                <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ $product->code }}</td>
                                <td class="px-md py-sm">
                                    <div class="flex items-center gap-sm">
                                        {{-- Product Image or Placeholder --}}
                                        <div
                                            class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container-high flex-shrink-0 flex items-center justify-center">
                                            @if($product->image)
                                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                    class="w-full h-full object-cover">
                                            @else
                                                <span class="material-symbols-outlined text-outline-variant text-[20px]">cake</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-label-lg text-label-lg font-bold text-on-surface">
                                                {{ $product->name }}</p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                {{ $product->unit->abbreviation ?? '-' }} -
                                                {{ $product->brand->name ?? 'Tanpa Brand' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-md py-sm font-body-md text-body-md text-on-surface-variant">
                                    {{ $product->category->name ?? '-' }}</td>
                                <td class="px-md py-sm">
                                    <span
                                        class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim">
                                        {{ ucwords(str_replace('_', ' ', $product->jenis)) }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-right font-body-md text-body-md text-on-surface-variant">
                                    {{ number_format($product->hpp, 0, ',', '.') }}</td>
                                <td class="px-md py-sm text-right font-label-lg text-label-lg font-bold text-primary">
                                    {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                                <td class="px-md py-sm text-center">
                                    @if($product->status === 'active')
                                        <span
                                            class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary-fixed-dim">
                                            <span class="w-1.5 h-1.5 rounded-full bg-tertiary inline-block"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container border border-error/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-error inline-block"></span>
                                            Discontinue
                                        </span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right">
                                    <div class="flex items-center justify-end gap-xs">
                                        <button type="button"
                                            title="Detail Produk"
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
                                            class="inline-flex items-center justify-center w-8 h-8 bg-surface-container border border-outline-variant text-primary rounded-lg hover:bg-primary-container transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </button>
                                        <a href="{{ route(($routePrefix ?? 'master.') . 'products.edit', $product) }}"
                                            title="Edit Produk"
                                            class="inline-flex items-center justify-center w-8 h-8 bg-surface-container border border-outline-variant text-on-surface rounded-lg hover:bg-surface-container-high transition-colors shadow-sm">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </a>
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'products.destroy', $product) }}"
                                            onsubmit="return confirm('Hapus produk {{ $product->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                title="Hapus Produk"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-surface-container border border-outline-variant text-error rounded-lg hover:bg-error-container transition-colors shadow-sm">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="px-md py-xl text-center text-on-surface-variant bg-surface-container-lowest">
                                    <span
                                        class="material-symbols-outlined text-[48px] text-outline opacity-40 mb-sm block">inventory_2</span>
                                    <p class="font-label-lg text-label-lg font-medium">Tidak ada data produk.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
                <div
                    class="bg-surface-container-low border-t border-outline-variant px-md py-sm text-on-surface-variant font-label-sm text-label-sm">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

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
            scopeLabel() {
                const map = { all: 'Semua Entitas', gudang: 'Gudang Tempua', jihans: 'Jihan\'s Food', hendhys: 'Hendhys Brownies' };
                return map[this.product.entity_scope] ?? this.product.entity_scope;
            },
            init() {
                window.addEventListener('open-product-detail', (e) => {
                    this.product = e.detail;
                    this.open = true;
                });
            }
        }"
        x-show="open"
        class="fixed inset-0 z-[60] overflow-y-auto"
        style="display:none;">

        {{-- Backdrop --}}
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="open"
                x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="open = false"></div>

            {{-- Panel --}}
            <div x-show="open"
                x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-2xl bg-surface rounded-2xl shadow-2xl overflow-hidden">

                {{-- Header strip --}}
                <div class="px-lg py-md flex items-center justify-between gap-md" style="background-color:#6c2f00;">
                    <div class="flex items-center gap-md min-w-0">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center overflow-hidden shrink-0" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);">
                            <template x-if="product.image">
                                <img :src="product.image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!product.image">
                                <span class="material-symbols-outlined text-[28px]" style="color:rgba(255,255,255,0.7);">inventory_2</span>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-headline-sm text-headline-sm font-bold leading-tight truncate text-white" x-text="product.name"></h3>
                            <div class="flex items-center gap-sm mt-xs flex-wrap">
                                <span class="font-mono text-xs" style="color:rgba(255,255,255,0.65);" x-text="product.code"></span>
                                <span class="w-1 h-1 rounded-full" style="background:rgba(255,255,255,0.35);"></span>
                                <span class="font-label-sm text-label-sm" style="color:rgba(255,255,255,0.75);" x-text="product.barcode !== '-' ? 'Barcode: ' + product.barcode : 'Tanpa Barcode'"></span>
                            </div>
                        </div>
                    </div>
                    <button @click="open = false" class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-colors text-white" style="background:rgba(255,255,255,0.12);" onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-lg space-y-lg overflow-y-auto max-h-[70vh]">

                    {{-- Badges --}}
                    <div class="flex flex-wrap gap-xs">
                        <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full text-xs font-semibold"
                            :class="product.status === 'active' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary-fixed-dim' : 'bg-error-container text-on-error-container border border-error/20'">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" :class="product.status === 'active' ? 'bg-tertiary' : 'bg-error'"></span>
                            <span x-text="product.status === 'active' ? 'Dijual' : 'Tidak Dijual'"></span>
                        </span>
                        <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full text-xs font-semibold bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim" x-text="product.jenis"></span>
                        <template x-if="product.visible_gudang">
                            <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full text-xs font-semibold bg-surface-container text-on-surface-variant border border-outline-variant">
                                <span class="material-symbols-outlined text-[13px]">warehouse</span> Gudang
                            </span>
                        </template>
                        <template x-if="product.visible_jihans">
                            <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full text-xs font-semibold bg-surface-container text-on-surface-variant border border-outline-variant">
                                <span class="material-symbols-outlined text-[13px]">storefront</span> Jihan's
                            </span>
                        </template>
                        <template x-if="product.visible_hendhys">
                            <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full text-xs font-semibold bg-surface-container text-on-surface-variant border border-outline-variant">
                                <span class="material-symbols-outlined text-[13px]">cake</span> Hendhys
                            </span>
                        </template>
                    </div>

                    {{-- Info grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-sm">
                        <div class="bg-surface-container-low rounded-xl p-sm">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Kategori</p>
                            <p class="font-label-md text-label-md font-semibold text-on-surface" x-text="product.category"></p>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-sm">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Brand</p>
                            <p class="font-label-md text-label-md font-semibold text-on-surface" x-text="product.brand"></p>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-sm">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Satuan</p>
                            <p class="font-label-md text-label-md font-semibold text-on-surface" x-text="product.unit"></p>
                        </div>
                        <div class="bg-surface-container-low rounded-xl p-sm">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Lok. Rak</p>
                            <p class="font-label-md text-label-md font-semibold text-on-surface" x-text="product.rack"></p>
                        </div>
                    </div>

                    {{-- Harga --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-sm">
                        <div class="bg-surface-container-low rounded-xl p-md flex flex-col gap-xs">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider">HPP</p>
                            <p class="font-headline-sm text-headline-sm font-bold text-on-surface-variant">Rp <span x-text="product.hpp"></span></p>
                        </div>
                        <div class="sm:col-span-2 rounded-xl p-md flex flex-col gap-xs" style="background-color:#6c2f00;">
                            <p class="font-label-xs text-[10px] font-bold uppercase tracking-wider" style="color:rgba(255,255,255,0.65);">Harga Jual</p>
                            <p class="font-headline-md text-headline-md font-bold text-white">Rp <span x-text="product.price"></span></p>
                            <p class="font-label-sm text-label-sm" style="color:rgba(255,255,255,0.75);" x-text="ppnLabel()"></p>
                        </div>
                    </div>

                    {{-- Harga Bertingkat --}}
                    <template x-if="product.tiers && product.tiers.length > 0">
                        <div class="rounded-xl border border-outline-variant overflow-hidden">
                            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant flex items-center gap-sm">
                                <span class="material-symbols-outlined text-primary text-[18px]">trending_down</span>
                                <p class="font-label-lg text-label-lg font-semibold text-on-surface">Tingkatan Harga /satuan</p>
                                <span class="ml-auto text-xs text-on-surface-variant" x-text="product.tiers.length + ' tier'"></span>
                            </div>
                            <div class="divide-y divide-outline-variant/50">
                                <template x-for="(tier, i) in [...product.tiers].sort((a,b) => a.min_qty - b.min_qty)" :key="i">
                                    <div class="flex items-center px-md py-sm gap-md"
                                        :class="i === 0 ? 'bg-surface' : i % 2 === 0 ? 'bg-surface' : 'bg-surface-container-lowest'">
                                        {{-- Nomor --}}
                                        <div class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0" x-text="i + 1"></div>
                                        {{-- Qty label --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">Beli</p>
                                            <p class="font-label-lg text-label-lg font-bold text-on-surface">
                                                <span x-text="'≥ ' + tier.min_qty.toLocaleString('id-ID')"></span>
                                                <span class="font-normal text-on-surface-variant text-sm" x-text="' ' + (product.unit_abbr || 'pcs')"></span>
                                            </p>
                                        </div>
                                        {{-- Arrow --}}
                                        <span class="material-symbols-outlined text-outline text-[18px]">arrow_forward</span>
                                        {{-- Harga --}}
                                        <div class="text-right shrink-0">
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">Harga</p>
                                            <p class="font-label-lg text-label-lg font-bold text-primary" x-text="'Rp ' + tier.price.toLocaleString('id-ID')"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            {{-- Contoh kalkulasi tier terendah --}}
                            <div class="px-md py-sm bg-primary/5 border-t border-primary/20 flex items-center gap-sm">
                                <span class="material-symbols-outlined text-primary text-[16px]">info</span>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">
                                    Harga reguler: <strong class="text-primary">Rp <span x-text="product.price"></span></strong> / satuan (beli di bawah tier pertama)
                                </p>
                            </div>
                        </div>
                    </template>

                    {{-- Stok & PPN --}}
                    <div class="grid grid-cols-3 gap-sm">
                        <div class="bg-surface-container-low rounded-xl p-sm text-center">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Stok Min</p>
                            <p class="font-headline-sm text-headline-sm font-bold text-on-surface" x-text="product.stock_min"></p>
                        </div>
                        <div class="col-span-2 bg-surface-container-low rounded-xl p-sm">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Pajak (PPN)</p>
                            <p class="font-label-lg text-label-lg font-semibold text-on-surface" x-text="ppnLabel()"></p>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <template x-if="product.notes && product.notes.trim() !== ''">
                        <div class="bg-surface-container-low rounded-xl p-md border border-outline-variant/50">
                            <p class="font-label-xs text-[10px] font-bold text-outline uppercase tracking-wider mb-xs">Catatan</p>
                            <p class="font-body-md text-body-md text-on-surface leading-relaxed" x-text="product.notes"></p>
                        </div>
                    </template>

                </div>

                {{-- Footer --}}
                <div class="px-lg py-md border-t border-outline-variant flex justify-end">
                    <button @click="open = false"
                        class="inline-flex items-center gap-xs px-md py-sm bg-surface-container text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors border border-outline-variant">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" aria-hidden="true" onclick="document.getElementById('importModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-surface rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-primary-container rounded-full">
                        <span class="material-symbols-outlined text-on-primary-container">upload_file</span>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg font-medium leading-6 text-on-surface" id="modal-title">Import Data Produk</h3>
                        <div class="mt-2">
                            <p class="text-sm text-on-surface-variant">Upload file Excel (.xlsx, .xls) atau CSV yang sesuai dengan format template. Produk dengan Barcode atau Nama yang sama akan otomatis di-update.</p>
                        </div>
                    </div>
                </div>
                <form action="{{ route(($routePrefix ?? 'master.') . 'products.import') }}" method="POST" enctype="multipart/form-data" class="mt-5 sm:mt-6">
                    @csrf
                    <div class="mb-4">
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-colors">
                    </div>
                    <div class="flex gap-3 justify-end mt-4">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 bg-surface-container text-on-surface rounded-lg font-medium hover:bg-surface-container-high transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-medium hover:bg-on-primary-fixed-variant transition-colors">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
