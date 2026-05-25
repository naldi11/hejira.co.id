@extends('layouts.gudang')
@section('title','Penerimaan Barang (GRN)')
@section('page-title','Penerimaan Barang')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Penerimaan Barang (GRN)</h2>
            <p class="text-sm text-slate-500 font-medium">Log masuk barang dari supplier berdasarkan dokumen PO</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gudang.receiving.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">archive</span>
                Buat GRN Baru
            </a>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Search & Filter --}}
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" action="{{ route('gudang.receiving.index') }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[250px] relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. GRN atau Supplier..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                </div>
                
                <div class="flex items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                    <span class="text-slate-300 material-symbols-outlined">trending_flat</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                </div>
                
                <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg shadow-slate-900/10">
                    Filter
                </button>

                @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                    <a href="{{ route('gudang.receiving.index') }}" class="w-11 h-11 flex items-center justify-center bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-100 transition-all">
                        <span class="material-symbols-outlined">refresh</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- Table Area --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Data Penerimaan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Supplier</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Referensi Dokumen</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($receivings as $grn)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $grn->grn_number }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ \Carbon\Carbon::parse($grn->date)->translatedFormat('d M Y') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
                                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                </div>
                                <span class="text-xs font-black text-slate-700 uppercase tracking-tight">{{ $grn->supplier->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($grn->po)
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PO:</span>
                                    <a href="{{ route('gudang.po.show', $grn->po->id) }}" class="text-xs font-black text-indigo-500 hover:underline underline-offset-4 decoration-2 tabular-nums">{{ $grn->po->po_number }}</a>
                                </div>
                            @else
                                <span class="text-xs font-bold text-slate-300 italic">Tanpa PO (Manual)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('gudang.receiving.show', $grn) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-white hover:text-indigo-600 transition-all">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">move_to_inbox</span>
                                <p class="text-slate-400 font-bold italic">Belum ada data penerimaan barang.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receivings->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/30">
            {{ $receivings->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
