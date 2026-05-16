@extends('layouts.hendhys')
@section('title', 'Ketersediaan Stok')
@section('page-title', 'Stok Barang ' . (auth()->user()->branch->type === 'pusat' ? 'Pusat Bakery' : 'Cabang ' . auth()->user()->branch->name))

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.stock.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode / Nama Produk..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[250px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Cari</button>
            @if(request()->filled('search'))
                <a href="{{ route('hendhys.stock.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        <a href="{{ route('hendhys.stock.movements') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg transition-colors text-sm font-medium whitespace-nowrap flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Histori Mutasi Stok
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium w-24">Kode</th>
                    <th class="p-4 font-medium">Nama Produk Bakery</th>
                    <th class="p-4 font-medium text-right">Kuantitas Fisik</th>
                    <th class="p-4 font-medium">Satuan Dasar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($stocks as $stock)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 font-medium text-gray-500">{{ $stock->code }}</td>
                    <td class="p-4 font-bold text-gray-800">{{ $stock->name }}</td>
                    <td class="p-4 text-right">
                        @php
                            $qty = (float) $stock->current_stock;
                        @endphp
                        @if($qty <= 0)
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded font-bold text-xs">Habis (0)</span>
                        @elseif($qty <= 10)
                            <span class="font-bold text-red-600 text-base">{{ $qty }}</span>
                        @else
                            <span class="font-bold text-gray-800 text-base">{{ $qty }}</span>
                        @endif
                    </td>
                    <td class="p-4 text-gray-600">{{ $stock->unit->code ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p>Data stok tidak ditemukan.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $stocks->links() }}
    </div>
    @endif
</div>
@endsection
