@extends('layouts.owner')
@section('title', "Dashboard Jihan's")
@section('page-title', "Performa Jihan's Food")

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    {{-- Card 1 --}}
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-md p-6 text-white">
        <p class="text-orange-100 font-medium text-sm mb-1 uppercase tracking-wider">Total Pendapatan (All Time)</p>
        <p class="text-3xl font-black mb-2">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
    </div>

    {{-- Card 2 --}}
    <div class="bg-gradient-to-br from-white to-orange-50 border border-orange-100 rounded-xl shadow-sm p-6 text-slate-800">
        <p class="text-slate-500 font-bold text-sm mb-1 uppercase tracking-wider">Pendapatan Hari Ini</p>
        <p class="text-3xl font-black mb-2 text-orange-600">Rp {{ number_format($revenueToday, 0, ',', '.') }}</p>
    </div>

    {{-- Card 3 --}}
    <div class="bg-gradient-to-br from-white to-orange-50 border border-orange-100 rounded-xl shadow-sm p-6 text-slate-800">
        <p class="text-slate-500 font-bold text-sm mb-1 uppercase tracking-wider">Sesi Produksi Hari Ini</p>
        <p class="text-3xl font-black mb-2 text-slate-800">{{ $totalProductionToday }} <span class="text-lg font-medium opacity-80 text-slate-500">batch</span></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    {{-- Produk Terlaris --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <h3 class="font-bold text-slate-800">5 Produk Tortilla Terlaris</h3>
        </div>
        <div class="p-0">
            @if($topProducts->isEmpty())
                <div class="p-5 text-center text-slate-500 text-sm">Belum ada data penjualan.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($topProducts as $idx => $prod)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm">{{ $idx + 1 }}</span>
                            <p class="font-bold text-slate-800">{{ $prod->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-500">Terjual</p>
                            <p class="font-black text-orange-600">{{ (float) $prod->total_sold }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Transaksi Terakhir --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-800">5 Transaksi POS Terakhir</h3>
        </div>
        <div class="p-0">
            @if($recentTransactions->isEmpty())
                <div class="p-5 text-center text-slate-500 text-sm">Belum ada transaksi.</div>
            @else
                <div class="divide-y divide-slate-100 text-sm">
                    @foreach($recentTransactions as $trx)
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between hover:bg-slate-50 transition-colors gap-2">
                        <div>
                            <p class="font-bold text-slate-800">{{ $trx->transaction_number }}</p>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($trx->date)->format('d/m/Y') }} {{ $trx->time }} &bull; Kasir: {{ $trx->creator->name }}</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="font-black text-slate-900">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</p>
                            @if($trx->status == 'paid')
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 font-bold rounded text-[10px] uppercase">Lunas</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 font-bold rounded text-[10px] uppercase">Batal</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
