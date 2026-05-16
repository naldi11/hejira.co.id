@extends('layouts.owner')
@section('title', "Dashboard Hendhys")
@section('page-title', "Performa Hendhys Brownies")

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    {{-- Card 1 --}}
    <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-xl shadow-md p-6 text-white">
        <p class="text-amber-100 font-medium text-sm mb-1 uppercase tracking-wider">Total Pendapatan (All Time)</p>
        <p class="text-3xl font-black mb-2">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
    </div>

    {{-- Card 2 --}}
    <div class="bg-gradient-to-br from-white to-amber-50 border border-amber-100 rounded-xl shadow-sm p-6 text-slate-800">
        <p class="text-slate-500 font-bold text-sm mb-1 uppercase tracking-wider">Sesi Produksi Hari Ini (Pusat)</p>
        <p class="text-3xl font-black mb-2 text-slate-800">{{ $totalProductionToday }} <span class="text-lg font-medium opacity-80 text-slate-500">batch</span></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Performa per Cabang --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden lg:col-span-1">
        <div class="p-5 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-800">Pendapatan per Outlet</h3>
        </div>
        <div class="p-0">
            @if($revenueByBranch->isEmpty())
                <div class="p-5 text-center text-slate-500 text-sm">Belum ada data penjualan.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($revenueByBranch as $rev)
                    <div class="p-4 hover:bg-slate-50 transition-colors">
                        <p class="font-bold text-slate-800 mb-1">{{ $rev['branch'] }}</p>
                        <p class="text-lg font-black text-amber-600">Rp {{ number_format($rev['total'], 0, ',', '.') }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 flex flex-col gap-8">
        {{-- Transaksi Terakhir --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">5 Transaksi POS Terakhir</h3>
                <span class="text-xs text-slate-500 font-medium">Lintas Cabang</span>
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
                                <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($trx->date)->format('d/m/Y') }} {{ $trx->time }} &bull; Outlet: <span class="font-bold text-slate-700">{{ $trx->branch_id ? $trx->branch->name : 'Pusat' }}</span></p>
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

        {{-- Produk Terlaris --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <h3 class="font-bold text-slate-800">5 Produk Bakery Terlaris</h3>
            </div>
            <div class="p-0">
                @if($topProducts->isEmpty())
                    <div class="p-5 text-center text-slate-500 text-sm">Belum ada data penjualan.</div>
                @else
                    <div class="divide-y divide-slate-100 grid grid-cols-1 md:grid-cols-2">
                        @foreach($topProducts as $idx => $prod)
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors {{ $idx % 2 == 0 && count($topProducts) > 1 ? 'md:border-r border-slate-100' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold flex items-center justify-center text-xs">{{ $idx + 1 }}</span>
                                <p class="font-bold text-slate-800 text-sm">{{ $prod->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-amber-600">{{ (float) $prod->total_sold }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
