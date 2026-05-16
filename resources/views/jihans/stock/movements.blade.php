@extends('layouts.jihans')
@section('title', 'Histori Pergerakan Stok')
@section('page-title', 'Kartu Stok Jihan\'s Food')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <div class="flex gap-2">
        <a href="{{ route('jihans.stock.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Stok Saat Ini
        </a>
        <a href="{{ route('jihans.stock.movements') }}" class="bg-orange-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-900 transition-colors">
            Kartu Stok (Histori)
        </a>
    </div>

    <form action="{{ route('jihans.stock.movements') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <select name="type" class="border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">Semua Tipe</option>
            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Masuk (In)</option>
            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Keluar (Out)</option>
        </select>
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk..." 
                   class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                    <th class="px-6 py-4 font-medium">Tanggal & Waktu</th>
                    <th class="px-6 py-4 font-medium">Produk</th>
                    <th class="px-6 py-4 font-medium">Tipe</th>
                    <th class="px-6 py-4 font-medium">Sumber / Referensi</th>
                    <th class="px-6 py-4 font-medium text-right">Qty</th>
                    <th class="px-6 py-4 font-medium text-right">Stok Akhir</th>
                    <th class="px-6 py-4 font-medium">Operator</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ \Carbon\Carbon::parse($movement->created_at)->format('d M Y, H:i') }}
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        {{ $movement->product->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($movement->type === 'in')
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                Masuk
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                Keluar
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs uppercase tracking-wider font-semibold text-gray-500">{{ str_replace('_', ' ', $movement->source) }}</span>
                        @if($movement->reference_id)
                            <div class="text-xs text-gray-400 mt-0.5">Ref ID: {{ $movement->reference_id }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-bold {{ $movement->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $movement->type === 'in' ? '+' : '-' }}{{ (float) $movement->quantity }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-bold text-gray-900">{{ (float) $movement->quantity_after }}</span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $movement->creator->name ?? 'Sistem' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Tidak ada histori pergerakan stok.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection
