@extends('layouts.jihans')
@section('title', 'Return ke Gudang')
@section('page-title', 'Return Barang ke Gudang Utama')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('jihans.returns-to-gudang.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <select name="status" class="text-sm border-gray-300 rounded-lg focus:ring-[#f97316] focus:border-[#f97316]">
                <option value="">Semua Status</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Dalam Perjalanan (Sent)</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima Gudang (Received)</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Return..." class="text-sm border-gray-300 rounded-lg focus:ring-[#f97316] focus:border-[#f97316] min-w-[200px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['status', 'search']))
                <a href="{{ route('jihans.returns-to-gudang.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        <a href="{{ route('jihans.returns-to-gudang.create') }}" class="bg-[#f97316] hover:bg-[#ea580c] text-white px-5 py-2 rounded-lg transition-colors text-sm font-medium whitespace-nowrap flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Return ke Gudang
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium">Tanggal</th>
                    <th class="p-4 font-medium">No. Return</th>
                    <th class="p-4 font-medium">Item Diretur</th>
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($returns as $ret)
                <tr class="hover:bg-orange-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($ret->date)->format('d M Y') }}</td>
                    <td class="p-4 font-medium text-[#f97316]">{{ $ret->return_number }}</td>
                    <td class="p-4 text-gray-600">
                        @php $itemCount = $ret->details()->count(); @endphp
                        {{ $itemCount }} Jenis Barang
                    </td>
                    <td class="p-4">
                        @if($ret->status == 'sent')
                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Dikirim ke Gudang</span>
                        @elseif($ret->status == 'received')
                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Diterima Gudang</span>
                        @endif
                    </td>
                    <td class="p-4 text-right">
                        <a href="{{ route('jihans.returns-to-gudang.show', $ret->id) }}" class="text-[#f97316] hover:text-[#ea580c] font-medium text-xs bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-md transition-colors inline-block">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                        <p>Belum ada histori return barang ke Gudang Utama.</p>
                    </td>
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
