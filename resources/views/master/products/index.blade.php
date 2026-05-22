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
            <a href="{{ route(($routePrefix ?? 'master.') . 'products.create') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Produk
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-sm mb-lg">
            <div class="relative flex-1 min-w-[180px]">
                <span
                    class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode..."
                    class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
            </div>
            
            <select name="entity_scope"
                class="px-sm py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Entitas</option>
                <option value="gudang" {{ request('entity_scope') === 'gudang' ? 'selected' : '' }}>Gudang</option>
                <option value="jihans" {{ request('entity_scope') === 'jihans' ? 'selected' : '' }}>Jihan's</option>
                <option value="hendhys" {{ request('entity_scope') === 'hendhys' ? 'selected' : '' }}>Hendhys</option>
                <option value="all" {{ request('entity_scope') === 'all' ? 'selected' : '' }}>Semua</option>
            </select>
            <select name="status"
                class="px-sm py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinue</option>
            </select>
            <button type="submit"
                class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors ">
                <span class="material-symbols-outlined text-[18px] align-middle">filter_list</span>
                Filter
            </button>
            @if(request()->hasAny(['search', 'entity_scope', 'status']))
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
                                    <div class="flex items-center justify-end gap-sm">
                                        <a href="{{ route(($routePrefix ?? 'master.') . 'products.edit', $product) }}"
                                            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high transition-colors  shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </a>
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'products.destroy', $product) }}"
                                            onsubmit="return confirm('Hapus produk {{ $product->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm text-label-sm hover:bg-error-container transition-colors  shadow-sm">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                                Hapus
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
