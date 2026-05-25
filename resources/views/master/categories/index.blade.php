@extends($layout ?? 'layouts.gudang')
@section('title', 'Kategori Produk')
@section('page-title', 'Master Data — Kategori Produk')

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-headline">Kategori Produk</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">{{ $categories->total() }} kategori kategori produk terdaftar</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                Tambah Kategori
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[280px] relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kategori..."
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-sm">
            </div>
            <button type="submit"
                class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route(($routePrefix ?? 'master.') . 'categories.index') }}"
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
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nama Kategori</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Visibilitas Entitas</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Total Produk</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Aksi</th>
                    </tr>
                </thead>
                <tbody x-data="{ editingId: null }">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <template x-if="editingId !== {{ $cat->id }}">
                                    <p class="text-sm font-black text-slate-900">{{ $cat->name }}</p>
                                </template>
                                <template x-if="editingId === {{ $cat->id }}">
                                    <form id="form-edit-{{ $cat->id }}" method="POST" action="{{ route(($routePrefix ?? 'master.') . 'categories.update', $cat) }}" class="flex items-center gap-2">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $cat->name }}" required
                                            class="flex-1 px-4 py-2 bg-white border-2 border-indigo-500 rounded-xl focus:ring-4 focus:ring-indigo-500/10 outline-none text-sm font-bold text-slate-900">
                                    </form>
                                </template>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if($cat->visible_gudang)  <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-slate-200 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">warehouse</span>Gudang</span> @endif
                                    @if($cat->visible_jihans)  <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-slate-200 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">storefront</span>Jihans</span> @endif
                                    @if($cat->visible_hendhys) <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-slate-200 flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">cake</span>Hendhys</span> @endif
                                    @if(!$cat->visible_gudang && !$cat->visible_jihans && !$cat->visible_hendhys) <span class="text-[10px] font-bold text-slate-300 italic">— Tidak terlihat</span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 bg-indigo-50 text-indigo-600 text-[11px] font-black rounded-lg border border-indigo-100">
                                    {{ $cat->products_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <template x-if="editingId !== {{ $cat->id }}">
                                        <div class="flex items-center gap-2">
                                            <button @click="editingId = {{ $cat->id }}"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-all border border-slate-200">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'categories.destroy', $cat) }}"
                                                onsubmit="return confirm('Hapus kategori {{ $cat->name }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-all border border-slate-200">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                    <template x-if="editingId === {{ $cat->id }}">
                                        <div class="flex items-center gap-2">
                                            <button form="form-edit-{{ $cat->id }}" type="submit"
                                                class="px-4 py-2 bg-emerald-500 text-white rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                                                Simpan
                                            </button>
                                            <button @click="editingId = null"
                                                class="px-4 py-2 bg-white border border-slate-200 text-slate-400 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-all">
                                                Batal
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center bg-slate-50/30">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-3xl flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-slate-300">category</span>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak ada data kategori</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Tambah --}}
    <div id="modal-add" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-add').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="p-8">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-[32px]">category</span>
                </div>
                <h3 class="text-xl font-black text-slate-900 font-headline mb-2">Tambah Kategori</h3>
                <p class="text-sm text-slate-500 mb-8 leading-relaxed">Klasifikasikan produk Anda dengan membuat kategori baru.</p>
                
                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'categories.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nama Kategori <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="cth: Roti Tawar, Minuman, dsb..."
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-sm font-bold text-slate-900">
                    </div>

                    <div class="pt-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-4 block">Tampilkan di Entitas</label>
                        <div class="flex gap-3">
                            @php
                                $scope = $currentScope ?? 'gudang';
                                $defGudang  = ($scope === 'gudang');
                                $defJihans  = in_array($scope, ['gudang','jihans']);
                                $defHendhys = in_array($scope, ['gudang','hendhys']);
                            @endphp
                            @foreach([
                                ['visible_gudang',  'Gudang',   'warehouse',  $defGudang],
                                ['visible_jihans',  "Jihan's",    'storefront', $defJihans],
                                ['visible_hendhys', 'Hendhys', 'cake',       $defHendhys],
                            ] as [$fieldName, $label, $icon, $checked])
                                <label x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
                                    :class="on ? 'border-indigo-600 bg-indigo-50 text-indigo-600' : 'border-slate-100 bg-slate-50 text-slate-400'"
                                    class="flex-1 flex flex-col items-center justify-center p-3 rounded-2xl border-2 cursor-pointer transition-all select-none">
                                    <input type="checkbox" name="{{ $fieldName }}" value="1" x-model="on" class="hidden">
                                    <span class="material-symbols-outlined text-[20px] mb-1" :class="on ? 'fill' : ''">{{ $icon }}</span>
                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" 
                            class="flex-1 px-6 py-4 bg-white border-2 border-slate-200 text-slate-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all">Batal</button>
                        <button type="submit" 
                            class="flex-1 px-6 py-4 bg-indigo-600 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
