@extends($layout ?? 'layouts.gudang')
@section('title', 'Brand')
@section('page-title', 'Master Data — Brand')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-background">Brand</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ count($brands) }} brand terdaftar</p>
            </div>
            <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Brand
            </button>
        </div>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                                Brand</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Scope
                            </th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">
                                Produk</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody x-data="{}">
                        @forelse($brands as $brand)
                            <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors group"
                                x-data="{ editOpen: false }">
                                <td class="px-md py-sm font-label-lg text-label-lg font-bold text-on-surface">{{ $brand->name }}
                                </td>
                                <td class="px-md py-sm">
                                    <div class="flex flex-wrap gap-xs">
                                        @if($brand->visible_gudang)  <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">warehouse</span>Gudang</span> @endif
                                        @if($brand->visible_jihans)  <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">storefront</span>Jihan's</span> @endif
                                        @if($brand->visible_hendhys) <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">cake</span>Hendhys</span> @endif
                                        @if(!$brand->visible_gudang && !$brand->visible_jihans && !$brand->visible_hendhys) <span class="inline-flex items-center px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant border border-outline-variant text-[11px]">—</span> @endif
                                    </div>
                                </td>
                                <td class="px-md py-sm text-center font-body-md text-body-md text-on-surface-variant">
                                    {{ $brand->products_count }}</td>
                                <td class="px-md py-sm text-right">
                                    <div class="flex items-center justify-end gap-sm">
                                        <button @click="editOpen = !editOpen"
                                            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high transition-colors  shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </button>
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'brands.destroy', $brand) }}"
                                            onsubmit="return confirm('Hapus brand {{ $brand->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm text-label-sm hover:bg-error-container transition-colors  shadow-sm">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                    {{-- Inline Edit --}}
                                    <div x-show="editOpen" x-cloak class="mt-sm text-left">
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'brands.update', $brand) }}"
                                            class="flex flex-wrap gap-sm items-center">
                                            @csrf @method('PUT')
                                            <input type="text" name="name" value="{{ $brand->name }}" required
                                                class="flex-1 min-w-[140px] bg-surface-container-low border-b border-primary focus:ring-0 font-body-md text-body-md text-on-surface px-sm py-xs rounded-t-sm outline-none">
                                            @include('master.partials.visibility-checkboxes', ['scope' => $currentScope ?? 'gudang', 'model' => $brand, 'isNew' => false])
                                            <button type="submit"
                                                class="px-sm py-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary-fixed-variant">Simpan</button>
                                            <button type="button" @click="editOpen = false"
                                                class="px-sm py-xs bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high">Batal</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-md py-xl text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[48px] text-outline opacity-40 mb-sm block">sell</span>
                                    <p class="font-label-lg text-label-lg font-medium">Belum ada brand.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div id="modal-add"
        class="hidden fixed inset-0 bg-on-surface/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-surface-container-lowest rounded-xl shadow-2xl p-lg w-full max-w-sm mx-md">
            <div class="flex items-center justify-between mb-md">
                <h3 class="font-headline-md text-title-lg text-on-surface">Tambah Brand</h3>
                <button onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="p-xs rounded-full hover:bg-surface-container text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @php $defaultScope = ($currentScope ?? 'gudang') === 'gudang' ? 'all' : ($currentScope ?? 'all'); @endphp
            <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'brands.store') }}" class="space-y-md">
                @csrf
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Brand <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all">
                        <input type="text" name="name" required placeholder="cth: Hendhys"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tampilkan di Entitas</label>
                    @include('master.partials.visibility-checkboxes', ['scope' => $defaultScope, 'model' => null, 'isNew' => true])
                </div>
                <div class="flex gap-sm pt-xs">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-sm rounded-lg font-label-lg text-label-lg hover:bg-on-primary-fixed-variant transition-all">Tambah</button>
                    <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                        class="flex-1 border border-outline-variant text-on-surface-variant py-sm rounded-lg font-label-lg text-label-lg hover:bg-surface-container transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection
