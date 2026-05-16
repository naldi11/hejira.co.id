@extends('layouts.gudang')
@section('title','Penerimaan Barang (GRN)')
@section('page-title','Gudang — Penerimaan Barang')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Penerimaan Barang (GRN)</h2>
        <p class="text-sm text-gray-400">{{ $receivings->total() }} dokumen penerimaan</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('gudang.receiving.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat GRN Baru
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
        <form method="GET" action="{{ route('gudang.receiving.index') }}" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. GRN atau Supplier..." 
                   class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <span class="text-gray-400 flex items-center">-</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                <a href="{{ route('gudang.receiving.index') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">No. GRN</th>
                    <th class="px-4 py-3 font-medium">Supplier</th>
                    <th class="px-4 py-3 font-medium">Referensi PO</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($receivings as $grn)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($grn->date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $grn->grn_number }}</td>
                    <td class="px-4 py-3">{{ $grn->supplier->name }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        @if($grn->po)
                            <a href="{{ route('gudang.po.show', $grn->po->id) }}" class="text-indigo-600 hover:underline">{{ $grn->po->po_number }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('gudang.receiving.show', $grn) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Lihat Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada data penerimaan barang.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($receivings->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $receivings->links() }}
    </div>
    @endif
</div>
@endsection
