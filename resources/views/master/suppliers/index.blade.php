@extends($layout ?? 'layouts.gudang')
@section('title', 'Daftar Supplier')
@section('page-title', 'Master Data — Supplier')

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-headline">Daftar Supplier</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">{{ $suppliers->total() }} mitra supplier terdaftar</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.create') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Supplier
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[280px] relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, atau telepon supplier..."
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-sm">
            </div>
            
            <div class="min-w-[180px]">
                <select name="status"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none text-sm appearance-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <button type="submit"
                class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Cari
            </button>

            @if(request('search') || request('status') !== null)
                <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.index') }}"
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
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nama Supplier</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kontak Personal</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Telepon</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-bold text-slate-400 px-2 py-1 bg-slate-100 rounded-lg">{{ $supplier->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-black text-slate-900">{{ $supplier->name }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-500">
                                {{ $supplier->contact_person ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-500">
                                {{ $supplier->phone ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($supplier->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.edit', $supplier) }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'suppliers.destroy', $supplier) }}"
                                        onsubmit="return confirm('Hapus supplier {{ $supplier->name }}?')">
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
                            <td colspan="6" class="px-6 py-12 text-center bg-slate-50/30">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-3xl flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-slate-300">local_shipping</span>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak ada data supplier</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
@endsection
