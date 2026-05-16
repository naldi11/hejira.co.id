@extends('layouts.jihans')
@section('title', 'Stok Tersedia')
@section('page-title', 'Stok Jihan\'s Food')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <div class="flex gap-2">
        <a href="{{ route('jihans.stock.index') }}" class="bg-orange-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-900 transition-colors">
            Stok Saat Ini
        </a>
        <a href="{{ route('jihans.stock.movements') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Kartu Stok (Histori)
        </a>
    </div>

    <form action="{{ route('jihans.stock.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <select name="jenis" class="border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">Semua Jenis</option>
            <option value="bahan_baku" {{ request('jenis') === 'bahan_baku' ? 'selected' : '' }}>Bahan Baku</option>
            <option value="bahan_jadi" {{ request('jenis') === 'bahan_jadi' ? 'selected' : '' }}>Bahan Jadi</option>
            <option value="lainnya" {{ request('jenis') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
        </select>
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk..." 
                   class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900">
            Cari
        </button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-medium">Kode</th>
                    <th class="px-6 py-4 font-medium">Nama Produk</th>
                    <th class="px-6 py-4 font-medium">Kategori</th>
                    <th class="px-6 py-4 font-medium">Jenis</th>
                    <th class="px-6 py-4 font-medium text-right">Stok Jihan's</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($stocks as $stock)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $stock->code }}</span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        {{ $stock->name }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $stock->category->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($stock->jenis === 'bahan_baku')
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10">Bahan Baku</span>
                        @elseif($stock->jenis === 'bahan_jadi')
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">Bahan Jadi</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10">Lainnya</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @php
                            $qty = (float) ($stock->current_stock ?? 0);
                        @endphp
                        <div class="flex items-center justify-end gap-2">
                            @if($qty <= 0)
                                <span class="text-xs font-medium text-red-500 bg-red-50 px-2 py-0.5 rounded">Habis</span>
                            @elseif($qty <= 50)
                                <span class="text-xs font-medium text-orange-500 bg-orange-50 px-2 py-0.5 rounded">Menipis</span>
                            @endif
                            <span class="font-bold {{ $qty > 0 ? 'text-gray-900' : 'text-red-600' }} text-base">{{ $qty }}</span>
                            <span class="text-gray-500 text-xs">{{ $stock->unit->abbreviation ?? '' }}</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p>Tidak ada data stok ditemukan.</p>
                        </div>
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
