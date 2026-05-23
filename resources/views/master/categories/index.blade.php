@extends($layout ?? 'layouts.gudang')
@section('title', 'Kategori Produk')
@section('page-title', 'Master Data — Kategori Produk')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-background">Kategori Produk</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $categories->total() }} kategori
                    terdaftar</p>
            </div>
            <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Kategori
            </button>
        </div>

        {{-- Filter --}}
        <form method="GET" class="flex gap-sm mb-lg">
            <div class="relative flex-1 max-w-sm">
                <span
                    class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori..."
                    class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
            </div>
            <button type="submit"
                class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors  flex items-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route(($routePrefix ?? 'master.') . 'categories.index') }}"
                    class="px-sm py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors  flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                            Kategori</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Scope</th>
                        <th
                            class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">
                            Produk</th>
                        <th
                            class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody x-data="{}">
                    @forelse($categories as $cat)
                        <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors group"
                            x-data="{ editOpen: false }">
                            <td class="px-md py-sm font-label-lg text-label-lg font-bold text-on-surface">{{ $cat->name }}</td>
                            <td class="px-md py-sm">
                                <div class="flex flex-wrap gap-xs">
                                    @if($cat->visible_gudang)  <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">warehouse</span>Gudang</span> @endif
                                    @if($cat->visible_jihans)  <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">storefront</span>Jihan's</span> @endif
                                    @if($cat->visible_hendhys) <span class="inline-flex items-center gap-xs px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim text-[11px]"><span class="material-symbols-outlined text-[12px]">cake</span>Hendhys</span> @endif
                                    @if(!$cat->visible_gudang && !$cat->visible_jihans && !$cat->visible_hendhys) <span class="inline-flex items-center px-xs py-[2px] rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant border border-outline-variant text-[11px]">—</span> @endif
                                </div>
                            </td>
                            <td class="px-md py-sm text-center font-body-md text-body-md text-on-surface-variant">
                                {{ $cat->products_count }}</td>
                            <td class="px-md py-sm text-right">
                                <div class="flex items-center justify-end gap-sm">
                                    <button @click="editOpen = !editOpen"
                                        class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high transition-colors  shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">edit</span>
                                        Edit
                                    </button>
                                    <form method="POST"
                                        action="{{ route(($routePrefix ?? 'master.') . 'categories.destroy', $cat) }}"
                                        onsubmit="return confirm('Hapus kategori {{ $cat->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm text-label-sm hover:bg-error-container transition-colors  shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">delete</span>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                                {{-- Inline Edit Row --}}
                                <div x-show="editOpen" x-cloak class="mt-sm text-left">
                                    <form method="POST"
                                        action="{{ route(($routePrefix ?? 'master.') . 'categories.update', $cat) }}"
                                        class="flex flex-wrap gap-sm items-center">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $cat->name }}" required
                                            class="flex-1 min-w-[140px] bg-surface-container-low border-b border-primary focus:ring-0 font-body-md text-body-md text-on-surface px-sm py-xs rounded-t-sm outline-none">
                                        @include('master.partials.visibility-checkboxes', ['scope' => $currentScope ?? 'gudang', 'model' => $cat, 'isNew' => false])
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
                                    class="material-symbols-outlined text-[48px] text-outline opacity-40 mb-sm block">category</span>
                                <p class="font-label-lg text-label-lg font-medium">Belum ada kategori.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($categories->hasPages())
                <div class="bg-surface-container-low border-t border-outline-variant px-md py-sm">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div id="modal-add"
        class="hidden fixed inset-0 bg-on-surface/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-surface-container-lowest rounded-xl shadow-2xl p-lg w-full max-w-sm mx-md">
            <div class="flex items-center justify-between mb-md">
                <h3 class="font-headline-md text-title-lg text-on-surface">Tambah Kategori</h3>
                <button onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="p-xs rounded-full hover:bg-surface-container text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @php $defaultScope = ($currentScope ?? 'gudang') === 'gudang' ? 'all' : ($currentScope ?? 'all'); @endphp
            <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'categories.store') }}" class="space-y-md">
                @csrf
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Kategori <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all">
                        <input type="text" name="name" required placeholder="cth: Roti Tawar"
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
