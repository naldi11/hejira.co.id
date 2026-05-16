@extends('layouts.hendhys')
@section('title', 'Produksi Bakery')
@section('page-title', 'Daftar Produksi Pusat')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.productions.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
            <span class="text-gray-500">-</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Produksi..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[200px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['date_from', 'date_to', 'search']))
                <a href="{{ route('hendhys.productions.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        <a href="{{ route('hendhys.productions.create') }}" class="bg-[#d97706] hover:bg-[#b45309] text-white px-5 py-2 rounded-lg transition-colors text-sm font-medium whitespace-nowrap flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Catat Produksi Baru
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium">Tanggal</th>
                    <th class="p-4 font-medium">No. Produksi</th>
                    <th class="p-4 font-medium">Item & Kuantitas</th>
                    <th class="p-4 font-medium">Operator</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($productions as $p)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($p->date)->format('d M Y') }}</td>
                    <td class="p-4 font-medium text-[#d97706]">{{ $p->production_number }}</td>
                    <td class="p-4">
                        <div class="space-y-1">
                        @foreach($p->details as $d)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                <span>{{ $d->product->name }}</span>
                                <span class="font-bold text-gray-700">({{ (float) $d->quantity_produced }} {{ $d->unit->code }})</span>
                            </div>
                        @endforeach
                        </div>
                    </td>
                    <td class="p-4">{{ $p->creator->name }}</td>
                    <td class="p-4 text-right">
                        <a href="{{ route('hendhys.productions.show', $p->id) }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-xs bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-md transition-colors">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p>Belum ada data produksi.</p>
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
