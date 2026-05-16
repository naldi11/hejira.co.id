@extends('layouts.owner')
@section('title', 'Dashboard Gudang')
@section('page-title', 'Pantauan Logistik Gudang Tempua')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Card 1 --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">PO Pending/Proses</p>
            <p class="text-2xl font-black text-slate-800">{{ $poPending }}</p>
        </div>
    </div>

    {{-- Card 2 --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center border border-green-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Penerimaan (Bulan Ini)</p>
            <p class="text-2xl font-black text-slate-800">{{ $receiveThisMonth }}</p>
        </div>
    </div>

    {{-- Card 3 --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center border border-orange-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Distribusi (Bulan Ini)</p>
            <p class="text-2xl font-black text-slate-800">{{ $transferOutThisMonth }}</p>
        </div>
    </div>

    {{-- Card 4 --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center border border-yellow-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Request Belum Diproses</p>
            <p class="text-2xl font-black text-slate-800">{{ $pendingRequests }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    {{-- Stok Tertinggi --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-800">5 Stok Bahan Baku Terbanyak</h3>
        </div>
        <div class="p-0">
            @if($topStocks->isEmpty())
                <div class="p-5 text-center text-slate-500 text-sm">Belum ada data stok.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($topStocks as $st)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div>
                            <p class="font-bold text-slate-800">{{ $st->product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $st->product->code }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 bg-green-50 text-green-700 font-bold rounded text-sm border border-green-100">{{ (float) $st->quantity }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stok Terendah --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-800">Peringatan: 5 Stok Menipis</h3>
        </div>
        <div class="p-0">
            @if($lowStocks->isEmpty())
                <div class="p-5 text-center text-slate-500 text-sm">Belum ada data stok.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($lowStocks as $st)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div>
                            <p class="font-bold text-slate-800">{{ $st->product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $st->product->code }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 {{ $st->quantity <= 10 ? 'bg-red-50 text-red-700 border-red-100' : 'bg-orange-50 text-orange-700 border-orange-100' }} font-bold rounded text-sm border">{{ (float) $st->quantity }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
