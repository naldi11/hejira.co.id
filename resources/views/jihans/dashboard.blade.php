@extends('layouts.jihans')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    {{-- Card 1: Total Produksi Hari Ini --}}
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Produksi Hari Ini</p>
            <p class="text-2xl font-bold text-gray-800">{{ \App\Models\JihansProduction::whereDate('date', now())->count() }} <span class="text-sm font-normal text-gray-500">batch</span></p>
        </div>
    </div>

    {{-- Card 2: Penjualan Hari Ini --}}
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Omset Hari Ini</p>
            <p class="text-xl font-bold text-gray-800">Rp {{ number_format(\App\Models\JihansTransaction::whereDate('date', now())->where('status', 'paid')->sum('grand_total'), 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Card 3: Transaksi Pending --}}
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Transaksi Pending</p>
            <p class="text-2xl font-bold text-gray-800">{{ \App\Models\JihansPendingTransaction::count() }} <span class="text-sm font-normal text-gray-500">hold</span></p>
        </div>
    </div>

    {{-- Card 4: Request Status --}}
    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium">Request Pending</p>
            <p class="text-2xl font-bold text-gray-800">{{ \App\Models\TransferRequest::where('from_entity', 'jihans')->where('status', 'pending')->count() }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Transaksi Terakhir --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Penjualan Terakhir</h3>
            <a href="{{ route('jihans.pos.index') }}" class="text-sm text-orange-600 hover:text-orange-700 font-medium">Ke Kasir &rarr;</a>
        </div>
        <div class="p-0">
            @php
                $recentTrx = \App\Models\JihansTransaction::with('creator')->latest('id')->take(5)->get();
            @endphp
            @if($recentTrx->isEmpty())
                <div class="p-5 text-center text-gray-500 text-sm">Belum ada transaksi</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($recentTrx as $trx)
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-800">{{ $trx->transaction_number }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trx->date)->format('d/m/Y') }} {{ $trx->time }} &bull; {{ $trx->customer_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</p>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Paid</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stok Menipis --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Stok Menipis (Butuh Request)</h3>
            <a href="{{ route('jihans.stock.index') }}" class="text-sm text-orange-600 hover:text-orange-700 font-medium">Lihat Stok &rarr;</a>
        </div>
        <div class="p-0">
            @php
                $lowStocks = \App\Models\Product::where('status', 'active')->whereIn('master_products.entity_scope', ['jihans', 'all'])
                    ->join('jihans_stock', 'master_products.id', '=', 'jihans_stock.product_id')
                    ->where('jihans_stock.quantity', '<=', 50)
                    ->select('master_products.*', 'jihans_stock.quantity as current_stock')
                    ->take(5)
                    ->get();
            @endphp
            @if($lowStocks->isEmpty())
                <div class="p-5 text-center text-gray-500 text-sm">Semua stok aman</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($lowStocks as $st)
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ $st->name }}</p>
                                <p class="text-xs text-gray-500">{{ $st->code }} &bull; {{ $st->jenis }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-red-600 text-lg">{{ (float) $st->current_stock }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
