@extends('layouts.gudang')
@section('title', 'Penerimaan Retur dari Cabang/Entitas')
@section('page-title', 'Gudang — Penerimaan Retur')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Penerimaan Retur Barang</h2>
        <p class="text-sm text-gray-400">Penerimaan kembali barang retur dari Hendhys Pusat atau Jihan's Food ke Gudang Utama</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
        <form method="GET" action="{{ route('gudang.returns.index') }}" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Retur..." 
                   class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            
            <select name="entity" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Asal</option>
                <option value="hendhys" {{ request('entity') == 'hendhys' ? 'selected' : '' }}>Hendhys (Pusat)</option>
                <option value="jihans" {{ request('entity') == 'jihans' ? 'selected' : '' }}>Jihans (Food)</option>
            </select>

            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Dalam Perjalanan (Sent)</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima Gudang (Received)</option>
            </select>
            
            <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['search', 'entity', 'status']))
                <a href="{{ route('gudang.returns.index') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
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
                    <th class="px-4 py-3 font-medium">No. Retur</th>
                    <th class="px-4 py-3 font-medium">Asal Entitas</th>
                    <th class="px-4 py-3 font-medium">Jumlah Item</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($returns as $ret)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500">{{ \Carbon\Carbon::parse($ret->date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $ret->return_number }}</td>
                    <td class="px-4 py-3">
                        @if($ret->from_entity === 'hendhys')
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Hendhys <span class="text-xs text-gray-400">({{ $ret->branch->name ?? 'Pusat' }})</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                Jihans <span class="text-xs text-gray-400">(Produksi)</span>
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $ret->details()->count() }} jenis barang
                    </td>
                    <td class="px-4 py-3">
                        @if($ret->status == 'sent')
                            <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Dikirim</span>
                        @elseif($ret->status == 'received')
                            <span class="px-2 py-1 rounded bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Diterima</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('gudang.returns.show', $ret) }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 hover:text-indigo-900 px-3 py-1 rounded text-xs font-bold">
                            @if($ret->status == 'sent')
                                Proses Penerimaan
                            @else
                                Lihat Detail
                            @endif
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada data retur barang masuk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($returns->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $returns->links() }}
    </div>
    @endif
</div>
@endsection
