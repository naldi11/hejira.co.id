@extends($layout ?? 'layouts.gudang')
@section('title', 'Cabang')
@section('page-title', 'Master Data — Cabang')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Data Cabang Hendhys</h2>
            <p class="text-sm text-slate-500 font-medium">Manajemen unit bisnis dan outlet resmi Hendhys Brownies.</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'branches.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Tambah Cabang
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden max-w-5xl">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                Total: <strong class="text-slate-900 tabular-nums">{{ $branches->total() }}</strong> Cabang
            </span>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Identitas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Tipe</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kontak</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">User</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branches as $branch)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $branch->name }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5 font-mono">{{ $branch->code }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border {{ $branch->type === 'pusat' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-blue-50 text-blue-600 border-blue-100' }}">
                                {{ $branch->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                            {{ $branch->phone ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-[11px] font-black text-slate-600 tabular-nums border border-slate-200">
                                {{ $branch->users_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border {{ $branch->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100' }}">
                                {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'branches.edit', $branch) }}" 
                                   class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'branches.destroy', $branch) }}"
                                      onsubmit="return confirm('Hapus cabang {{ $branch->name }}?')" class="inline-block">
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
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">storefront</span>
                                <p class="text-slate-400 font-bold italic">Belum ada data cabang.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($branches->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/30">
            {{ $branches->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
