@extends('layouts.hendhys')
@section('title', 'Ketersediaan Stok')
@section('page-title', $isPusat ? 'Stok Pusat & Cabang' : 'Stok ' . auth()->user()->branch->name)

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface space-y-md">

        {{-- ===== STOK PUSAT (selalu tampil) ===== --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div
                class="p-md bg-surface-container-low border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                <div class="flex items-center gap-sm">
                    @if($isPusat)
                        <span
                            class="bg-primary-container text-on-primary-container font-label-sm text-label-sm font-bold px-sm py-[2px] rounded-full">PUSAT
                            BAKERY</span>
                    @else
                        <span
                            class="bg-tertiary-container text-on-tertiary-container font-label-sm text-label-sm font-bold px-sm py-[2px] rounded-full">{{ strtoupper(auth()->user()->branch->name) }}</span>
                    @endif
                    <h2 class="font-headline-sm text-headline-sm font-bold text-on-surface">Stok Tersedia</h2>
                </div>
                <div class="flex items-center gap-sm flex-wrap">
                    <form action="{{ route('hendhys.stock.index') }}" method="GET" class="flex items-center gap-sm">
                        @if(request()->filled('branch_id'))
                            <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                        @endif
                        <div class="relative min-w-[180px]">
                            <span
                                class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk..."
                                class="w-full pl-xl pr-sm py-sm bg-surface-container border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
                        </div>
                        <button type="submit"
                            class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg hover:bg-on-primary-fixed-variant transition-colors shadow-sm">Cari</button>
                        @if(request()->filled('search'))
                            <a href="{{ route('hendhys.stock.index') }}"
                                class="text-error hover:text-error/80 font-label-sm text-[13px] ml-xs">Reset</a>
                        @endif
                    </form>

                    @if(!$isPusat)
                        <a href="{{ route('hendhys.branch-requests.index') }}"
                            class="px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-lg text-label-lg hover:bg-secondary-fixed transition-colors flex items-center gap-xs shadow-sm shadow-secondary/20">
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                            Request Stok
                        </a>
                    @endif

                    <a href="{{ route('hendhys.stock.movements') }}"
                        class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors flex items-center gap-xs shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">history</span>
                        Histori Mutasi
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold w-24">
                                Kode</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                                Produk</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">Sumber</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Stok</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Satuan
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($stocks as $stock)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ $stock->code }}</td>
                                <td class="px-md py-sm font-bold text-on-surface">{{ $stock->name }}</td>
                                <td class="px-md py-sm text-center">
                                    @if($stock->source_type === 'produced')
                                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[10px] font-bold">PRODUKSI</span>
                                    @else
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">GUDANG</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right">
                                    @php $qty = (float) $stock->current_stock; @endphp
                                    @if($qty <= 0)
                                        <span
                                            class="px-sm py-[2px] bg-error-container text-on-error-container rounded font-bold text-xs">Habis
                                            (0)</span>
                                    @elseif($qty <= 10)
                                        <span class="font-bold text-error text-base">{{ $qty }}</span>
                                    @else
                                        <span class="font-bold text-on-surface text-base">{{ $qty }}</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-on-surface-variant">{{ $stock->unit->abbreviation ?? '-' }}</td>
                                <td class="px-md py-sm text-center">
                                    @if($isPusat)
                                        @if($stock->source_type === 'produced')
                                            <a href="{{ route('hendhys.productions.create') }}" class="text-purple-600 hover:text-purple-900 flex items-center justify-center gap-1 text-xs font-bold">
                                                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                                Produksi
                                            </a>
                                        @else
                                            <a href="{{ route('hendhys.transfer-requests.create') }}" class="text-blue-600 hover:text-blue-900 flex items-center justify-center gap-1 text-xs font-bold">
                                                <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                                                Request
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('hendhys.branch-requests.index') }}" class="text-secondary hover:text-secondary-fixed flex items-center justify-center gap-1 text-xs font-bold">
                                            <span class="material-symbols-outlined text-[16px]">shopping_cart</span>
                                            Request
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-xs block">inventory_2</span>
                                    <p>Data stok tidak ditemukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($stocks->hasPages())
                <div class="p-md border-t border-outline-variant">{{ $stocks->links() }}</div>
            @endif
        </div>

        {{-- ===== STOK PER CABANG (hanya untuk Pusat) ===== --}}
        @if($isPusat)
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mt-md">
                <div
                    class="p-md bg-secondary-container border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                    <div class="flex items-center gap-sm">
                        <span
                            class="bg-surface text-on-surface font-label-sm text-label-sm font-bold px-sm py-[2px] rounded-full shadow-sm">STOK
                            CABANG</span>
                        <h2 class="font-headline-sm text-headline-sm font-bold text-on-secondary-container">
                            @if($selectedBranchId)
                                Stok - {{ $branches->firstWhere('id', $selectedBranchId)?->name ?? 'Cabang' }}
                            @else
                                Semua Cabang
                            @endif
                        </h2>
                    </div>
                    {{-- Filter Cabang --}}
                    <form action="{{ route('hendhys.stock.index') }}" method="GET" class="flex items-center gap-sm flex-wrap">
                        @if(request()->filled('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <select name="branch_id" onchange="this.form.submit()"
                            class="pl-sm pr-8 py-sm border border-outline-variant rounded-lg bg-surface-container text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                            <option value="">- Semua Cabang -</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($selectedBranchId)
                            <a href="{{ route('hendhys.stock.index') }}"
                                class="font-label-sm text-[13px] text-error hover:text-error/80">Reset Filter</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold w-24">
                                    Kode</th>
                                <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                                    Produk</th>
                                <th
                                    class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                    Stok Cabang</th>
                                <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Satuan
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container">
                            @forelse($branchStocks as $item)
                                <tr class="hover:bg-surface-container transition-colors">
                                    <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ $item->code }}</td>
                                    <td class="px-md py-sm font-bold text-on-surface">{{ $item->name }}</td>
                                    <td class="px-md py-sm text-right">
                                        @php $qty = (float) $item->current_stock; @endphp
                                        @if($qty <= 0)
                                            <span
                                                class="px-sm py-[2px] bg-error-container text-on-error-container rounded font-bold text-xs">Habis
                                                (0)</span>
                                        @elseif($qty <= 10)
                                            <span class="font-bold text-error text-base">{{ $qty }}</span>
                                        @else
                                            <span class="font-bold text-on-surface text-base">{{ $qty }}</span>
                                        @endif
                                    </td>
                                    <td class="px-md py-sm text-on-surface-variant">{{ $item->unit->abbreviation ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-xl text-center text-on-surface-variant">
                                        <span class="material-symbols-outlined text-4xl text-outline mb-xs block">inventory_2</span>
                                        <p class="font-body-md">
                                            {{ $selectedBranchId ? 'Tidak ada stok untuk cabang ini.' : 'Pilih cabang untuk melihat stok.' }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($branchStocks) && $branchStocks->hasPages())
                    <div class="p-md border-t border-outline-variant">
                        {{ $branchStocks->appends(request()->except('branch_page'))->links() }}</div>
                @endif
            </div>
        @endif

    </div>
@endsection