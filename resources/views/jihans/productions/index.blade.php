@extends('layouts.jihans')
@section('title', 'Data Produksi')
@section('page-title', 'Hasil Produksi Tortilla')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <a href="{{ route('jihans.productions.create') }}" class="bg-orange-800 hover:bg-orange-900 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Input Produksi
    </a>

    <form action="{{ route('jihans.productions.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Mulai Tgl">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Sampai Tgl">
        <div class="relative flex-1 sm:w-48">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Batch..." 
                   class="w-full pl-8 pr-4 py-2 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900">
            Filter
        </button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-medium">Tanggal</th>
                    <th class="px-6 py-4 font-medium">No. Batch Produksi</th>
                    <th class="px-6 py-4 font-medium">Produk Tortilla</th>
                    <th class="px-6 py-4 font-medium text-center">Ukuran</th>
                    <th class="px-6 py-4 font-medium text-right">Hasil (Qty)</th>
                    <th class="px-6 py-4 font-medium">PIC</th>
                    <th class="px-6 py-4 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productions as $prod)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ \Carbon\Carbon::parse($prod->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-semibold text-gray-800">{{ $prod->production_number }}</span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        {{ $prod->product->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize 
                            {{ $prod->size === 'besar' ? 'bg-purple-100 text-purple-800' : ($prod->size === 'sedang' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                            {{ $prod->size }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-bold text-gray-900">{{ (float) $prod->quantity_produced }}</span>
                        <span class="text-xs text-gray-500">{{ $prod->unit->abbreviation ?? '' }}</span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $prod->creator->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('jihans.productions.show', $prod) }}" class="text-orange-600 hover:text-orange-900 font-medium text-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <p>Belum ada data produksi yang dicatat.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($productions->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $productions->links() }}
    </div>
    @endif
</div>
@endsection
