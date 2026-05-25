@extends($layout ?? 'layouts.gudang')
@section('title', 'Brand')
@section('page-title', 'Master Data — Brand')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Brand / Merek</h2>
            <p class="text-sm text-slate-500 font-medium">Pengelompokan produk berdasarkan brand atau produsen.</p>
        </div>
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Tambah Brand
        </button>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                Total: <strong class="text-slate-900 tabular-nums">{{ count($brands) }}</strong> Brand
            </span>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nama Brand</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Visibilitas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Produk Terkait</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody x-data="{}">
                    @forelse($brands as $brand)
                        <tr class="hover:bg-slate-50/50 transition-colors group" x-data="{ editOpen: false }">
                            <td class="px-6 py-4">
                                <span class="text-sm font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $brand->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($brand->visible_gudang)  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-blue-50 text-blue-600 border border-blue-100"><span class="material-symbols-outlined text-[12px]">warehouse</span>Gudang</span> @endif
                                    @if($brand->visible_jihans)  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-orange-50 text-orange-600 border border-orange-100"><span class="material-symbols-outlined text-[12px]">bakery_dining</span>Jihan's</span> @endif
                                    @if($brand->visible_hendhys) <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-amber-50 text-amber-600 border border-amber-100"><span class="material-symbols-outlined text-[12px]">cake</span>Hendhys</span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold text-slate-500 tabular-nums">{{ number_format($brand->products_count) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editOpen = !editOpen"
                                        class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'brands.destroy', $brand) }}"
                                        onsubmit="return confirm('Hapus brand {{ $brand->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-slate-200">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>

                                {{-- Inline Edit --}}
                                <div x-show="editOpen" x-cloak 
                                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                                     class="mt-4 p-5 bg-slate-50 rounded-[1.5rem] border border-slate-200 text-left">
                                    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'brands.update', $brand) }}" class="space-y-4">
                                        @csrf @method('PUT')
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Brand</label>
                                            <input type="text" name="name" value="{{ $brand->name }}" required
                                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Visibilitas</label>
                                            @include('master.partials.visibility-checkboxes', ['scope' => $currentScope ?? 'gudang', 'model' => $brand, 'isNew' => false])
                                        </div>
                                        <div class="flex gap-2 pt-2">
                                            <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all">Simpan</button>
                                            <button type="button" @click="editOpen = false" class="px-6 py-2.5 bg-white text-slate-500 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-50">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">sell</span>
                                    <p class="text-slate-400 font-bold italic">Belum ada brand terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('modal-add').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
        <div class="p-8 sm:p-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-inner">
                        <span class="material-symbols-outlined text-[22px]">sell</span>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 font-headline tracking-tight">Tambah Brand</h3>
                </div>
                <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            @php $defaultScope = ($currentScope ?? 'gudang') === 'gudang' ? 'all' : ($currentScope ?? 'all'); @endphp
            <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'brands.store') }}" class="space-y-6">
                @csrf
                <div class="space-y-1">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 block">Nama Brand <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="cth: Jihan's, Hendhys, Generic..."
                        class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 block">Tampilkan di Entitas</label>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        @include('master.partials.visibility-checkboxes', ['scope' => $defaultScope, 'model' => null, 'isNew' => true])
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="submit" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">Simpan Brand</button>
                    <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
