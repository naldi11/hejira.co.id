@extends('layouts.gudang')
@section('title', 'Transfer Keluar (DO / Pengeluaran)')
@section('page-title', 'Gudang — Transfer Keluar')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Transfer Keluar Barang</h2>
        <p class="text-sm text-gray-400">Pengiriman barang dari Gudang Utama ke Cabang (Hendhys) atau Produksi (Jihans)</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('gudang.transfer-out.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Transfer Baru (Tanpa Request)
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
        <form method="GET" action="{{ route('gudang.transfer-out.index') }}" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Dokumen (DO)..." 
                   class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            
            <select name="to_entity" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Tujuan</option>
                <option value="hendhys" {{ request('to_entity') == 'hendhys' ? 'selected' : '' }}>Hendhys (Cabang)</option>
                <option value="jihans" {{ request('to_entity') == 'jihans' ? 'selected' : '' }}>Jihans (Stok Produksi)</option>
            </select>
            
            <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['search', 'to_entity']))
                <a href="{{ route('gudang.transfer-out.index') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
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
                    <th class="px-4 py-3 font-medium">No. Transfer (DO)</th>
                    <th class="px-4 py-3 font-medium">Tujuan</th>
                    <th class="px-4 py-3 font-medium">Referensi Request</th>
                    <th class="px-4 py-3 font-medium">Dibuat Oleh</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transfers as $trf)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500">{{ \Carbon\Carbon::parse($trf->date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $trf->transfer_number }}</td>
                    <td class="px-4 py-3">
                        @if($trf->to_entity === 'hendhys')
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Hendhys <span class="text-xs text-gray-400">({{ $trf->branch->name ?? '-' }})</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                Jihans <span class="text-xs text-gray-400">(Produksi)</span>
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($trf->request)
                            <a href="{{ route('gudang.transfer-requests.show', $trf->request) }}" class="text-indigo-600 hover:underline font-medium text-xs">{{ $trf->request->request_number }}</a>
                        @else
                            <span class="text-gray-400 text-xs italic">Tanpa Request (Direct)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $trf->creator->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('gudang.transfer-out.show', $trf) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Lihat Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada data Transfer Keluar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transfers->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $transfers->links() }}
    </div>
    @endif
</div>
@endsection
