@extends('layouts.gudang')
@section('title', 'Transfer Requests (Approval)')
@section('page-title', 'Gudang — Transfer Requests')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Permintaan Barang (Transfer Request)</h2>
        <p class="text-sm text-gray-400">Approval permintaan dari Cabang (Hendhys) atau Entitas Lain (Jihans)</p>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menunggu Approval</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['pending'] }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Disetujui (Belum Dikirim)</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['approved'] }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Selesai (Completed)</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts['completed'] }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
        <form method="GET" action="{{ route('gudang.transfer-requests.index') }}" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Request..." 
                   class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            
            <select name="from_entity" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Asal Request</option>
                <option value="hendhys" {{ request('from_entity') == 'hendhys' ? 'selected' : '' }}>Hendhys (Cabang)</option>
                <option value="jihans" {{ request('from_entity') == 'jihans' ? 'selected' : '' }}>Jihans (Stok Gudang Jihans)</option>
            </select>
            
            <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'from_entity']))
                <a href="{{ route('gudang.transfer-requests.index') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
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
                    <th class="px-4 py-3 font-medium">No. Request</th>
                    <th class="px-4 py-3 font-medium">Asal (Peminta)</th>
                    <th class="px-4 py-3 font-medium text-center">Status</th>
                    <th class="px-4 py-3 font-medium">Diminta Oleh</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $statusClass = [
                        'pending'   => 'bg-yellow-100 text-yellow-700',
                        'approved'  => 'bg-blue-100 text-blue-700',
                        'partial'   => 'bg-indigo-100 text-indigo-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'rejected'  => 'bg-red-100 text-red-700',
                    ];
                @endphp
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500">{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $req->request_number }}</td>
                    <td class="px-4 py-3">
                        @if($req->from_entity === 'hendhys')
                            Hendhys <span class="text-xs text-gray-400">({{ $req->branch->name ?? 'Cabang' }})</span>
                        @else
                            Jihans <span class="text-xs text-gray-400">(Produksi)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $statusClass[$req->status] ?? 'bg-gray-100 text-gray-600' }} uppercase tracking-wider">
                            {{ $req->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $req->requester->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($req->status === 'pending')
                            <a href="{{ route('gudang.transfer-requests.show', $req) }}" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-medium">
                                Review & Approval
                            </a>
                        @else
                            <a href="{{ route('gudang.transfer-requests.show', $req) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Lihat Detail</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada data Transfer Request.</td>
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
