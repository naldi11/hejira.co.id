@extends('layouts.gudang')
@section('title','Purchase Order')
@section('page-title','Purchase Order')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Pesanan Pembelian (PO)</h2>
            <p class="text-sm text-slate-500 font-medium">Kelola pesanan stok barang ke supplier/vendor</p>
        </div>
        <a href="{{ route('gudang.po.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
            Buat PO Baru
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Search & Filter --}}
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" action="{{ route('gudang.po.index') }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[250px] relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. PO atau Supplier..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                </div>
                
                <select name="status" class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="">Semua Status</option>
                    @foreach(['draft'=>'Draft','sent'=>'Terkirim','partial'=>'Sebagian','received'=>'Diterima','cancelled'=>'Batal'] as $val=>$lbl)
                        <option value="{{ $val }}" {{ request('status')===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                
                <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg shadow-slate-900/10">
                    Filter
                </button>

                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('gudang.po.index') }}" class="w-11 h-11 flex items-center justify-center bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-100 transition-all">
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
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Dokumen</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Supplier</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Nilai Pesanan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $statusClass = [
                            'draft'     => 'bg-slate-100 text-slate-600 border-slate-200',
                            'sent'      => 'bg-blue-50 text-blue-600 border-blue-100',
                            'partial'   => 'bg-amber-50 text-amber-600 border-amber-100',
                            'received'  => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                            'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100'
                        ];
                        $statusLabel = ['draft'=>'Draft','sent'=>'Terkirim','partial'=>'Sebagian','received'=>'Diterima','cancelled'=>'Batal'];
                    @endphp
                    @forelse($orders as $po)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-indigo-600 tracking-tight group-hover:underline underline-offset-4 cursor-pointer" onclick="window.location='{{ route('gudang.po.show', $po) }}'">{{ $po->po_number }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $po->date->translatedFormat('d M Y') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-[18px]">factory</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-700 uppercase tracking-tight">{{ $po->supplier->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">Est. Tiba: {{ $po->expected_date?->translatedFormat('d/m/y') ?? '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-black text-slate-900 tabular-nums">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border {{ $statusClass[$po->status] ?? '' }}">
                                {{ $statusLabel[$po->status] ?? $po->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('gudang.po.show', $po) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-white hover:text-indigo-600 transition-all">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">shopping_basket</span>
                                <p class="text-slate-400 font-bold italic">Belum ada data Purchase Order.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/30">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
