@extends($layout ?? 'layouts.jihans')
@section('title', 'Karyawan')
@section('page-title', 'Master Data — Sumber Daya')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Data Karyawan</h2>
            <p class="text-sm text-slate-500 font-medium">Kelola profil dan status aktif karyawan untuk operasional harian.</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Tambah Karyawan
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
        {{-- Search Area --}}
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[250px] relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama karyawan..."
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                </div>
                <select name="status" class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all">Cari</button>
            </form>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Karyawan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kontak</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($karyawans as $k)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-indigo-600 border border-slate-200 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ strtoupper(substr($k->name, 0, 1)) }}
                                </div>
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $k->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                                <span class="material-symbols-outlined text-[16px]">call</span>
                                {{ $k->phone ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border {{ $k->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100' }}">
                                {{ $k->is_active ? 'Aktif' : 'Off' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.edit', $k) }}" 
                                   class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'karyawan.destroy', $k) }}"
                                      onsubmit="return confirm('Hapus karyawan {{ $k->name }}?')" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-bold italic">Belum ada data karyawan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($karyawans->hasPages())
        <div class="p-6 border-t border-slate-100">
            {{ $karyawans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
