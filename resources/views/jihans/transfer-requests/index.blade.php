@extends('layouts.jihans')
@section('title', 'Request ke Gudang')
@section('page-title', 'Request Bahan Baku ke Gudang')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <a href="{{ route('jihans.transfer-requests.create') }}" class="bg-orange-800 hover:bg-orange-900 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Buat Request Baru
    </a>

    <form action="{{ route('jihans.transfer-requests.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <select name="status" class="border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">Semua Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        </select>
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Request..." 
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
                    <th class="px-6 py-4 font-medium">No. Request</th>
                    <th class="px-6 py-4 font-medium">Tanggal</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 font-medium">Catatan</th>
                    <th class="px-6 py-4 font-medium">Oleh</th>
                    <th class="px-6 py-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-mono font-semibold text-gray-800">{{ $req->request_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ \Carbon\Carbon::parse($req->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-blue-100 text-blue-800',
                                'partial' => 'bg-indigo-100 text-indigo-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                            $color = $colors[$req->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase {{ $color }}">
                            {{ $req->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate">
                        {{ $req->notes ?: '-' }}
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $req->creator->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        @php
                            $pendingDO = $req->transferOuts->where('status', 'sent')->first();
                        @endphp
                        @if($pendingDO)
                            <a href="{{ route('jihans.transfer-requests.receive-form', $pendingDO->id) }}" class="inline-flex items-center gap-1 bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-orange-700 transition-colors shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Terima Barang
                            </a>
                        @else
                            <a href="{{ route('jihans.transfer-requests.show', $req) }}" class="text-orange-600 hover:text-orange-900 font-medium text-sm">Lihat Detail</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Tidak ada data transfer request.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $requests->links() }}
    </div>
    @endif
</div>
@endsection
