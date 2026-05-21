@extends($layout ?? 'layouts.gudang')
@section('title', 'Satuan')
@section('page-title', 'Master Data — Satuan')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-background">Satuan Produk</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Kelola data satuan ukur untuk produk dan
                    material.</p>
            </div>
            <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Satuan
            </button>
        </div>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low">
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Menampilkan <strong class="text-on-surface">{{ $units->firstItem() ?? 0 }}</strong> â€“ <strong
                        class="text-on-surface">{{ $units->lastItem() ?? 0 }}</strong> dari <strong
                        class="text-on-surface">{{ $units->total() }}</strong> total satuan
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                                Satuan</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                Singkatan</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Scope
                            </th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">
                                Jml Produk</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody x-data="{}">
                        @forelse($units as $unit)
                            <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors group"
                                x-data="{ editOpen: false }">
                                <td class="px-md py-sm font-label-lg text-label-lg font-bold text-on-surface">{{ $unit->name }}
                                </td>
                                <td class="px-md py-sm">
                                    <span
                                        class="inline-flex items-center px-sm py-xs rounded-md font-mono font-bold text-xs bg-primary-fixed text-on-primary-fixed-variant border border-primary-fixed-dim">
                                        {{ $unit->abbreviation }}
                                    </span>
                                </td>
                                <td class="px-md py-sm">
                                    <span
                                        class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim">
                                        {{ ucfirst($unit->entity_scope ?? 'All') }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-center font-body-md text-body-md text-on-surface-variant">
                                    {{ $unit->products_count }}</td>
                                <td class="px-md py-sm text-right">
                                    <div class="flex items-center justify-end gap-sm">
                                        <button @click="editOpen = !editOpen"
                                            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high transition-colors  shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </button>
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'units.destroy', $unit) }}"
                                            onsubmit="return confirm('Hapus satuan {{ $unit->name }}?')" class="inline-block">
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
                                            action="{{ route(($routePrefix ?? 'master.') . 'units.update', $unit) }}"
                                            class="flex flex-wrap gap-sm items-center">
                                            @csrf @method('PUT')
                                            <input type="text" name="name" value="{{ $unit->name }}" required
                                                placeholder="Nama Satuan"
                                                class="flex-1 min-w-[120px] bg-surface-container-low border-b border-primary focus:ring-0 font-body-md text-body-md text-on-surface px-sm py-xs rounded-t-sm outline-none">
                                            <input type="text" name="abbreviation" value="{{ $unit->abbreviation }}" required
                                                maxlength="10" placeholder="Singkatan"
                                                class="w-24 font-mono bg-surface-container-low border-b border-primary focus:ring-0 font-body-md text-body-md text-on-surface px-sm py-xs rounded-t-sm outline-none">
                                            <button type="submit"
                                                class="px-sm py-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary-fixed-variant ">Simpan</button>
                                            <button type="button" @click="editOpen = false"
                                                class="px-sm py-xs bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high ">Batal</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[48px] text-outline opacity-40 mb-sm block">straighten</span>
                                    <p class="font-label-lg text-label-lg font-medium">Belum ada data satuan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($units->hasPages())
                <div class="bg-surface-container-low border-t border-outline-variant px-md py-sm">
                    {{ $units->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div id="modal-add"
        class="hidden fixed inset-0 bg-on-surface/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-surface-container-lowest rounded-xl shadow-2xl p-lg w-full max-w-sm mx-md">
            <div class="flex items-center justify-between mb-md">
                <h3 class="font-headline-md text-title-lg text-on-surface">Tambah Satuan Baru</h3>
                <button onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="p-xs rounded-full hover:bg-surface-container text-on-surface-variant transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'units.store') }}" class="space-y-md">
                @csrf
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Satuan <span
                            class="text-error">*</span></label>
                    <div
                        class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all">
                        <input type="text" name="name" required placeholder="cth: Kilogram"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Singkatan <span
                            class="text-error">*</span></label>
                    <div
                        class="bg-surface-container-low rounded-t-lg border-b border-outline-variant focus-within:border-primary focus-within:border-b-2 transition-all">
                        <input type="text" name="abbreviation" required maxlength="10" placeholder="cth: KG"
                            class="bg-transparent border-none focus:ring-0 w-full font-mono font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>
                <div class="flex gap-sm pt-xs">
                    <button type="submit"
                        class="flex-1 bg-primary text-on-primary py-sm rounded-lg font-label-lg text-label-lg hover:bg-on-primary-fixed-variant  transition-all">Simpan</button>
                    <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                        class="flex-1 border border-outline-variant text-on-surface-variant py-sm rounded-lg font-label-lg text-label-lg hover:bg-surface-container transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endsection
